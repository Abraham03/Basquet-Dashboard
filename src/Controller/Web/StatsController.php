<?php
class StatsController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getDashboardStats($tournamentId) {
        if (empty($tournamentId)) {
            Response::json(['status' => 'error', 'message' => 'Tournament ID required'], 400);
        }
        
        $id = (int)$tournamentId;

        try {
            // 1. KPIs Generales
            $kpiQuery = "SELECT 
                (SELECT COUNT(*) FROM matches WHERE tournament_id = $id AND status='FINISHED') as total_matches,
                (SELECT COUNT(*) FROM tournament_teams WHERE tournament_id = $id) as total_teams,
                (SELECT IFNULL(AVG(score_a + score_b), 0) FROM matches WHERE tournament_id = $id AND status='FINISHED') as avg_points";
            $kpis = $this->queryOne($kpiQuery);

            // 2. Tabla de Posiciones (Con diferencia de puntos calculada)
            $standingsQuery = "
                SELECT t.name as team, 
                COUNT(CASE WHEN m.score_a > m.score_b AND m.team_a_id = t.id THEN 1 
                           WHEN m.score_b > m.score_a AND m.team_b_id = t.id THEN 1 END) as w,
                COUNT(CASE WHEN m.score_a < m.score_b AND m.team_a_id = t.id THEN 1 
                           WHEN m.score_b < m.score_a AND m.team_b_id = t.id THEN 1 END) as l,
                IFNULL(SUM(CASE WHEN m.team_a_id = t.id THEN m.score_a ELSE m.score_b END), 0) as pts_favor,
                IFNULL(SUM(CASE WHEN m.team_a_id = t.id THEN m.score_b ELSE m.score_a END), 0) as pts_contra,
                (IFNULL(SUM(CASE WHEN m.team_a_id = t.id THEN m.score_a ELSE m.score_b END), 0) - 
                 IFNULL(SUM(CASE WHEN m.team_a_id = t.id THEN m.score_b ELSE m.score_a END), 0)) as point_diff
                FROM teams t
                JOIN tournament_teams tt ON t.id = tt.team_id
                LEFT JOIN matches m ON (t.id = m.team_a_id OR t.id = m.team_b_id) AND m.tournament_id = $id AND m.status = 'FINISHED'
                WHERE tt.tournament_id = $id
                GROUP BY t.id
                ORDER BY w DESC, point_diff DESC
                LIMIT 10";
            $standings = $this->queryAll($standingsQuery);

            // 3. Top Anotadores (MVP Race)
            $scorersQuery = "
                SELECT p.name, SUM(sl.points_scored) as total_points, t.short_name as team
                FROM score_logs sl
                JOIN matches m ON sl.match_id = m.id
                JOIN players p ON sl.player_id = p.id
                JOIN teams t ON p.team_id = t.id
                WHERE m.tournament_id = $id
                GROUP BY sl.player_id
                ORDER BY total_points DESC
                LIMIT 5";
            $scorers = $this->queryAll($scorersQuery);

            // 4. NUEVO: Top Triples (Jugadores con más canastas de 3)
            $triplesQuery = "
                SELECT p.name, COUNT(*) as triples_made, t.short_name as team
                FROM score_logs sl
                JOIN matches m ON sl.match_id = m.id
                JOIN players p ON sl.player_id = p.id
                JOIN teams t ON p.team_id = t.id
                WHERE m.tournament_id = $id AND sl.points_scored = 3
                GROUP BY sl.player_id
                ORDER BY triples_made DESC
                LIMIT 5";
            $triples = $this->queryAll($triplesQuery);

            // 5. NUEVO: Mejores Defensas (Menos puntos recibidos promedio)
            $defenseQuery = "
                SELECT t.name as team, 
                       (SUM(CASE WHEN m.team_a_id = t.id THEN m.score_b ELSE m.score_a END) / COUNT(m.id)) as avg_allowed
                FROM teams t
                JOIN tournament_teams tt ON t.id = tt.team_id
                JOIN matches m ON (t.id = m.team_a_id OR t.id = m.team_b_id)
                WHERE m.tournament_id = $id AND m.status = 'FINISHED'
                GROUP BY t.id
                ORDER BY avg_allowed ASC
                LIMIT 5";
            $defense = $this->queryAll($defenseQuery);

            // 6. NUEVO: Puntos por Periodo (Para gráfica de línea)
            $periodQuery = "
                SELECT period, SUM(points_scored) as total 
                FROM score_logs sl 
                JOIN matches m ON sl.match_id = m.id 
                WHERE m.tournament_id = $id 
                GROUP BY period 
                ORDER BY period ASC";
            $periods = $this->queryAll($periodQuery);

            Response::json([
                'status' => 'success',
                'data' => [
                    'stats' => $kpis,
                    'standings' => $standings,
                    'top_scorers' => $scorers,
                    'top_triples' => $triples,
                    'best_defense' => $defense,
                    'periods' => $periods
                ]
            ]);

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