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
            'players'     => $this->fetchAll("SELECT * FROM players WHERE active=1")
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
    
    
    public function createTeam(string $name, string $shortName, string $coach): int {
        $stmt = $this->db->prepare("INSERT INTO teams (name, short_name, coach_name) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $shortName, $coach);
        $stmt->execute();
        return $stmt->insert_id; // Devuelve el ID del nuevo equipo
    }

    public function createPlayer(int $teamId, string $name, int $number): int {
        $stmt = $this->db->prepare("INSERT INTO players (team_id, name, default_number, active) VALUES (?, ?, ?, 1)");
        $stmt->bind_param("isi", $teamId, $name, $number);
        $stmt->execute();
        return $stmt->insert_id; // Devuelve el ID del nuevo jugador
    }
    
}
?>