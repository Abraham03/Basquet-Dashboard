<?php
class MatchRepository {
    private $db;

    public function __construct(Database $db) {
        $this->db = $db->getConnection();
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    }

    public function syncMatch(array $data): array {
        Logger::write("MatchRepository: Iniciando SyncMatch ID: " . $data['match_id']);
        
        $currentTime = method_exists('Database', 'now') ? Database::now() : date('Y-m-d H:i:s');
        
        if (!$this->db) {
            throw new Exception("La conexión a la base de datos es nula.");
        }

        try {
            $this->db->begin_transaction();

            // 1. Guardar el ID tal cual llega de Flutter (sea positivo o negativo)
            $id = (string)$data['match_id'];

            // ---------------------------------------------------------
            // Insertar/Actualizar Partido (Cabecera)
            // ---------------------------------------------------------
            $sqlMatch = "INSERT INTO matches 
                (id, tournament_id, venue_id, team_a_id, team_b_id, team_a_name, team_b_name, 
                 score_a, score_b, current_period, time_left, status, match_date, 
                 main_referee, aux_referee, scorekeeper, signature_data, updated_at, pdf_url)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'FINISHED', ?, ?, ?, ?, ?, ?, ?)
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
                updated_at = VALUES(updated_at),
                pdf_url = COALESCE(VALUES(pdf_url), pdf_url)";

            $stmt = $this->db->prepare($sqlMatch);
            
            $tourn = !empty($data['tournament_id']) ? (int)$data['tournament_id'] : null;
            $venue = !empty($data['venue_id']) ? (int)$data['venue_id'] : null;
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
            $pdfUrl = $data['pdf_url'] ?? null;
            
            // Tipos: s i i i i s s i i i s s s s s s s s (Total 18)
            $stmt->bind_param("siiiissiiissssssss", 
                $id, $tourn, $venue, $ta_id, $tb_id, 
                $ta_name, $tb_name, $sa, $sb, $period, $time, $date,
                $ref1, $ref2, $scorek, $sig,
                $currentTime, $pdfUrl
            );

            $stmt->execute();
            $stmt->close(); 

            // ---------------------------------------------------------
            // 2. Limpiar y Reemplazar Eventos
            // ---------------------------------------------------------
            $delStmt = $this->db->prepare("DELETE FROM score_logs WHERE match_id = ?");
            $delStmt->bind_param("s", $id);
            $delStmt->execute();
            $delStmt->close();

            if (!empty($data['events']) && is_array($data['events'])) {
                $sqlEvent = "INSERT INTO score_logs 
                            (match_id, period, team_id, team_side, player_id, player_name, player_number, points_scored, score_after, created_at) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmtEvent = $this->db->prepare($sqlEvent);
                
                foreach ($data['events'] as $event) {
                    $per = (int)$event['period'];
                    $side = $event['team_side']; 
                    $realTeamId = ($side === 'A') ? $ta_id : $tb_id;
                    $pId = !empty($event['player_id']) ? (int)$event['player_id'] : null;
                    $pName = isset($event['player_name']) ? mb_strtoupper($event['player_name'], 'UTF-8') : '';
                    $pNum = (int)$event['player_number'];
                    $pts = (int)$event['points_scored'];
                    $scoreAfter = (int)$event['score_after'];

                    $stmtEvent->bind_param("siisisiiis", 
                        $id, $per, $realTeamId, $side, 
                        $pId, $pName, $pNum, $pts, $scoreAfter, $currentTime
                    );
                    $stmtEvent->execute();
                }
                $stmtEvent->close();
            }
            
            // ---------------------------------------------------------
            // 3. Limpiar y Reemplazar Rosters (Alineaciones / Asistencia)
            // ---------------------------------------------------------
            if (isset($data['rosters']) && is_array($data['rosters'])) {
                $delRoster = $this->db->prepare("DELETE FROM match_rosters WHERE match_id = ?");
                $delRoster->bind_param("s", $id);
                $delRoster->execute();
                $delRoster->close();

                $sqlRoster = "INSERT INTO match_rosters (match_id, player_id, team_side, jersey_number, is_captain) VALUES (?, ?, ?, ?, ?)";
                $stmtRoster = $this->db->prepare($sqlRoster);

                foreach ($data['rosters'] as $roster) {
                    $pId = (int)$roster['player_id'];
                    $side = (string)$roster['team_side']; // 'A' o 'B'
                    $jersey = (int)$roster['jersey_number'];
                    $isCap = (int)$roster['is_captain'];

                    // Tipos: s (string), i (int), s (string), i (int), i (int)
                    $stmtRoster->bind_param("sisii", $id, $pId, $side, $jersey, $isCap);
                    $stmtRoster->execute();
                }
                $stmtRoster->close();
            }

            // ---------------------------------------------------------
            // 4. Vincular Fixture si aplica
            // ---------------------------------------------------------
            $fixtureId = $data['fixture_id'] ?? null;
            if (!empty($fixtureId)) {
                $fixStmt = $this->db->prepare("UPDATE fixtures SET status = 'FINISHED', venue_id = ?, match_id = ? WHERE id = ?");
                $fixStmt->bind_param("isi", $venue, $id, $fixtureId);
                $fixStmt->execute();
                $fixStmt->close();
            }

            $this->db->commit();
            Logger::write("MatchRepository: Sincronización exitosa. Real Match ID: $id");

            return ['real_match_id' => $id];

        } catch (\Throwable $e) {
            if ($this->db) { 
                try { $this->db->rollback(); } catch (\Throwable $t) {} 
            }
            Logger::write("MatchRepository ERROR FATAL: " . $e->getMessage() . " | Línea: " . $e->getLine());
            throw new Exception("Fallo en la sincronización de la base de datos.");
        }
    }
}
?>