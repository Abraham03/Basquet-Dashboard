<?php
class StatsController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getTeamPlayerStats($tournamentId, $teamId) {
        $tId = (int)$tournamentId;
        $tmId = (int)$teamId;

        try {
            // Actualizado: Ahora verifica que el match_id exista en los fixtures oficiales de ese torneo
            $sql = "
                SELECT 
                    p.id, p.name, p.default_number, p.photo_url,
                    IFNULL(SUM(sl.points_scored), 0) as total_points,
                    IFNULL(SUM(CASE WHEN sl.points_scored = 3 THEN 1 ELSE 0 END), 0) as triples,
                    COALESCE(v.games_played, 0) AS games_played,
                    COALESCE(v.total_games, 0) AS total_games,
                    IF(v.total_games > 0, (v.games_played / v.total_games) * 100, 0) AS attendance_percentage,
                    COALESCE(v.min_attendance_percent, 60) AS min_attendance_percent
                FROM players p
                LEFT JOIN score_logs sl ON p.id = sl.player_id AND sl.match_id IN (
                    SELECT match_id FROM fixtures WHERE tournament_id = $tId AND match_id IS NOT NULL
                )
                LEFT JOIN v_player_attendance v ON p.id = v.player_id AND v.tournament_id = $tId
                WHERE p.team_id = $tmId AND p.active = 1
                GROUP BY p.id
                ORDER BY total_points DESC, p.default_number ASC
            ";
            $players = $this->queryAll($sql);
            Response::json(['status' => 'success', 'data' => $players]);
        } catch (Throwable $e) {
            Response::json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function getDashboardStats($tournamentId) {
        if (empty($tournamentId)) {
            Response::json(['status' => 'error', 'message' => 'Tournament ID required'], 400);
        }
        $id = (int)$tournamentId;

        try {
            // 0. Obtener Reglas dinámicas del torneo
            $rulesQuery = "SELECT points_win, points_loss, points_forfeit_win, points_forfeit_loss FROM tournament_rules WHERE tournament_id = ? LIMIT 1";
            $stmtRules = $this->db->prepare($rulesQuery);
            $stmtRules->bind_param("i", $id);
            $stmtRules->execute();
            $rules = $stmtRules->get_result()->fetch_assoc();

            $pW  = $rules['points_win'] ?? 2;
            $pL  = $rules['points_loss'] ?? 1; 
            $pFW = $rules['points_forfeit_win'] ?? 2;
            $pFL = $rules['points_forfeit_loss'] ?? 0;

            // 1. KPIs Generales (Tomando fixtures como base)
            $kpiQuery = "SELECT COUNT(f.id) as total_matches, 
                        (SELECT COUNT(*) FROM tournament_teams WHERE tournament_id = ?) as total_teams,
                        IFNULL(AVG(m.score_a + m.score_b), 0) as avg_points
                        FROM fixtures f
                        LEFT JOIN matches m ON f.match_id = m.id
                        WHERE f.tournament_id = ? AND f.status='FINISHED'";
            $stmtKpi = $this->db->prepare($kpiQuery);
            $stmtKpi->bind_param("ii", $id, $id);
            $stmtKpi->execute();
            $kpis = $stmtKpi->get_result()->fetch_assoc();

            // 2. Tabla de Posiciones (Enlazando equipos desde la planeación en fixtures)
            $standingsQuery = "
                SELECT t.id, t.name as team, t.logo_url,
                COUNT(m.id) as j,
                COUNT(CASE WHEN (m.forfeit_status = 'NONE' AND ((m.team_a_id = t.id AND m.score_a > m.score_b) OR (m.team_b_id = t.id AND m.score_b > m.score_a))) OR (m.team_a_id = t.id AND m.forfeit_status = 'TEAM_B') OR (m.team_b_id = t.id AND m.forfeit_status = 'TEAM_A') THEN 1 END) as jg,
                COUNT(CASE WHEN (m.team_a_id = t.id AND m.forfeit_status IN ('TEAM_A', 'BOTH')) OR (m.team_b_id = t.id AND m.forfeit_status IN ('TEAM_B', 'BOTH')) THEN 1 END) as jd,
                COUNT(CASE WHEN m.forfeit_status = 'NONE' AND ((m.team_a_id = t.id AND m.score_a < m.score_b) OR (m.team_b_id = t.id AND m.score_b < m.score_a)) THEN 1 END) as jp,
                (
                    (COUNT(CASE WHEN m.forfeit_status = 'NONE' AND ((m.team_a_id = t.id AND m.score_a > m.score_b) OR (m.team_b_id = t.id AND m.score_b > m.score_a)) THEN 1 END) * $pW) +
                    (COUNT(CASE WHEN (m.team_a_id = t.id AND m.forfeit_status = 'TEAM_B') OR (m.team_b_id = t.id AND m.forfeit_status = 'TEAM_A') THEN 1 END) * $pFW) +
                    (COUNT(CASE WHEN m.forfeit_status = 'NONE' AND ((m.team_a_id = t.id AND m.score_a < m.score_b) OR (m.team_b_id = t.id AND m.score_b < m.score_a)) THEN 1 END) * $pL) +
                    (COUNT(CASE WHEN (m.team_a_id = t.id AND m.forfeit_status IN ('TEAM_A', 'BOTH')) OR (m.team_b_id = t.id AND m.forfeit_status IN ('TEAM_B', 'BOTH')) THEN 1 END) * $pFL)
                ) as pts,
                IFNULL(SUM(CASE WHEN m.team_a_id = t.id THEN m.score_a ELSE m.score_b END), 0) as pts_favor,
                IFNULL(SUM(CASE WHEN m.team_a_id = t.id THEN m.score_b ELSE m.score_a END), 0) as pts_contra
                FROM teams t
                JOIN tournament_teams tt ON t.id = tt.team_id
                LEFT JOIN fixtures f ON (t.id = f.team_a_id OR t.id = f.team_b_id) AND f.tournament_id = ? AND f.status = 'FINISHED'
                LEFT JOIN matches m ON f.match_id = m.id AND m.status = 'FINISHED'
                WHERE tt.tournament_id = ?
                GROUP BY t.id 
                ORDER BY pts DESC, (IFNULL(SUM(CASE WHEN m.team_a_id = t.id THEN m.score_a ELSE m.score_b END), 0) - IFNULL(SUM(CASE WHEN m.team_a_id = t.id THEN m.score_b ELSE m.score_a END), 0)) DESC";

            $stmtSt = $this->db->prepare($standingsQuery);
            $stmtSt->bind_param("ii", $id, $id);
            $stmtSt->execute();
            $standings = $stmtSt->get_result()->fetch_all(MYSQLI_ASSOC);

            // 3. Top Anotadores
            $scorers = $this->queryAll("SELECT p.name, p.photo_url, SUM(sl.points_scored) as total_points, t.short_name as team FROM score_logs sl JOIN fixtures f ON sl.match_id = f.match_id JOIN players p ON sl.player_id = p.id JOIN teams t ON p.team_id = t.id WHERE f.tournament_id = $id GROUP BY sl.player_id ORDER BY total_points DESC LIMIT 5");

            // 4. Top Triples
            $triples = $this->queryAll("SELECT p.name, p.photo_url, COUNT(*) as triples_made, t.short_name as team FROM score_logs sl JOIN fixtures f ON sl.match_id = f.match_id JOIN players p ON sl.player_id = p.id JOIN teams t ON p.team_id = t.id WHERE f.tournament_id = $id AND sl.points_scored = 3 GROUP BY sl.player_id ORDER BY triples_made DESC LIMIT 5");

            // 5. Mejores Defensas
            $defense = $this->queryAll("SELECT t.name as team, t.logo_url, (SUM(CASE WHEN m.team_a_id = t.id THEN m.score_b ELSE m.score_a END) / COUNT(m.id)) as avg_allowed FROM teams t JOIN tournament_teams tt ON t.id = tt.team_id JOIN fixtures f ON (t.id = f.team_a_id OR t.id = f.team_b_id) AND f.tournament_id = $id AND f.status = 'FINISHED' JOIN matches m ON f.match_id = m.id GROUP BY t.id ORDER BY avg_allowed ASC LIMIT 5");

            // 6. Puntos por Periodo
            $periods = $this->queryAll("SELECT period, SUM(points_scored) as total FROM score_logs sl JOIN fixtures f ON sl.match_id = f.match_id WHERE f.tournament_id = $id GROUP BY period ORDER BY period ASC");

            Response::json(['status' => 'success', 'data' => [
                'stats' => $kpis, 
                'standings' => $standings,
                'top_scorers' => $scorers,
                'top_triples' => $triples,
                'best_defense' => $defense,
                'periods' => $periods
            ]]);

        } catch (Throwable $e) {
            Response::json(['status' => 'error', 'message' => 'SQL Error: ' . $e->getMessage()], 500);
        }
    }

    private function queryOne($sql) {
        $res = $this->db->query($sql);
        if (!$res) throw new Exception($this->db->error);
        return $res->fetch_assoc();
    }

    private function queryAll($sql) {
        $res = $this->db->query($sql);
        if (!$res) throw new Exception($this->db->error);
        return $res->fetch_all(MYSQLI_ASSOC);
    }
}
?>