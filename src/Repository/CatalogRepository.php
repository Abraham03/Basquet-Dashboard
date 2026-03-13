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
    

    public function getSyncData(int $tournamentId): array {
        Logger::write("CatalogRepository: Obteniendo datos de sincronización. Torneo ID: $tournamentId");
        
        $teams = [];
        $players = [];
        $relationships = [];
        $fixtures = [];

        // Si mandamos un ID de torneo mayor a 0, traemos su información pesada
        if ($tournamentId > 0) {
            $teams = $this->fetchAll("
                SELECT t.* FROM teams t 
                INNER JOIN tournament_teams tt ON t.id = tt.team_id 
                WHERE tt.tournament_id = $tournamentId
            ");
            
            $players = $this->fetchAll("
                SELECT 
                    p.*,
                    COALESCE(v.games_played, 0) AS games_played,
                    COALESCE(v.total_games, 0) AS total_games,
                    IF(v.total_games > 0, (v.games_played / v.total_games) * 100, 0) AS attendance_percentage,
                    COALESCE(v.min_attendance_percent, 60) AS min_attendance_percent
                FROM players p 
                INNER JOIN tournament_teams tt ON p.team_id = tt.team_id 
                LEFT JOIN v_player_attendance v ON p.id = v.player_id AND v.tournament_id = $tournamentId
                WHERE tt.tournament_id = $tournamentId AND p.active = 1
            ");
            
            $relationships = $this->fetchAll("
                SELECT tournament_id, team_id FROM tournament_teams WHERE tournament_id = $tournamentId
            ");
            
            $fixtures = $this->fetchAll("
                SELECT f.*, 
                r.name as round_name, 
                ta.name as team_a, ta.logo_url as logo_a,
                tb.name as team_b, tb.logo_url as logo_b,
                v.name as venue_name,
                m.score_a, m.score_b, m.pdf_url, m.status as match_status
                FROM fixtures f
                JOIN tournament_rounds r ON f.round_id = r.id
                JOIN teams ta ON f.team_a_id = ta.id
                JOIN teams tb ON f.team_b_id = tb.id
                LEFT JOIN venues v ON f.venue_id = v.id
                LEFT JOIN matches m ON f.match_id COLLATE utf8mb4_unicode_ci = m.id COLLATE utf8mb4_unicode_ci
                WHERE f.tournament_id = $tournamentId
                ORDER BY r.round_order ASC, f.id ASC
            ");
            // Procesar el status de los partidos terminados
            foreach ($fixtures as &$fixture) {
                if (!empty($fixture['match_status'])) {
                    $fixture['status'] = $fixture['match_status'];
                }
            }
        }

        return [
            // Siempre enviamos torneos y sedes (pesan muy poco)
            'tournaments' => $this->fetchAll("SELECT * FROM tournaments WHERE status='ACTIVE'"),
            'venues'      => $this->fetchAll("SELECT * FROM venues"),
            'teams'       => $teams,
            'players'     => $players,
            'tournament_teams' => $relationships,
            'fixtures'    => $fixtures
        ];
    }

    /**
     * Obtiene todos los catálogos necesarios para inicializar la App.
     * @return array Array con torneos, sedes, equipos y jugadores.
     */
    public function getAllCatalogs(): array {
         Logger::write("CatalogRepository: Obteniendo todos los catálogos");
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
     * Obtiene datos específicos de un torneo seleccionado.
     * Filtra equipos y jugadores usando la tabla intermedia tournament_teams.
     */
    public function getTournamentData(int $tournamentId): array {
        Logger::write("CatalogRepository: Obteniendo datos del torneo ID: $tournamentId");
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
            'players' => $this->fetchAll("
                SELECT 
                    p.*,
                    COALESCE(v.games_played, 0) AS games_played,
                    COALESCE(v.total_games, 0) AS total_games,
                    IF(v.total_games > 0, (v.games_played / v.total_games) * 100, 0) AS attendance_percentage,
                    COALESCE(v.min_attendance_percent, 60) AS min_attendance_percent
                FROM players p
                 INNER JOIN tournament_teams tt ON p.team_id = tt.team_id
                 LEFT JOIN v_player_attendance v ON p.id = v.player_id AND v.tournament_id = $tournamentId
                 WHERE tt.tournament_id = $tournamentId AND p.active = 1"
            ),
            
            // 4. venues globales
            "venues" => $this->fetchAll("SELECT * FROM venues"),
            
        ];
    }
    
    // 1. Solo lista de torneos (Para el select del dashboard)
    public function getTournamentsList(): array {
        return $this->fetchAll("SELECT id, name, category FROM tournaments ORDER BY id DESC");
    }

    // 2. Datos filtrados por torneo (Para las tablas del dashboard)
    public function getDataByTournament($tournamentId): array {
        $sqlTeams = "SELECT t.* FROM teams t";
        $sqlPlayers = "SELECT p.* FROM players p WHERE p.active = 1";

        // Si hay un torneo seleccionado, filtramos
        if ($tournamentId > 0) {
            $sqlTeams = "SELECT t.* FROM teams t 
                         INNER JOIN tournament_teams tt ON t.id = tt.team_id 
                         WHERE tt.tournament_id = $tournamentId";
            
            // Jugadores que pertenecen a equipos de este torneo
            // Nota: Si un jugador está en un equipo, y ese equipo está en el torneo, mostramos al jugador.
            // Opcional: Si quieres ser más estricto, podrías filtrar más, pero esto suele bastar.
            $sqlPlayers = "SELECT p.* FROM players p 
                           INNER JOIN teams t ON p.team_id = t.id 
                           INNER JOIN tournament_teams tt ON t.id = tt.team_id 
                           WHERE tt.tournament_id = $tournamentId AND p.active = 1";
        }

        return [
            'teams' => $this->fetchAll($sqlTeams),
            'players' => $this->fetchAll($sqlPlayers)
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
    
    
}
?>