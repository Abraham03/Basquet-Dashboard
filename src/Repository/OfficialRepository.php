<?php
class OfficialRepository {
    private mysqli $db;

    public function __construct(Database $db) {
        $this->db = $db->getConnection();
    }

    public function getAllActive(): array {
        Logger::write("OfficialRepository: Obteniendo oficiales activos");
        return $this->fetchAll("SELECT id, name, role, signature_data FROM officials WHERE active = 1 ORDER BY role ASC, name ASC");
    }

    public function create(string $name, string $role, ?string $signature = null): int {
        Logger::write("OfficialRepository: Creando oficial -> $name ($role)");
        $stmt = $this->db->prepare("INSERT INTO officials (name, role, signature_data, active) VALUES (?, ?, ?, 1)");
        
        if (!$stmt) throw new Exception("Error DB: " . $this->db->error);
        
        $stmt->bind_param("sss", $name, $role, $signature);
        $stmt->execute();
        return $this->db->insert_id;
    }
    
    public function update(int $id, string $name, string $role, ?string $signature = null): bool {
    Logger::write("OfficialRepository: Actualizando oficial ID -> $id");
    
    if ($signature === null || empty($signature)) {
        // Solo actualizamos nombre y rol
        $stmt = $this->db->prepare("UPDATE officials SET name = ?, role = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $role, $id);
    } else {
        // Incluimos signature_data en la actualización
        $stmt = $this->db->prepare("UPDATE officials SET name = ?, role = ?, signature_data = ? WHERE id = ?");
        $stmt->bind_param("sssi", $name, $role, $signature, $id);
    }
    
    if (!$stmt) throw new Exception("Error DB: " . $this->db->error);
    return $stmt->execute();
}

    public function delete(int $id): bool {
        Logger::write("OfficialRepository: Desactivando oficial ID -> $id");
        $stmt = $this->db->prepare("UPDATE officials SET active = 0 WHERE id = ?");
        
        if (!$stmt) throw new Exception("Error DB: " . $this->db->error);
        
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    private function fetchAll(string $query): array {
        $result = $this->db->query($query);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}
?>