<?php

class TeamRepository {
    private mysqli $db;

    public function __construct(Database $db) {
        $this->db = $db -> getConnection();
    }



    public function createTeam($name, $shortName, $coach, $logoUrl) {
    Logger::write("TeamRepository: Creando equipo $name");
    $sql = "INSERT INTO teams (name, short_name, coach_name, logo_url) VALUES (?, ?, ?, ?)";
    $stmt = $this->db->prepare($sql);
    
    // Usar bind_param es más seguro para manejar tipos
    $stmt->bind_param("ssss", $name, $shortName, $coach, $logoUrl);
    
    if ($stmt->execute()) {
        Logger::write("TeamRepository: Creado con éxito equipo $name");
        return $this->db->insert_id; 
    }
    return 0;
    }



    public function attachTeamToTournament(int $teamId, int $tournamentId): void {
        Logger::write("TeamRepository: Vinculando Equipo $teamId al Torneo $tournamentId");
        $stmt = $this->db->prepare("INSERT INTO tournament_teams (tournament_id, team_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $tournamentId, $teamId);
        $stmt->execute();
    }
    
    
    // --- NUEVO: UPDATE ---
    public function update($id, $name, $shortName, $coach, $logoUrl) {
        $stmt = $this->db->prepare("UPDATE teams SET name = ?, short_name = ?, coach_name = ?, logo_url = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $name, $shortName, $coach, $logoUrl , $id);
        return $stmt->execute();
    }

    // --- ACTUALIZAR TORNEO DEL EQUIPO ---
    public function updateTeamTournament($teamId, $tournamentId) {
        // Creamos la nueva
        $this->attachTeamToTournament($teamId, $tournamentId);
    }
    
    public function detachTeamFromTournament($teamId, $tournamentId) {
    $stmt = $this->db->prepare("DELETE FROM tournament_teams WHERE team_id = ? AND tournament_id = ?");
    $stmt->bind_param("ii", $teamId, $tournamentId);
    return $stmt->execute();
}

    
    public function delete($id) {
    $stmt = $this->db->prepare("DELETE FROM teams WHERE id = ?");
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}
    
}
?>