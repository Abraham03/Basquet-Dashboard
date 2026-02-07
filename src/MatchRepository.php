<?php
// src/MatchRepository.php

class MatchRepository {
    private $db;

    public function __construct(Database $db) {
        $this->db = $db->getConnection();
    }

    /**
     * Sincroniza el partido completo de forma atómica.
     */
    public function syncMatch(array $data) {
        try {
            // 1. Iniciar Transacción (ACID)
            $this->db->beginTransaction();

            // 2. Insertar o Actualizar Cabecera del Partido (Upsert)
            // Guardamos signature_base64 directamente en signature_data
            $sqlMatch = "INSERT INTO matches 
                (id, team_a_name, team_b_name, score_a, score_b, status, signature_data, updated_at)
                VALUES (:id, :ta_name, :tb_name, :sa, :sb, 'FINISHED', :sig, NOW())
                ON DUPLICATE KEY UPDATE 
                score_a = VALUES(score_a), 
                score_b = VALUES(score_b), 
                status = 'FINISHED', 
                signature_data = VALUES(signature_data),
                updated_at = NOW()";

            $stmt = $this->db->prepare($sqlMatch);
            $stmt->execute([
                ':id' => $data['match_id'],
                ':ta_name' => $data['team_a_name'],
                ':tb_name' => $data['team_b_name'],
                ':sa' => $data['score_a'],
                ':sb' => $data['score_b'],
                ':sig' => $data['signature_base64'] ?? null // Guardamos el Base64 directo
            ]);

            // 3. Reemplazar Eventos (Log del partido)
            // Primero borramos los anteriores para evitar duplicados si se re-sincroniza
            $delStmt = $this->db->prepare("DELETE FROM score_logs WHERE match_id = :mid");
            $delStmt->execute([':mid' => $data['match_id']]);

            if (!empty($data['events'])) {
                // Usamos prepared statement una vez y lo ejecutamos múltiples veces (Más eficiente)
                $sqlEvent = "INSERT INTO score_logs (match_id, period, team_id, player_number, points, event_type, created_at) 
                             VALUES (:mid, :per, :tid, :pnum, :pts, :type, NOW())";
                $stmtEvent = $this->db->prepare($sqlEvent);

                foreach ($data['events'] as $event) {
                    $stmtEvent->execute([
                        ':mid' => $data['match_id'],
                        ':per' => $event['period'],
                        ':tid' => $event['team_id'], 
                        ':pnum' => $event['player_number'],
                        ':pts' => $event['points'],
                        ':type' => $event['type'] ?? 'POINT'
                    ]);
                }
            }

            // 4. Confirmar cambios
            $this->db->commit();
            return ['success' => true, 'message' => 'Partido sincronizado correctamente'];

        } catch (Exception $e) {
            // Si algo falla, revertimos todo
            $this->db->rollBack();
            error_log("Sync Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error en servidor: ' . $e->getMessage()];
        }
    }
}
?>