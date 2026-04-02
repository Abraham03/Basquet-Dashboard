<?php
class TeamRepository {
    private mysqli $db;

    public function __construct(Database $db) {
        $this->db = $db->getConnection();
    }

    // Validar si el nombre del equipo ya existe EN ESE TORNEO ESPECÍFICO
    public function teamNameExistsInTournament(string $name, int $tournamentId, ?int $excludeId = null): bool {
        // Hacemos un JOIN para buscar solo dentro de los equipos vinculados al torneo actual
        $query = "SELECT t.id FROM teams t 
                  INNER JOIN tournament_teams tt ON t.id = tt.team_id 
                  WHERE t.name = ? AND tt.tournament_id = ?";
        
        // Si estamos actualizando, excluimos el ID actual para no chocar con nosotros mismos
        if ($excludeId !== null) {
            $query .= " AND t.id != ?";
        }
        
        $stmt = $this->db->prepare($query);
        
        if ($excludeId !== null) {
            // 'sii' = String, Integer, Integer
            $stmt->bind_param("sii", $name, $tournamentId, $excludeId);
        } else {
            // 'si' = String, Integer
            $stmt->bind_param("si", $name, $tournamentId);
        }
        
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();
        
        return $exists;
    }

    // Obtener la URL del logo actual para poder borrarlo ---
    public function getLogoUrl(int $id): ?string {
        $stmt = $this->db->prepare("SELECT logo_url FROM teams WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            return $row['logo_url'];
        }
        return null;
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
        Logger::write("TeamRepository: Intentando eliminar equipo ID: $id");
        $stmt = $this->db->prepare("DELETE FROM teams WHERE id = ?");
        
        if (!$stmt) {
            throw new Exception("Error DB: " . $this->db->error);
        }
        
        $stmt->bind_param("i", $id);
        
        try {
            return $stmt->execute();
        } catch (mysqli_sql_exception $e) {
            // El código 1451 indica que falló la restricción de llave foránea (Tiene partidos o jugadores amarrados)
            if ($e->getCode() == 1451) {
                throw new Exception("No puedes eliminar este equipo permanentemente porque ya tiene partidos programados o finalizados. Para ocultarlo del torneo actual, filtra por el torneo y usa la opción 'Quitar'.");
            }
            // Si es otro tipo de error SQL, lo lanza genéricamente
            throw new Exception("Error de base de datos: " . $e->getMessage());
        }
    }
    
    // Obtener los partidos de un equipo en un torneo que tengan acta PDF
    public function getTeamMatchesWithReports(int $teamId, int $tournamentId): array {
        $sql = "SELECT id, team_a_name, team_b_name, match_date, pdf_url
                FROM matches
                WHERE tournament_id = ? 
                AND (team_a_id = ? OR team_b_id = ?) 
                AND pdf_url IS NOT NULL AND pdf_url != ''
                ORDER BY match_date ASC";
                
        $stmt = $this->db->prepare($sql);
        if (!$stmt) throw new Exception("Error DB: " . $this->db->error);
        
        $stmt->bind_param("iii", $tournamentId, $teamId, $teamId);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?>