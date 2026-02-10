<?php
/**
 * Clase ApiController
 * Responsabilidad: Recibir peticiones HTTP, validar inputs y dirigir al repositorio correcto.
 */
class ApiController {
    private CatalogRepository $catalogRepo;
    private MatchRepository $matchRepo;

    public function __construct() {
        // 1. Obtener instancia Singleton de la BD
        $this->logFile = dirname(__DIR__) . '/debug_controller.txt';
        $this->log("------------------------------------------------");
        $this->log("1. Constructor ApiController iniciado.");
        try {
            // Intentar conectar a la BD
            $this->log("2. Intentando obtener instancia de Database...");
            $database = Database::getInstance();
            
            // Verificar si la conexión es válida
            if ($database->getConnection()->connect_error) {
                $this->log("ERROR FATAL: Conexión fallida: " . $database->getConnection()->connect_error);
                throw new Exception("DB Error");
            }
            $this->log("3. Conexión DB exitosa.");

            $this->catalogRepo = new CatalogRepository($database);
            $this->matchRepo = new MatchRepository($database);
            $this->log("4. Repositorios inicializados.");

        } catch (Exception $e) {
            $this->log("EXCEPCIÓN EN CONSTRUCTOR: " . $e->getMessage());
            // No detenemos aquí para permitir que handleRequest devuelva el error JSON
        }
    }
    
    private function log($msg) {
        // Escribir en el archivo de log con fecha/hora
        file_put_contents($this->logFile, date('H:i:s') . " - " . $msg . "\n", FILE_APPEND);
    }

    public function handleRequest(): void {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? null;

$this->log("5. HandleRequest: Método [$method] - Acción [$action]");

        try {
            if ($method === 'GET') {
                $this->handleGet($action);
            } elseif ($method === 'POST') {
                $this->handlePost($action);
            } else {
                $this->log("Error: Método no permitido");
                Response::json(['status' => 'error', 'message' => 'Method not allowed'], 405);
            }
        } catch (Exception $e) {
            $this->log("ERROR CRÍTICO: " . $e->getMessage());
            Response::json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    private function handleGet(?string $action): void {
        $this->log("6. Procesando GET...");
        switch ($action) {
            case 'get_data':
                $data = $this->catalogRepo->getAllCatalogs();
                Response::json(['status' => 'success', 'data' => $data]);
                break;
            
            default:
                Response::json(['status' => 'ok', 'message' => 'Basketball API v3.0 Running']);
                break;
        }
    }

    private function handlePost(?string $action): void {
        $this->log("6. Procesando POST...");
        // Leer el cuerpo JSON
        $inputJSON = file_get_contents('php://input');
        $this->log("7. Payload RAW recibido (Primeros 100 caracteres): " . substr($inputJSON, 0, 100) . "...");
        $input = json_decode($inputJSON, true);

        // Validación estricta del JSON
        if ((!$input || !is_array($input)) && json_last_error() !== JSON_ERROR_NONE) {
            $this->log("ERROR: JSON inválido. Error: " . json_last_error_msg());
            Response::json(['status' => 'error', 'message' => 'Invalid JSON body'], 400);
            return;
        }

        switch ($action) {
            // --- CASO 1: SINCRONIZAR PARTIDO ---
            case 'sync_match':
                $this->log("8. Entrando a case 'sync_match'");
                if (empty($input['match_id'])) {
                    $this->log("ERROR: match_id faltante o vacío en el JSON.");
                    $this->log("Contenido de input: " . print_r($input, true));
                    Response::json(['status' => 'error', 'message' => 'match_id is required'], 400);
                    return;
                }
                $this->log("9. Llamando a MatchRepository->syncMatch...");
                $result = $this->matchRepo->syncMatch($input);
                $this->log("10. Resultado del Repo: " . print_r($result, true));
                if ($result['status'] === 'success') {
                    Response::json($result);
                } else {
                    Response::json($result, 500);
                }
                break;

            // --- CASO 2: CREAR EQUIPO ---
            case 'create_team':
                if (empty($input['name'])) {
                    Response::json(['status' => 'error', 'message' => 'Team name is required'], 400);
                    return;
                }
                
                $newId = $this->catalogRepo->createTeam(
                    $input['name'], 
                    $input['shortName'] ?? '', 
                    $input['coachName'] ?? ''
                );
                
                Response::json(['status' => 'success', 'message' => 'Team created', 'newId' => $newId]);
                break;

            // --- CASO 3: AGREGAR JUGADOR ---
            case 'add_player':
                if (empty($input['teamId']) || empty($input['name'])) {
                    Response::json(['status' => 'error', 'message' => 'Team ID and Name required'], 400);
                    return;
                }

                $newId = $this->catalogRepo->createPlayer(
                    (int)$input['teamId'],
                    $input['name'],
                    (int)($input['number'] ?? 0)
                );

                Response::json(['status' => 'success', 'message' => 'Player added', 'newId' => $newId]);
                break; 

            default:
                Response::json(['status' => 'error', 'message' => 'Unknown POST action: ' . $action], 400);
        }
    }
}
?>