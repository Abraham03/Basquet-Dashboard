<?php

class TeamRepository {
    private mysqli $db;

    public function __construct(Database $db) {
        $this->db = $db -> getConnection();
    }



// Asegúrate de actualizar createTeam para recibir tournament_id si tu BD lo soporta
public function createTeam($name, $shortName, $coach, $logoUrl) {
    Logger::write("TeamRepository: Crando equipo $name");

    // Asumiendo que agregaste la columna tournament_id a la tabla teams
    $sql = "INSERT INTO teams (name, short_name, coach_name, logo_url) VALUES (?, ?, ?, ?)";
    $stmt = $this->db->prepare($sql);
    if ($stmt->execute([$name, $shortName, $coach, $logoUrl])) {
         Logger::write("TeamRepository: Crado con exito equipo $name");
            // CORRECCIÓN: Devolver el ID generado, no el resultado de execute()
            return $stmt->insert_id; 
        }
        return 0; // O lanzar excepción si falló
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