<?php
class TournamentRepository {
    private mysqli $db;

    public function __construct(Database $db) {
        $this->db = $db->getConnection();
    }
    
    public function getTournamentData(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM tournaments WHERE id = ?");
        if (!$stmt) throw new Exception("Error DB: " . $this->db->error);
        
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result ?: null;
    }
    
    public function createTournament(string $name, string $category): int {
        Logger::write("TournamentRepository: Creando nuevo torneo $name");
        
        $stmt = $this->db->prepare("INSERT INTO tournaments (name, category) VALUES (?, ?)");
        if (!$stmt) throw new Exception("Error DB: " . $this->db->error);
        
        // BUG SOLUCIONADO: Se cambió execute([array]) por bind_param()
        $stmt->bind_param("ss", $name, $category);
        $stmt->execute();
        
        return $this->db->insert_id;
    }
    
    public function update(int $id, string $name, string $category): bool {
        $stmt = $this->db->prepare("UPDATE tournaments SET name = ?, category = ? WHERE id = ?");
        if (!$stmt) throw new Exception("Error DB: " . $this->db->error);
        
        $stmt->bind_param("ssi", $name, $category, $id);
        return $stmt->execute();
    }
    
    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM tournaments WHERE id = ?");
        if (!$stmt) throw new Exception("Error DB: " . $this->db->error);
        
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function getFixtureData(int $tournamentId): array {
        $tObj = $this->getTournamentData($tournamentId);
        $tName = $tObj['name'] ?? 'Torneo';
    
        $sql = "SELECT f.*, 
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
                WHERE f.tournament_id = ?
                ORDER BY r.round_order ASC, f.id ASC";
                
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            Logger::write("Error prepare getFixtureData: " . $this->db->error);
            throw new Exception("Error en la base de datos al cargar el fixture.");
        }

        $stmt->bind_param("i", $tournamentId);
        $stmt->execute();
        $res = $stmt->get_result();
        
        $rounds = [];
        while($row = $res->fetch_assoc()) {
            if (!empty($row['match_status'])) {
                $row['status'] = $row['match_status'];
            }
            $rounds[$row['round_name']][] = $row;
        }
        
        return ['tournament_name' => $tName, 'rounds' => $rounds];
    }
        
    public function updateFixtureMatch(int $matchId, ?string $datetime, ?int $venueId, string $status): void {
        $stmt = $this->db->prepare("UPDATE fixtures SET scheduled_datetime = ?, venue_id = ?, status = ? WHERE id = ?");
        if (!$stmt) throw new Exception("Error DB: " . $this->db->error);
        
        // Uso de variables referenciadas seguras incluso con NULL
        $stmt->bind_param("sisi", $datetime, $venueId, $status, $matchId);
        $stmt->execute();
    }

    public function saveRules(int $tournamentId, array $rules): void {
        Logger::write("TournamentRepository: Guardando reglas para torneo $tournamentId");
        $stmt = $this->db->prepare("INSERT INTO tournament_rules 
            (tournament_id, matchups_per_pair, points_win, points_draw, points_loss, points_forfeit_win, points_forfeit_loss)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            matchups_per_pair = VALUES(matchups_per_pair),
            points_win = VALUES(points_win),
            points_draw = VALUES(points_draw),
            points_loss = VALUES(points_loss)");
            
        if (!$stmt) throw new Exception("Error DB: " . $this->db->error);
        
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
    }

    public function createRound(int $tournamentId, string $name, int $order, ?string $date = null): int {
        $stmt = $this->db->prepare("INSERT INTO tournament_rounds (tournament_id, name, round_order, start_date_estimated) VALUES (?, ?, ?, ?)");
        if (!$stmt) throw new Exception("Error DB: " . $this->db->error);
        
        $stmt->bind_param("isis", $tournamentId, $name, $order, $date);
        $stmt->execute();
        return $stmt->insert_id;
    }

    public function createFixture(int $tournamentId, int $roundId, int $teamA, int $teamB): void {
        $stmt = $this->db->prepare("INSERT INTO fixtures (tournament_id, round_id, team_a_id, team_b_id, status) VALUES (?, ?, ?, ?, 'SCHEDULED')");
        if (!$stmt) throw new Exception("Error DB: " . $this->db->error);
        
        $stmt->bind_param("iiii", $tournamentId, $roundId, $teamA, $teamB);
        $stmt->execute();
    }

    public function clearFixture(int $tournamentId): void {
        // VULNERABILIDAD SOLUCIONADA: Se cambió interpolación directa por statement preparado
        $stmt = $this->db->prepare("DELETE FROM tournament_rounds WHERE tournament_id = ?");
        if (!$stmt) throw new Exception("Error DB: " . $this->db->error);
        
        $stmt->bind_param("i", $tournamentId);
        $stmt->execute();
    }
    
    public function getTeamIds(int $tournamentId): array {
        $stmt = $this->db->prepare("SELECT team_id FROM tournament_teams WHERE tournament_id = ?");
        if (!$stmt) throw new Exception("Error DB: " . $this->db->error);
        
        $stmt->bind_param("i", $tournamentId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $ids = [];
        while ($row = $result->fetch_assoc()) {
            $ids[] = (int)$row['team_id'];
        }
        return $ids;
    }
}
?>