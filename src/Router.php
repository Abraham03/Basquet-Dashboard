<?php
class Router {
    public function handleRequest() {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: Content-Type");
        header("Content-Type: application/json; charset=UTF-8");

        $method = $_SERVER['REQUEST_METHOD'];
        
        if ($method == 'OPTIONS') {
            http_response_code(200);
            exit();
        }

        // 1. Leer y Decodificar Input
        $inputJSON = file_get_contents('php://input');
        $input = json_decode($inputJSON, true);

        // Si no es JSON válido, intentar POST estándar
        if (!is_array($input)) {
            $input = $_POST;
        }

        // 2. Determinar Acción
        // Prioridad: 1. URL ($_GET) -> 2. Cuerpo JSON ($input) -> 3. Defecto
        $action = $_GET['action'] ?? ($input['action'] ?? 'NO_ACTION');

        Logger::write("Router: [$method] action=$action", $input); // Log simplificado
        try {
            switch ($action) {
                // --- Autenticacion
                case 'admin_login':(new AuthController())->login($input);
                    break;
                case 'admin_logout':(new AuthController())->logout();
                    break;
                    
                // --- Lectura   
                case 'get_data':(new CatalogController())->getAll();
                    break;
                case 'get_tournament_data':(new TournamentController())->getOne($_GET['tournament_id'] ?? 0);
                    break;
                case 'get_dashboard_stats':(new StatsController())->getDashboardStats($_GET['tournament_id'] ?? 0);
                    break;  
                case 'get_tournaments_list': (new CatalogController())->getTournamentsList();
                    break;
                case 'get_data_by_tournament':(new CatalogController())->getDataByTournament();
                    break;    
                case 'get_fixture': (new TournamentController())->getFixture($_GET['tournament_id'] ?? 0);
                    break;
                case 'get_sync_data': 
                    $tid = isset($_GET['tournament_id']) ? (int)$_GET['tournament_id'] : 0;
                    (new CatalogController())->getSyncData($tid);
                    break;
                    
                // --- Creacion
                case 'create_tournament':(new TournamentController())->create($input);
                    break;
                case 'create_team':(new TeamController())->create($input);
                    break;
                case 'add_player':(new PlayerController())->addPlayer($input);
                    break;
                    
                case 'generate_fixture': 
                        // Validación rápida antes de llamar al controlador
                        if(empty($input)) {
                            throw new Exception("El cuerpo de la petición está vacío (JSON inválido)");
                        }
                        (new TournamentController())->generateFixture($input); 
                    break;    
                    
                // --- ACTUALIZACIÓN 
                case 'update_tournament': (new TournamentController())->update($input); 
                    break;
                case 'update_team': (new TeamController())->update($input); 
                    break;
                case 'update_player': (new PlayerController())->update($input); 
                    break;    
                case 'update_fixture_match': (new TournamentController())->updateFixtureMatch($input); break;    
                    
                // --- Eliminacion
                case 'delete_tournament':(new TournamentController())->delete($input['id'] ?? 0); 
                    break;
                case 'delete_team':(new TeamController())->delete($input['id'] ?? 0); 
                    break;
                case 'delete_player':(new PlayerController())->delete($input['id'] ?? 0); 
                    break;    
                case 'detach_team': (new TeamController())->detach($input); 
                    break;   
                case 'delete_fixture': (new TournamentController())->deleteFixture($input); break;    

                // Sincronizacion Flutter
                case 'sync_match':
                        // Si viene como multipart (con archivo), los datos JSON están en $_POST['data']
                        if (isset($_POST['data'])) {
                            $input = json_decode($_POST['data'], true);
                        }
                        (new MatchController())->sync($input);
                    break;
                    
                // --- Galería Slider
                case 'get_slider_images': (new GalleryController())->getImages(); break;
                case 'upload_slider_image': (new GalleryController())->uploadImage(); break;
                case 'delete_slider_image': (new GalleryController())->deleteImage($input); break; 
                
                // --- Usuarios y Accesos
                case 'get_users': (new UserController())->getAll(); break;
                case 'create_user': (new UserController())->create($input); break;
                case 'update_user': (new UserController())->update($input); break;
                case 'delete_user': (new UserController())->delete($input['id'] ?? 0); break;
                    
    
                default:
                    Logger::write("Error: Acción no encontrada -> $action");
                    Response::json(['status' => 'error', 'message' => 'Action not found'], 404);
            }
        } catch (Exception $e) {
            // --- LOGUEAR EL ERROR REAL ---
            Logger::write("EXCEPCIÓN CRÍTICA: " . $e->getMessage());
            Logger::write("Archivo: " . $e->getFile() . " Línea: " . $e->getLine());
            Logger::write("Trace: " . $e->getTraceAsString());
            
            Response::json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
?>