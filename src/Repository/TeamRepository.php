<?php
class TeamRepository {
    private mysqli $db;

    public function __construct(Database $db) {
        $this->db = $db->getConnection();
    }

    public function createTeam(string $name, string $shortName, string $coach, ?string $logoUrl): int {
        Logger::write("TeamRepository: Creando equipo $name");
        
        $sql = "INSERT INTO teams (name, short_name, coach_name, logo_url) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Error DB: " . $this->db->error);
        }
        
        $stmt->bind_param("ssss", $name, $shortName, $coach, $logoUrl);
        $stmt->execute();
        
        return $this->db->insert_id; 
    }

    public function attachTeamToTournament(int $teamId, int $tournamentId): void {
        Logger::write("TeamRepository: Vinculando Equipo $teamId al Torneo $tournamentId");
        
        // Evitar duplicados (Ignora si ya existe la relación)
        $stmt = $this->db->prepare("INSERT IGNORE INTO tournament_teams (tournament_id, team_id) VALUES (?, ?)");
        if ($stmt) {
            $stmt->bind_param("ii", $tournamentId, $teamId);
            $stmt->execute();
        }
    }
    
    public function update(int $id, string $name, string $shortName, string $coach, ?string $logoUrl): bool {
        Logger::write("TeamRepository: Actualizando equipo ID: $id");

        // Solución al BUG: Si $logoUrl es null, NO sobreescribimos la columna en SQL
        if ($logoUrl !== null) {
            $stmt = $this->db->prepare("UPDATE teams SET name = ?, short_name = ?, coach_name = ?, logo_url = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $name, $shortName, $coach, $logoUrl, $id);
        } else {
            $stmt = $this->db->prepare("UPDATE teams SET name = ?, short_name = ?, coach_name = ? WHERE id = ?");
            $stmt->bind_param("sssi", $name, $shortName, $coach, $id);
        }

        if (!$stmt) {
            throw new Exception("Error DB: " . $this->db->error);
        }

        return $stmt->execute();
    }

    public function updateTeamTournament(int $teamId, int $tournamentId): void {
        $this->attachTeamToTournament($teamId, $tournamentId);
    }
    
    public function detachTeamFromTournament(int $teamId, int $tournamentId): bool {
        $stmt = $this->db->prepare("DELETE FROM tournament_teams WHERE team_id = ? AND tournament_id = ?");
        if (!$stmt) throw new Exception("Error DB: " . $this->db->error);
        
        $stmt->bind_param("ii", $teamId, $tournamentId);
        return $stmt->execute();
    }
    
    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM teams WHERE id = ?");
        if (!$stmt) throw new Exception("Error DB: " . $this->db->error);
        
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
?>