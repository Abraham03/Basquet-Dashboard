<?php

class TournamentRepository {
    private mysqli $db;

    public function __construct(Database $db) {
        $this->db = $db -> getConnection();
    }
    
public function createTournament($name, $category) {
             Logger::write("TournamentRepository: Cruando nuevo torneo");

    $stmt = $this->db->prepare("INSERT INTO tournaments (name, category) VALUES (?, ?)");
    return $stmt->execute([$name, $category]);
}

public function update($id, $name, $category) {
        $stmt = $this->db->prepare("UPDATE tournaments SET name = ?, category = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $category, $id);
        return $stmt->execute();
    }

public function delete($id) {
    $stmt = $this->db->prepare("DELETE FROM tournaments WHERE id = ?");
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}
    
}
?>