<?php
class PlayerRepository {
    private mysqli $db;

    public function __construct(Database $db) {
        $this->db = $db->getConnection();
    }

    // --- NUEVO: Validar si el número de playera ya existe en ese equipo ---
    public function playerNumberExistsInTeam(int $teamId, int $number, ?int $excludeId = null): bool {
        $query = "SELECT id FROM players WHERE team_id = ? AND default_number = ?";
        
        // Si estamos actualizando, excluimos el ID actual para no chocar con nosotros mismos
        if ($excludeId !== null) {
            $query .= " AND id != ?";
        }
        
        $stmt = $this->db->prepare($query);
        
        if ($excludeId !== null) {
            // "iii" significa que los tres parámetros son Integers (Enteros)
            $stmt->bind_param("iii", $teamId, $number, $excludeId);
        } else {
            // "ii" significa que los dos parámetros son Integers
            $stmt->bind_param("ii", $teamId, $number);
        }
        
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();
        
        return $exists;
    }
    
    // Obtener la URL de la foto actual para poder borrarla ---
    public function getPhotoUrl(int $id): ?string {
        $stmt = $this->db->prepare("SELECT photo_url FROM players WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            return $row['photo_url'];
        }
        return null;
    }

    public function createPlayer(int $teamId, string $name, int $number, ?string $photoUrl): int {
        Logger::write("PlayerRepository: Insertando nuevo jugador -> $name");
        
        $stmt = $this->db->prepare("INSERT INTO players (team_id, name, default_number, photo_url, active) VALUES (?, ?, ?, ?, 1)");
        
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta: " . $this->db->error);
        }

        $stmt->bind_param("isis", $teamId, $name, $number, $photoUrl);
        $stmt->execute();
        
        return $stmt->insert_id;
    }
    
    public function update(int $id, int $teamId, string $name, int $number, ?string $photoUrl): bool {
        Logger::write("PlayerRepository: Actualizando jugador ID: $id");
        
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