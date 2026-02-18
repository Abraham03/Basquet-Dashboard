<?php
class TournamentController {
    private $repo;

    public function __construct() {
        $this->repo = new TournamentRepository(Database::getInstance());
    }

    public function getOne($id) {
        if (!$id) {
            Response::json(['status' => 'error', 'message' => 'Tournament ID required'], 400);
        }
        $data = $this->repo->getTournamentData((int)$id);
        Response::json(['status' => 'success', 'data' => $data]);
    }

    public function create($data) {
        if (empty($data['name'])) {
            Response::json(['status' => 'error', 'message' => 'Tournament name is required'], 400);
        }

        $newId = $this->repo->createTournament(
            $data['name'],
            $data['category'] ?? 'Libre'
        );

        Response::json([
            'status' => 'success', 
            'message' => 'Tournament created', 
            'newId' => $newId
        ]);
    }
    
    public function update($data) {
        if (empty($data['id']) || empty($data['name'])) {
            Response::json(['status' => 'error', 'message' => 'ID and Name required'], 400);
        }

        $this->repo->update(
            (int)$data['id'],
            $data['name'],
            $data['category'] ?? 'Libre'
        );

        Response::json(['status' => 'success', 'message' => 'Torneo actualizado']);
    }
    
    public function delete($id) {
        if (!$id) Response::json(['status' => 'error', 'message' => 'ID required'], 400);
        $this->repo->delete($id);
        Response::json(['status' => 'success', 'message' => 'Torneo eliminado']);
    }
    
    public function getFixture($id) {
            if (!$id) Response::json(['status' => 'error', 'message' => 'ID required'], 400);
            $data = $this->repo->getFixtureData((int)$id);
            Response::json(['status' => 'success', 'data' => $data]);
        }

    public function generateFixture($input) {
            try {
                 Logger::write("Controller: generateFixture input:", $input);
    
                // 1. Extracción robusta del ID
                $tId = null;
                if (isset($input['tournament_id'])) {
                    $tId = $input['tournament_id'];
                } elseif (isset($_GET['tournament_id'])) {
                    // Fallback por si acaso se manda por URL
                    $tId = $_GET['tournament_id'];
                }
    
                // Convertir a entero para validación
                $tIdInt = intval($tId);
    
                if ($tIdInt <= 0) {
                    throw new Exception("ID de torneo inválido o faltante (Recibido: " . var_export($tId, true) . ")");
                }
    
                // 2. Validar configuración
                $configRaw = $input['config'] ?? [];
                if (!is_array($configRaw)) {
                    // Si viene como JSON string (a veces pasa con ciertos clientes HTTP)
                    $configRaw = json_decode($configRaw, true) ?? [];
                }
    
                $config = [
                    'matchups_per_pair' => $configRaw['vueltas'] ?? 1,
                    'points_win'        => $configRaw['pts_victoria'] ?? 2,
                    'points_draw'       => $configRaw['pts_empate'] ?? 1,
                    'points_loss'       => $configRaw['pts_derrota'] ?? 1,
                    'points_forfeit_win'=> 2,
                    'points_forfeit_loss'=> 0
                ];
    
                // 3. Ejecutar
                $generator = new FixtureGenerator();
                $result = $generator->generate($tIdInt, $config);
    
                Response::json($result);
    
            } catch (Exception $e) {
                Logger::write("Error generateFixture: " . $e->getMessage());
                Response::json(['status' => 'error', 'message' => $e->getMessage()], 500);
            }
        }
        
        public function updateFixtureMatch($input) {
        try {
            if (empty($input['match_id'])) throw new Exception("Falta match_id");

            $matchId = (int)$input['match_id'];
            $venueId = !empty($input['venue_id']) ? (int)$input['venue_id'] : null;
            $status = $input['status'] ?? 'SCHEDULED';
            
            $datetime = null;
            if (!empty($input['date']) && !empty($input['time'])) {
                $datetime = $input['date'] . ' ' . $input['time'] . ':00';
            }

            $this->repo->updateFixtureMatch($matchId, $datetime, $venueId, $status);
            Response::json(['status' => 'success', 'message' => 'Partido actualizado']);

        } catch (Exception $e) {
            Response::json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

}
?>