<?php
/**
 * Clase ApiController
 * Responsabilidad: Recibir peticiones HTTP, validar inputs y dirigir al repositorio correcto.
 */
class ApiController {
    private CatalogRepository $catalogRepo;
    private MatchRepository $matchRepo;

    public function __construct() {
        $this->catalogRepo = new CatalogRepository();
        $this->matchRepo = new MatchRepository();
    }

    public function handleRequest(): void {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? null;

        try {
            if ($method === 'GET') {
                $this->handleGet($action);
            } elseif ($method === 'POST') {
                $this->handlePost($action);
            } else {
                Response::json(['status' => 'error', 'message' => 'Method not allowed'], 405);
            }
        } catch (Exception $e) {
            Response::json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    private function handleGet(?string $action): void {
        switch ($action) {
            case 'get_data':
                $data = $this->catalogRepo->getAllCatalogs();
                Response::json(['status' => 'success', 'data' => $data]);
                break;
            
            default:
                Response::json(['status' => 'ok', 'message' => 'Basketball API v2.0 (OOP) Running']);
                break;
        }
    }

    private function handlePost(?string $action): void {
        // Leer el cuerpo JSON
        $inputJSON = file_get_contents('php://input');
        $input = json_decode($inputJSON, true);

        if (!$input) {
            Response::json(['status' => 'error', 'message' => 'Invalid JSON body'], 400);
        }

        switch ($action) {
            // --- CASO 1: SINCRONIZAR PARTIDO ---
            case 'sync_match':
                if (!isset($input['matchId'])) {
                    Response::json(['status' => 'error', 'message' => 'matchId is required'], 400);
                }
                $this->matchRepo->syncMatch($input);
                Response::json(['status' => 'success', 'message' => 'Match synced successfully']);
                break;

            // --- CASO 2: CREAR EQUIPO (MOVIDO AQUÍ CORRECTAMENTE) ---
            case 'create_team':
                if (empty($input['name'])) {
                    Response::json(['status' => 'error', 'message' => 'Team name is required'], 400);
                }
                
                $newId = $this->catalogRepo->createTeam(
                    $input['name'], 
                    $input['shortName'] ?? '', 
                    $input['coachName'] ?? ''
                );
                
                Response::json(['status' => 'success', 'message' => 'Team created', 'newId' => $newId]);
                break;

            // --- CASO 3: AGREGAR JUGADOR (MOVIDO AQUÍ CORRECTAMENTE) ---
            case 'add_player':
                if (empty($input['teamId']) || empty($input['name'])) {
                    Response::json(['status' => 'error', 'message' => 'Team ID and Name required'], 400);
                }

                $newId = $this->catalogRepo->createPlayer(
                    (int)$input['teamId'],
                    $input['name'],
                    (int)($input['number'] ?? 0)
                );

                Response::json(['status' => 'success', 'message' => 'Player added', 'newId' => $newId]);
                break;  

            default:
                Response::json(['status' => 'error', 'message' => 'Unknown POST action'], 400);
        }
    }
}
?>