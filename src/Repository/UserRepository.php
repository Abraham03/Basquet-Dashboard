<?php
class UserRepository {
    private $db;

    public function __construct(Database $db) {
        $this->db = $db->getConnection();
    }

    public function getAll() {
        // Hacemos JOIN para traer el nombre del equipo asignado
        $sql = "SELECT u.id, u.username, u.role, u.team_id, t.name as team_name 
                FROM admins u 
                LEFT JOIN teams t ON u.team_id = t.id 
                ORDER BY u.id DESC";
        $result = $this->db->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function create($user, $pass, $role, $teamId) {
        $stmt = $this->db->prepare("INSERT INTO admins (username, password, role, team_id, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssi", $user, $pass, $role, $teamId);
        if (!$stmt->execute()) throw new Exception($this->db->error);
    }

    public function update($id, $user, $pass, $role, $teamId) {
        if ($pass) {
            // Si mandó password, lo actualizamos
            $stmt = $this->db->prepare("UPDATE admins SET username=?, password=?, role=?, team_id=? WHERE id=?");
            $stmt->bind_param("sssii", $user, $pass, $role, $teamId, $id);
        } else {
            // Si NO mandó password, conservamos el actual
            $stmt = $this->db->prepare("UPDATE admins SET username=?, role=?, team_id=? WHERE id=?");
            $stmt->bind_param("ssii", $user, $role, $teamId, $id);
        }
        if (!$stmt->execute()) throw new Exception($this->db->error);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM admins WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }
}
?>