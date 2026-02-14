<?php
class Router {
    public function handleRequest() {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: Content-Type");
        header("Content-Type: application/json; charset=UTF-8");

        $method = $_SERVER['REQUEST_METHOD'];
        
        // --- FIX PARA FLUTTER/CORS (IMPORTANTE) ---
        // Si Flutter manda una petición de prueba (OPTIONS), respondemos OK y terminamos.
        if ($method == 'OPTIONS') {
            http_response_code(200);
            exit();
        }

        $action = $_GET['action'] ?? 'NO_ACTION';
        
        // Leemos el input
        $inputJSON = file_get_contents('php://input');
        $input = json_decode($inputJSON, true) ?? $_POST;

        // --- LOGUEAR LA PETICIÓN ENTRANTE ---
        Logger::write("Petición Recibida: [$method] action=$action", $input);

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
                    
                // --- Creacion
                case 'create_tournament':(new TournamentController())->create($input);
                    break;
                case 'create_team':(new TeamController())->create($input);
                    break;
                case 'add_player':(new PlayerController())->addPlayer($input);
                    break;
                    
                // --- ACTUALIZACIÓN 
                case 'update_tournament': 
                    (new TournamentController())->update($input); 
                    break;
                case 'update_team': 
                    (new TeamController())->update($input); 
                    break;
                case 'update_player': 
                    (new PlayerController())->update($input); 
                    break;    
                    
                // --- Eliminacion
                case 'delete_tournament': 
                    (new TournamentController())->delete($input['id'] ?? 0); 
                    break;
                case 'delete_team': 
                    (new TeamController())->delete($input['id'] ?? 0); 
                    break;
                case 'delete_player': 
                    (new PlayerController())->delete($input['id'] ?? 0); 
                    break;    
                    
                case 'detach_team': (new TeamController())->detach($input); 
                    break;    

                // Sincronizacion Flutter
                case 'sync_match':(new MatchController())->sync($input);
                    break;
    
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