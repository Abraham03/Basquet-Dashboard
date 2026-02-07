<?php
/**
 * Clase MatchRepository
 * Responsabilidad: Manejar la lógica de guardado y sincronización de partidos.
 * Usa Transacciones ACID para asegurar la integridad de datos.
 */
class MatchRepository {
    private mysqli $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Sincroniza un partido completo (MatchState) desde la App a la BD.
     * @param array $data El JSON decodificado que envió Flutter.
     */
    public function syncMatch(array $data): void {
        // 1. INICIAR TRANSACCIÓN: Todo se guarda o nada se guarda.
        $this->db->begin_transaction();

        try {
            // A. Guardar/Actualizar la cabecera del partido
            $this->upsertMatch($data);
            
            // B. ESTRATEGIA DE LIMPIEZA:
            // Borramos los detalles anteriores para evitar duplicados y 
            // volvemos a insertar el estado actual exacto de la App.
            $this->deleteRelatedData($data['matchId']);
            
            // C. Insertar Logs (Puntos punto a punto)
            if (!empty($data['scoreLog'])) {
                $this->insertScoreLogs($data['matchId'], $data['scoreLog']);
            }
            
            // D. Insertar Resumen por Periodos
            if (!empty($data['periodScores'])) {
                $this->insertPeriodScores($data['matchId'], $data['periodScores']);
            }
            
            // E. Insertar Estadísticas de Jugadores
            if (!empty($data['playerStats'])) {
                $this->insertPlayerStats($data['matchId'], $data['playerStats']);
            }

            // 2. CONFIRMAR TRANSACCIÓN (Guardar cambios permanentemente)
            $this->db->commit();
        } catch (Exception $e) {
            // 3. REVERTIR TRANSACCIÓN (Si algo falla, la BD queda intacta como estaba antes)
            $this->db->rollback();
            throw $e; // Re-lanzamos el error para que el Controller responda con error 500
        }
    }

    // --- Métodos Privados (Helpers SQL) ---

    private function upsertMatch(array $data): void {
        // INSERT ... ON DUPLICATE KEY UPDATE: Si el ID existe, actualiza; si no, inserta.
        $sql = "INSERT INTO matches (
                    id, tournament_id, venue_id, team_a_id, team_b_id, 
                    team_a_name, team_b_name, score_a, score_b, 
                    current_period, time_left, status, 
                    match_date, main_referee, aux_referee, scorekeeper
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    score_a = VALUES(score_a),
                    score_b = VALUES(score_b),
                    current_period = VALUES(current_period),
                    time_left = VALUES(time_left),
                    status = VALUES(status),
                    updated_at = NOW()";

        // Usamos Prepared Statements para prevenir Inyección SQL
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            "siiiissiiissssss", // Tipos de datos (s=string, i=int)
            $data['matchId'], $data['tournamentId'], $data['venueId'],
            $data['teamAId'], $data['teamBId'], $data['teamAName'], $data['teamBName'],
            $data['scoreA'], $data['scoreB'], $data['currentPeriod'], $data['timeLeft'],
            $data['status'], $data['matchDate'], $data['mainReferee'], $data['auxReferee'],
            $data['scorekeeper']
        );
        $stmt->execute();
    }

    private function deleteRelatedData(string $matchId): void {
        $id = $this->db->real_escape_string($matchId);
        // Borramos hijos en orden inverso
        $this->db->query("DELETE FROM score_logs WHERE match_id = '$id'");
        $this->db->query("DELETE FROM period_scores WHERE match_id = '$id'");
        $this->db->query("DELETE FROM match_player_stats WHERE match_id = '$id'");
    }

    private function insertScoreLogs(string $matchId, array $logs): void {
        $stmt = $this->db->prepare("INSERT INTO score_logs (match_id, period, team_id, team_side, player_id, player_name, player_number, points_scored, score_after) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($logs as $log) {
            $stmt->bind_param(
                "siisssiii",
                $matchId, $log['period'], $log['teamId'], $log['teamSide'],
                $log['playerId'], $log['playerName'], $log['playerNumber'],
                $log['points'], $log['scoreAfter']
            );
            $stmt->execute();
        }
    }

    private function insertPeriodScores(string $matchId, array $periodScores): void {
        $stmt = $this->db->prepare("INSERT INTO period_scores (match_id, period_number, score_a, score_b) VALUES (?, ?, ?, ?)");
        
        foreach ($periodScores as $periodNum => $scores) {
            // $scores viene como array simple [puntosA, puntosB]
            $stmt->bind_param("siii", $matchId, $periodNum, $scores[0], $scores[1]);
            $stmt->execute();
        }
    }

    private function insertPlayerStats(string $matchId, array $stats): void {
        $stmt = $this->db->prepare("INSERT INTO match_player_stats (match_id, team_id, player_id, player_name, player_number, team_side, total_points, total_fouls) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($stats as $stat) {
            $stmt->bind_param(
                "siissiii",
                $matchId, $stat['teamId'], $stat['playerId'],
                $stat['name'], $stat['number'], $stat['teamSide'],
                $stat['points'], $stat['fouls']
            );
            $stmt->execute();
        }
    }
}
?>