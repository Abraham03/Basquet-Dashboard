<?php
/**
 * Clase CatalogRepository
 * Responsabilidad: Consultar datos maestros (lectura) de la base de datos.
 * Principio: Separation of Concerns (Separa SQL de la lógica de control).
 */
class CatalogRepository {
    private mysqli $db;

    public function __construct(Database $db) {
        $this->db = $db -> getConnection();
    }

    /**
     * Obtiene todos los catálogos necesarios para inicializar la App.
     * @return array Array con torneos, sedes, equipos y jugadores.
     */
    public function getAllCatalogs(): array {
        return [
            // Solo torneos activos
            'tournaments' => $this->fetchAll("SELECT * FROM tournaments WHERE status='ACTIVE'"),
            'venues'      => $this->fetchAll("SELECT * FROM venues"),
            'teams'       => $this->fetchAll("SELECT * FROM teams"),
            // Solo jugadores activos para que aparezcan en el roster
            'players'     => $this->fetchAll("SELECT * FROM players WHERE active=1"),
            'tournament_teams' => $this->fetchAll("SELECT tournament_id, team_id FROM tournament_teams")
        ];
    }

    /**
     * Helper privado para ejecutar un SELECT y devolver un array asociativo.
     */
    private function fetchAll(string $query): array {
        $result = $this->db->query($query);
        // fetch_all(MYSQLI_ASSOC) devuelve un array limpio de PHP
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    
    
public function createTournament($name, $category) {
    $stmt = $this->db->prepare("INSERT INTO tournaments (name, category) VALUES (?, ?)");
    return $stmt->execute([$name, $category]);
}

// Asegúrate de actualizar createTeam para recibir tournament_id si tu BD lo soporta
public function createTeam($name, $shortName, $coach) {
    // Asumiendo que agregaste la columna tournament_id a la tabla teams
    $sql = "INSERT INTO teams (name, short_name, coach_name) VALUES (?, ?, ?)";
    $stmt = $this->db->prepare($sql);
    if ($stmt->execute([$name, $shortName, $coach])) {
            // CORRECCIÓN: Devolver el ID generado, no el resultado de execute()
            return $stmt->insert_id; 
        }
        return 0; // O lanzar excepción si falló
}

    public function createPlayer(int $teamId, string $name, int $number): int {
        $stmt = $this->db->prepare("INSERT INTO players (team_id, name, default_number, active) VALUES (?, ?, ?, 1)");
        $stmt->bind_param("isi", $teamId, $name, $number);
        $stmt->execute();
        return $stmt->insert_id; // Devuelve el ID del nuevo jugador
    }
    
    /**
     * Obtiene datos específicos de un torneo seleccionado.
     * Filtra equipos y jugadores usando la tabla intermedia tournament_teams.
     */
    public function getTournamentData(int $tournamentId): array {
        return [
            // 1. Partidos solo de este torneo
            'matches' => $this->fetchAll(
                "SELECT * FROM matches WHERE tournament_id = $tournamentId"
            ),
            
            // 2. Equipos INSCRITOS en este torneo (Usando la tabla pivot)
            'teams' => $this->fetchAll(
                "SELECT t.* FROM teams t
                 INNER JOIN tournament_teams tt ON t.id = tt.team_id
                 WHERE tt.tournament_id = $tournamentId"
            ),
            
            // 3. Jugadores de los equipos de este torneo
            'players' => $this->fetchAll(
                "SELECT p.* FROM players p
                 INNER JOIN tournament_teams tt ON p.team_id = tt.team_id
                 WHERE tt.tournament_id = $tournamentId AND p.active = 1"
            ),
            
            // 4. venues globales
            "venues" => $this->fetchAll("SELECT * FROM venues"),
            
        ];
    }
    
    /**
     * Método auxiliar para registrar un equipo en un torneo (Llamar al crear equipo)
     */
    public function attachTeamToTournament(int $teamId, int $tournamentId): void {
        $stmt = $this->db->prepare("INSERT INTO tournament_teams (tournament_id, team_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $tournamentId, $teamId);
        $stmt->execute();
    }
    
}
?>