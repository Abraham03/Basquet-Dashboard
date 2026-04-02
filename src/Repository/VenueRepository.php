<?php
class VenueRepository {
    private mysqli $db;

    public function __construct(Database $db) {
        $this->db = $db->getConnection();
    }
    
    public function create(string $name, string $address): int {
        Logger::write("VenueRepository: Creando nueva sede $name");
        
        $stmt = $this->db->prepare("INSERT INTO venues (name, address) VALUES (?, ?)");
        if (!$stmt) throw new Exception("Error DB: " . $this->db->error);
        
        $stmt->bind_param("ss", $name, $address);
        $stmt->execute();
        
        return $this->db->insert_id;
    }
    
    public function update(int $id, string $name, string $address): bool {
        $stmt = $this->db->prepare("UPDATE venues SET name = ?, address = ? WHERE id = ?");
        if (!$stmt) throw new Exception("Error DB: " . $this->db->error);
        
        $stmt->bind_param("ssi", $name, $address, $id);
        return $stmt->execute();
    }
    
    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM venues WHERE id = ?");
        if (!$stmt) throw new Exception("Error DB: " . $this->db->error);
        
        $stmt->bind_param("i", $id);
        
        // Si hay partidos asignados a esta sede, MySQL lanzará una excepción
        // que capturaremos en el Controller para dar un mensaje amigable.
        return $stmt->execute();
    }
}
?>