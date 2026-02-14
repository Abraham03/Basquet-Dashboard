<?php

class PlayerRepository {
    private mysqli $db;

    public function __construct(Database $db) {
        $this->db = $db -> getConnection();
    }





    public function createPlayer(int $teamId, string $name, int $number): int {
                 Logger::write("PlayerRepository: Insertndo nuevo jugador");
        $stmt = $this->db->prepare("INSERT INTO players (team_id, name, default_number, active) VALUES (?, ?, ?, 1)");
        $stmt->bind_param("isi", $teamId, $name, $number);
        $stmt->execute();
        return $stmt->insert_id; // Devuelve el ID del nuevo jugador
    }
    
    public function update($id, $teamId, $name, $number) {
        $stmt = $this->db->prepare("UPDATE players SET team_id = ?, name = ?, default_number = ? WHERE id = ?");
        $stmt->bind_param("isii", $teamId, $name, $number, $id);
        return $stmt->execute();
    }
    
    public function delete($id) {
    $stmt = $this->db->prepare("DELETE FROM players WHERE id = ?");
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}
    
}
?>