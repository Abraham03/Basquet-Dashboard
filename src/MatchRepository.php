<?php
// src/MatchRepository.php

class MatchRepository {
    private $db;

    public function __construct(Database $db) {
        $this->db = $db->getConnection();
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    }

    public function syncMatch(array $data) {
        $logFile = __DIR__ . '/debug_payload_full.txt';
        
        file_put_contents($logFile, "=== INICIO REQUEST: " . date('Y-m-d H:i:s') . " ===\n");
        file_put_contents($logFile, "DATA COMPLETA:\n" . print_r($data, true) . "\n", FILE_APPEND);

        try {
            if (!$this->db) {
                throw new Exception("La conexión a la base de datos es NULL.");
            }

            $this->db->begin_transaction();

            // ---------------------------------------------------------
            // 1. Insertar/Actualizar Partido
            // ---------------------------------------------------------
            $sqlMatch = "INSERT INTO matches 
                (id, tournament_id, venue_id, team_a_id, team_b_id, team_a_name, team_b_name, 
                 score_a, score_b, current_period, time_left, status, match_date, 
                 main_referee, aux_referee, scorekeeper, signature_data, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'FINISHED', ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                score_a = VALUES(score_a), 
                score_b = VALUES(score_b), 
                current_period = VALUES(current_period),
                time_left = VALUES(time_left),
                status = 'FINISHED', 
                match_date = VALUES(match_date),
                team_b_name = VALUES(team_b_name), 
                main_referee = VALUES(main_referee),
                aux_referee = VALUES(aux_referee),
                scorekeeper = VALUES(scorekeeper),
                signature_data = VALUES(signature_data), 
                updated_at = NOW()";

            $stmt = $this->db->prepare($sqlMatch);
            
            // Variables partido
            $id = $data['match_id'];
            $tourn = $data['tournament_id'] ?? null;
            $venue = $data['venue_id'] ?? null;
            $ta_id = (int)$data['team_a_id'];
            $tb_id = (int)$data['team_b_id'];
            $ta_name = $data['team_a_name'] ?? '';
            $tb_name = $data['team_b_name'] ?? ''; 
            $sa = (int)$data['score_a'];
            $sb = (int)$data['score_b'];
            $period = (int)($data['current_period'] ?? 1);
            $time = $data['time_left'] ?? '00:00';
            $date = $data['match_date'] ?? date('Y-m-d H:i:s'); 
            $ref1 = $data['main_referee'] ?? '';
            $ref2 = $data['aux_referee'] ?? '';
            $scorek = $data['scorekeeper'] ?? '';
            $sig = !empty($data['signature_base64']) ? $data['signature_base64'] : null;

            // --- CORRECCIÓN AQUÍ ---
            // Tenías 18 caracteres, pero solo hay 16 variables.
            // Cadena correcta (16 chars): s i i i i s s i i i s s s s s s
            $types = "siiiissiiissssss"; 
            
            $stmt->bind_param($types, 
                $id, $tourn, $venue, $ta_id, $tb_id, 
                $ta_name, $tb_name, 
                $sa, $sb, $period, $time, $date,
                $ref1, $ref2, $scorek, $sig
            );

            $stmt->execute();
            $stmt->close(); 
            file_put_contents($logFile, "Match INSERT ejecutado OK.\n", FILE_APPEND);

            // ---------------------------------------------------------
            // 2. Reemplazar Eventos
            // ---------------------------------------------------------
            $delStmt = $this->db->prepare("DELETE FROM score_logs WHERE match_id = ?");
            $delStmt->bind_param("s", $id);
            $delStmt->execute();
            $delStmt->close();

            if (!empty($data['events']) && is_array($data['events'])) {
                $sqlEvent = "INSERT INTO score_logs 
                            (match_id, period, team_id, team_side, player_id, player_name, player_number, points_scored, score_after, created_at) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                
                $stmtEvent = $this->db->prepare($sqlEvent);
                
                if (!$stmtEvent) {
                    throw new Exception("Error prepare Eventos: " . $this->db->error);
                }

                foreach ($data['events'] as $event) {
                    $per = $event['period'];
                    $side = $event['team_side']; 
                    $realTeamId = ($side == 'A') ? $ta_id : $tb_id;
                    
                    // ID numérico (tu log confirma que ya llega bien, ej: 25)
                    $pId = !empty($event['player_id']) ? (int)$event['player_id'] : null;
                    
                    $pName = $event['player_name'] ?? '';
                    $pNum = (int)$event['player_number'];
                    $pts = (int)$event['points_scored'];
                    $scoreAfter = (int)$event['score_after'];

                    // Tipos: s i i s i s i i i
                    $stmtEvent->bind_param("siisisiii", 
                        $id, 
                        $per, 
                        $realTeamId, 
                        $side, 
                        $pId, 
                        $pName, 
                        $pNum, 
                        $pts, 
                        $scoreAfter
                    );
                    $stmtEvent->execute();
                }
                $stmtEvent->close();
                file_put_contents($logFile, "Eventos insertados con PLAYER_ID: " . count($data['events']) . "\n", FILE_APPEND);
            }

            $this->db->commit();
            file_put_contents($logFile, "TRANSACCIÓN EXITOSA.\n", FILE_APPEND);
            
            return ['status' => 'success', 'message' => 'Sincronizado'];

        } catch (\Throwable $e) {
            if ($this->db) { try { $this->db->rollback(); } catch (\Throwable $t) {} }
            
            $errorMsg = "ERROR FATAL: " . $e->getMessage() . "\nLínea: " . $e->getLine();
            file_put_contents($logFile, $errorMsg, FILE_APPEND);
            throw $e;
        }
    }
}
?>