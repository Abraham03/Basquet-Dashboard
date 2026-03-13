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
                LEFT JOIN score_logs sl ON p.id = sl.player_id AND sl.match_id IN (SELECT id FROM matches WHERE tournament_id = $tId)
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
            // 0. Obtener TODAS las Reglas del Torneo para calcular Puntos Dinámicos
            $rulesQuery = "SELECT points_win, points_draw, points_loss, points_forfeit_win, points_forfeit_loss FROM tournament_rules WHERE tournament_id = $id LIMIT 1";
            $rules = $this->queryOne($rulesQuery);
            
            $ptsWin         = isset($rules['points_win']) ? (int)$rules['points_win'] : 2;
            $ptsDraw        = isset($rules['points_draw']) ? (int)$rules['points_draw'] : 1;
            $ptsLoss        = isset($rules['points_loss']) ? (int)$rules['points_loss'] : 1;
            $ptsForfeitWin  = isset($rules['points_forfeit_win']) ? (int)$rules['points_forfeit_win'] : 2;
            $ptsForfeitLoss = isset($rules['points_forfeit_loss']) ? (int)$rules['points_forfeit_loss'] : 0;

            // 1. KPIs Generales
            $kpiQuery = "SELECT 
                (SELECT COUNT(*) FROM matches WHERE tournament_id = $id AND status='FINISHED') as total_matches,
                (SELECT COUNT(*) FROM tournament_teams WHERE tournament_id = $id) as total_teams,
                (SELECT IFNULL(AVG(score_a + score_b), 0) FROM matches WHERE tournament_id = $id AND status='FINISHED') as avg_points";
            $kpis = $this->queryOne($kpiQuery);

            // 2. Tabla de Posiciones 
            $standingsQuery = "
                SELECT t.id, t.name as team, t.logo_url,
                
                -- Victorias (W)
                COUNT(CASE WHEN m.score_a > m.score_b AND m.team_a_id = t.id THEN 1 
                           WHEN m.score_b > m.score_a AND m.team_b_id = t.id THEN 1 END) as w,
                           
                -- Derrotas (L) incluyendo Doble Default
                COUNT(CASE WHEN m.score_a < m.score_b AND m.team_a_id = t.id THEN 1 
                           WHEN m.score_b < m.score_a AND m.team_b_id = t.id THEN 1 
                           WHEN m.forfeit_status = 'BOTH' AND (m.team_a_id = t.id OR m.team_b_id = t.id) THEN 1 END) as l,
                           
                -- Empates (D) asegurando que no haya forfeit
                COUNT(CASE WHEN m.score_a = m.score_b AND m.score_a IS NOT NULL AND m.forfeit_status = 'NONE' AND (m.team_a_id = t.id OR m.team_b_id = t.id) THEN 1 END) as d,

                -- CALCULO DE PUNTOS DINÁMICOS COMPLETOS
                (
                    -- Victorias Normales
                    COUNT(CASE WHEN m.score_a > m.score_b AND m.team_a_id = t.id AND m.forfeit_status = 'NONE' THEN 1 
                               WHEN m.score_b > m.score_a AND m.team_b_id = t.id AND m.forfeit_status = 'NONE' THEN 1 END) * $ptsWin +
                    
                    -- Derrotas Normales
                    COUNT(CASE WHEN m.score_a < m.score_b AND m.team_a_id = t.id AND m.forfeit_status = 'NONE' THEN 1 
                               WHEN m.score_b < m.score_a AND m.team_b_id = t.id AND m.forfeit_status = 'NONE' THEN 1 END) * $ptsLoss +
                    
                    -- Empates Normales
                    COUNT(CASE WHEN m.score_a = m.score_b AND m.score_a IS NOT NULL AND m.forfeit_status = 'NONE' AND (m.team_a_id = t.id OR m.team_b_id = t.id) THEN 1 END) * $ptsDraw +
                    
                    -- Victorias por Default (El equipo ganó porque el otro no se presentó)
                    COUNT(CASE WHEN m.team_a_id = t.id AND m.forfeit_status = 'TEAM_B' THEN 1 
                               WHEN m.team_b_id = t.id AND m.forfeit_status = 'TEAM_A' THEN 1 END) * $ptsForfeitWin +
                               
                    -- Derrotas por Default (El equipo faltó o hubo doble inasistencia)
                    COUNT(CASE WHEN m.team_a_id = t.id AND m.forfeit_status IN ('TEAM_A', 'BOTH') THEN 1 
                               WHEN m.team_b_id = t.id AND m.forfeit_status IN ('TEAM_B', 'BOTH') THEN 1 END) * $ptsForfeitLoss
                ) as pts,

                IFNULL(SUM(CASE WHEN m.team_a_id = t.id THEN m.score_a ELSE m.score_b END), 0) as pts_favor,
                IFNULL(SUM(CASE WHEN m.team_a_id = t.id THEN m.score_b ELSE m.score_a END), 0) as pts_contra,
                (IFNULL(SUM(CASE WHEN m.team_a_id = t.id THEN m.score_a ELSE m.score_b END), 0) - 
                 IFNULL(SUM(CASE WHEN m.team_a_id = t.id THEN m.score_b ELSE m.score_a END), 0)) as point_diff
                 
                FROM teams t
                JOIN tournament_teams tt ON t.id = tt.team_id
                LEFT JOIN matches m ON (t.id = m.team_a_id OR t.id = m.team_b_id) AND m.tournament_id = $id AND m.status = 'FINISHED'
                WHERE tt.tournament_id = $id
                GROUP BY t.id
                ORDER BY pts DESC, point_diff DESC
                LIMIT 10";
            $standings = $this->queryAll($standingsQuery);

            // 3. Top Anotadores
            $scorersQuery = "
                SELECT p.name, p.photo_url, SUM(sl.points_scored) as total_points, t.short_name as team
                FROM score_logs sl
                JOIN matches m ON sl.match_id = m.id
                JOIN players p ON sl.player_id = p.id
                JOIN teams t ON p.team_id = t.id
                WHERE m.tournament_id = $id
                GROUP BY sl.player_id
                ORDER BY total_points DESC
                LIMIT 5";
            $scorers = $this->queryAll($scorersQuery);

            // 4. Top Triples
            $triplesQuery = "
                SELECT p.name, p.photo_url, COUNT(*) as triples_made, t.short_name as team
                FROM score_logs sl
                JOIN matches m ON sl.match_id = m.id
                JOIN players p ON sl.player_id = p.id
                JOIN teams t ON p.team_id = t.id
                WHERE m.tournament_id = $id AND sl.points_scored = 3
                GROUP BY sl.player_id
                ORDER BY triples_made DESC
                LIMIT 5";
            $triples = $this->queryAll($triplesQuery);

            // 5. Mejores Defensas
            $defenseQuery = "
                SELECT t.name as team, t.logo_url, 
                       (SUM(CASE WHEN m.team_a_id = t.id THEN m.score_b ELSE m.score_a END) / COUNT(m.id)) as avg_allowed
                FROM teams t
                JOIN tournament_teams tt ON t.id = tt.team_id
                JOIN matches m ON (t.id = m.team_a_id OR t.id = m.team_b_id)
                WHERE m.tournament_id = $id AND m.status = 'FINISHED'
                GROUP BY t.id
                ORDER BY avg_allowed ASC
                LIMIT 5";
            $defense = $this->queryAll($defenseQuery);

            // 6. Puntos por Periodo
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
        $row = $res->fetch_assoc();
        return $row ? $row : null; 
    }

    private function queryAll($sql) {
        $res = $this->db->query($sql);
        if (!$res) throw new Exception($this->db->error);
        return $res->fetch_all(MYSQLI_ASSOC);
    }
}
?>