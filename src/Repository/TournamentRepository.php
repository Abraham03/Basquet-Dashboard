<?php

class TournamentRepository {
    private mysqli $db;

    public function __construct(Database $db) {
        $this->db = $db -> getConnection();
    }
    
    public function getTournamentData($id) {
        $stmt = $this->db->prepare("SELECT * FROM tournaments WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    public function createTournament($name, $category) {
                 Logger::write("TournamentRepository: Cruando nuevo torneo");
    
        $stmt = $this->db->prepare("INSERT INTO tournaments (name, category) VALUES (?, ?)");
        return $stmt->execute([$name, $category]);
    }
    
    public function update($id, $name, $category) {
            $stmt = $this->db->prepare("UPDATE tournaments SET name = ?, category = ? WHERE id = ?");
            $stmt->bind_param("ssi", $name, $category, $id);
            return $stmt->execute();
        }
    
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM tournaments WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function getFixtureData($tournamentId) {
            // 1. Obtener nombre torneo
            $tObj = $this->getTournamentData($tournamentId);
            $tName = $tObj['name'] ?? 'Torneo';
        
            // 2. Obtener partidos (AGREGAMOS logo_url)
            $sql = "SELECT f.*, 
                    r.name as round_name, 
                    ta.name as team_a, ta.logo_url as logo_a,
                    tb.name as team_b, tb.logo_url as logo_b,
                    v.name as venue_name
                    FROM fixtures f
                    JOIN tournament_rounds r ON f.round_id = r.id
                    JOIN teams ta ON f.team_a_id = ta.id
                    JOIN teams tb ON f.team_b_id = tb.id
                    LEFT JOIN venues v ON f.venue_id = v.id
                    WHERE f.tournament_id = ?
                    ORDER BY r.round_order ASC, f.id ASC";
                    
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $tournamentId);
            $stmt->execute();
            $res = $stmt->get_result();
            
            $rounds = [];
            while($row = $res->fetch_assoc()) {
                $rounds[$row['round_name']][] = $row;
            }
            
            return ['tournament_name' => $tName, 'rounds' => $rounds];
        }
        
        public function updateFixtureMatch($matchId, $datetime, $venueId, $status) {
        $stmt = $this->db->prepare("UPDATE fixtures SET scheduled_datetime = ?, venue_id = ?, status = ? WHERE id = ?");
        // 'sisi' -> string, integer (o null), string, integer
        $stmt->bind_param("sisi", $datetime, $venueId, $status, $matchId);
        $stmt->execute();
        $stmt->close();
    }

    public function saveRules($tournamentId, $rules) {
         Logger::write("TournamentRepository: Guardando reglas");
            $stmt = $this->db->prepare("INSERT INTO tournament_rules 
                (tournament_id, matchups_per_pair, points_win, points_draw, points_loss, points_forfeit_win, points_forfeit_loss)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                matchups_per_pair = VALUES(matchups_per_pair),
                points_win = VALUES(points_win),
                points_draw = VALUES(points_draw),
                points_loss = VALUES(points_loss)");
            
            $stmt->bind_param("iiiiiii", 
                $tournamentId, 
                $rules['matchups_per_pair'], 
                $rules['points_win'], 
                $rules['points_draw'], 
                $rules['points_loss'],
                $rules['points_forfeit_win'],
                $rules['points_forfeit_loss']
            );
            $stmt->execute();
            $stmt->close();
        }

    // 2. Crear Jornada (Round)
    public function createRound($tournamentId, $name, $order, $date = null) {
        $stmt = $this->db->prepare("INSERT INTO tournament_rounds (tournament_id, name, round_order, start_date_estimated) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isis", $tournamentId, $name, $order, $date);
        $stmt->execute();
        $roundId = $stmt->insert_id;
        $stmt->close();
        return $roundId;
    }

    // 3. Crear Fixture (El partido planeado)
    public function createFixture($tournamentId, $roundId, $teamA, $teamB) {
        $stmt = $this->db->prepare("INSERT INTO fixtures (tournament_id, round_id, team_a_id, team_b_id, status) VALUES (?, ?, ?, ?, 'SCHEDULED')");
        $stmt->bind_param("iiii", $tournamentId, $roundId, $teamA, $teamB);
        $stmt->execute();
        $stmt->close();
    }

    // 4. Limpiar fixture previo (por si se regenera)
    public function clearFixture($tournamentId) {
        // Al borrar el torneo se borra en cascada, pero si regeneramos, limpiamos manual
        $this->db->query("DELETE FROM tournament_rounds WHERE tournament_id = $tournamentId"); 
        // Fixtures se borran solos por la FK en cascada con rounds, o puedes borrarlos explícitamente.
    }
    
    // 5. Obtener IDs de equipos del torneo
    public function getTeamIds($tournamentId) {
        $stmt = $this->db->prepare("SELECT team_id FROM tournament_teams WHERE tournament_id = ?");
        $stmt->bind_param("i", $tournamentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $ids = [];
        while ($row = $result->fetch_assoc()) {
            $ids[] = $row['team_id'];
        }
        return $ids;
    }
    
}
?>