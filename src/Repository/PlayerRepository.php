<?php
class PlayerRepository {
    private mysqli $db;

    public function __construct(Database $db) {
        $this->db = $db->getConnection();
    }

    public function createPlayer(int $teamId, string $name, int $number, ?string $photoUrl): int {
        Logger::write("PlayerRepository: Insertando nuevo jugador -> $name");
        
        $stmt = $this->db->prepare("INSERT INTO players (team_id, name, default_number, photo_url, active) VALUES (?, ?, ?, ?, 1)");
        
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta: " . $this->db->error);
        }

        $stmt->bind_param("isis", $teamId, $name, $number, $photoUrl);
        $stmt->execute();
        
        return $stmt->insert_id; // Devuelve el ID del nuevo jugador
    }
    
    public function update(int $id, int $teamId, string $name, int $number, ?string $photoUrl): bool {
        Logger::write("PlayerRepository: Actualizando jugador ID: $id");
        
        // Si hay foto nueva, actualizamos todo. Si no, ignoramos la columna photo_url
        if ($photoUrl !== null) {
            $stmt = $this->db->prepare("UPDATE players SET team_id = ?, name = ?, default_number = ?, photo_url = ? WHERE id = ?");
            $stmt->bind_param("isisi", $teamId, $name, $number, $photoUrl, $id);
        } else {
            $stmt = $this->db->prepare("UPDATE players SET team_id = ?, name = ?, default_number = ? WHERE id = ?");
            $stmt->bind_param("isii", $teamId, $name, $number, $id);
        }
        
        if (!$stmt) throw new Exception("Error al preparar la consulta: " . $this->db->error);

        return $stmt->execute();
    }
    
    public function delete(int $id): bool {
        Logger::write("PlayerRepository: Eliminando jugador ID: $id");
        
        $stmt = $this->db->prepare("DELETE FROM players WHERE id = ?");
        
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta: " . $this->db->error);
        }

        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
?>