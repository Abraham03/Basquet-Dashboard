<?php
class TournamentController extends BaseController {
    private $repo;

    public function __construct() {
        $this->repo = new TournamentRepository(Database::getInstance());
    }

    public function getOne($id) {
        $this->validate(['id' => $id], ['id' => 'required|integer']);
        
        try {
            $data = $this->repo->getTournamentData((int)$id);
            if (!$data) {
                Response::error('Torneo no encontrado', Response::HTTP_NOT_FOUND);
            }
            Response::success('Torneo recuperado', $data);
        } catch (Exception $e) {
            Response::error('Error interno del servidor', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function create($data) {
        $cleanData = $this->sanitize($data);
        $this->validate($cleanData, [
            'name' => 'required'
        ]);

        $upperName = mb_strtoupper($cleanData['name'], 'UTF-8');
        $upperCategory = !empty($cleanData['category']) ? mb_strtoupper($cleanData['category'], 'UTF-8') : 'LIBRE';

        try {
            $newId = $this->repo->createTournament($upperName, $upperCategory);
            // CORRECCIÓN CLAVE: Envolvemos en un array como pide Response.php
            Response::success('Torneo creado exitosamente', ['newId' => $newId], Response::HTTP_CREATED);
        } catch (Exception $e) {
            Logger::write("Error en create Tournament: " . $e->getMessage());
            Response::error('No se pudo crear el torneo', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update($data) {
        $cleanData = $this->sanitize($data);
        $this->validate($cleanData, [
            'id'   => 'required|integer',
            'name' => 'required'
        ]);

        $upperName = mb_strtoupper($cleanData['name'], 'UTF-8');
        $upperCategory = !empty($cleanData['category']) ? mb_strtoupper($cleanData['category'], 'UTF-8') : 'LIBRE';

        try {
            $this->repo->update((int)$cleanData['id'], $upperName, $upperCategory);
            Response::success('Torneo actualizado correctamente');
        } catch (Exception $e) {
            Logger::write("Error en update Tournament: " . $e->getMessage());
            Response::error('No se pudo actualizar el torneo', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function delete($id) {
        $this->validate(['id' => $id], ['id' => 'required|integer']);
        
        try {
            $this->repo->delete((int)$id);
            Response::success('Torneo eliminado correctamente');
        } catch (Exception $e) {
            Logger::write("Error en delete Tournament: " . $e->getMessage());
            Response::error($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function deleteFixture($input) {
        $tId = $input['id'] ?? null;
        $this->validate(['id' => $tId], ['id' => 'required|integer']);
        $tIdInt = (int)$tId;

        try {
            $this->repo->clearFixture($tIdInt);
            Response::success('Calendario y estadísticas purgadas correctamente');
        } catch (Exception $e) {
            Logger::write("Error deleteFixture: " . $e->getMessage());
            Response::error('Error interno al purgar el calendario', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function getFixture($id) {
        $this->validate(['id' => $id], ['id' => 'required|integer']);
        
        try {
            $data = $this->repo->getFixtureData((int)$id);
            Response::success('Fixture recuperado', $data);
        } catch (Exception $e) {
            Response::error($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function generateFixture($input) {
        try {
            Logger::write("Controller: generateFixture request recibida.");

            $tId = $input['tournament_id'] ?? $_GET['tournament_id'] ?? null;
            $this->validate(['tournament_id' => $tId], ['tournament_id' => 'required|integer']);
            $tIdInt = (int)$tId;

            if ($this->repo->hasPlayedMatches($tIdInt)) {
                Response::error(
                    'No se puede regenerar el fixture porque ya existen partidos en juego o finalizados. Debes cancelar los partidos primero.', 
                    Response::HTTP_BAD_REQUEST
                );
            }

            // AHORA LAS REGLAS SE LEEN DESDE LA BASE DE DATOS
            $stmt = $this->repo->getTournamentData($tIdInt); // Reutilizamos un método (o creamos una consulta directa si prefieres)
            $db = Database::getInstance()->getConnection();
            $rulesStmt = $db->query("SELECT * FROM tournament_rules WHERE tournament_id = $tIdInt");
            $rulesData = $rulesStmt->fetch_assoc();

            $config = [
                'matchups_per_pair'   => (int)($rulesData['matchups_per_pair'] ?? 1),
                'points_win'          => (int)($rulesData['points_win'] ?? 2),
                'points_draw'         => (int)($rulesData['points_draw'] ?? 0),
                'points_loss'         => (int)($rulesData['points_loss'] ?? 1),
                'points_forfeit_win'  => (int)($rulesData['points_forfeit_win'] ?? 2),
                'points_forfeit_loss' => (int)($rulesData['points_forfeit_loss'] ?? 0)
            ];

            // Pasamos el config a tu generador que no se modifica
            require_once __DIR__ . '/../../Service/FixtureGenerator.php';
            $generator = new FixtureGenerator();
            $result = $generator->generate($tIdInt, $config);

            Response::json($result);

        } catch (Exception $e) {
            Logger::write("Error generateFixture: " . $e->getMessage());
            Response::error($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    
    // Actualiza los equipos de un partido manual existente
    public function updateFixtureTeams($data) {
        $cleanData = $this->sanitize($data);
        $this->validate($cleanData, [
            'fixture_id'    => 'required|integer',
            'new_team_a_id' => 'required|integer',
            'new_team_b_id' => 'required|integer'
        ]);

        try {
            $fixtureId = (int)$cleanData['fixture_id'];
            $teamA = (int)$cleanData['new_team_a_id'];
            $teamB = (int)$cleanData['new_team_b_id'];

            // Llamamos a la función que ya tenías en tu Repository
            $this->repo->updateFixtureTeams($fixtureId, $teamA, $teamB);

            Response::success('Equipos del partido actualizados');
        } catch (Exception $e) {
            Logger::write("Error en updateFixtureTeams: " . $e->getMessage());
            Response::error('Error actualizando partido', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    // Actualiza la fecha, hor, cancha y estado de un encuentro 
    public function updateFixtureMatch($input) {
        $cleanData = $this->sanitize($input);

        $this->validate($cleanData, [
            'match_id' => 'required|integer',
            'venue_id' => 'integer',
            'status'   => 'in:SCHEDULED,PLAYING,FINISHED,CANCELLED'
        ]);

        try {
            $matchId = (int)$cleanData['match_id'];
            $venueId = !empty($cleanData['venue_id']) ? (int)$cleanData['venue_id'] : null;
            $status  = $cleanData['status'] ?? 'SCHEDULED';
            
            $datetime = null;
            if (!empty($cleanData['date']) && !empty($cleanData['time'])) {
                $datetime = $cleanData['date'] . ' ' . $cleanData['time'] . ':00';
            }

            $this->repo->updateFixtureMatch($matchId, $datetime, $venueId, $status);
            Response::success('Partido actualizado correctamente');

        } catch (Exception $e) {
            Logger::write("Error updateFixtureMatch: " . $e->getMessage());
            Response::error('No se pudo actualizar el partido', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    // Obtiene el estado de los equipos para el Constructor Manual en la App
    public function getTeamSchedulingStatus($tournamentId, $roundId) {
        $this->validate(
            ['tournament_id' => $tournamentId, 'round_id' => $roundId], 
            ['tournament_id' => 'required|integer', 'round_id' => 'required|integer']
        );
        
        try {
            // Llama al método que creamos en el paso anterior en TournamentRepository
            $data = $this->repo->getTeamSchedulingStatus((int)$tournamentId, (int)$roundId);
            Response::success('Estado de equipos recuperado', $data);
        } catch (Exception $e) {
            Logger::write("Error getTeamSchedulingStatus: " . $e->getMessage());
            Response::error('Error interno al obtener estado de los equipos', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function saveTournamentRules($input) {
        $cleanData = $this->sanitize($input);
        
        $this->validate($cleanData, [
            'tournament_id' => 'required|integer',
            'config' => 'required' 
        ]);

        try {
            $tId = (int)$cleanData['tournament_id'];
            $config = $cleanData['config'];
            
            $this->repo->saveRules($tId, $config);
            
            Response::success('Reglas guardadas correctamente');
        } catch (Exception $e) {
            Logger::write("Error saveTournamentRules: " . $e->getMessage());
            Response::error('Error guardando reglas', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    // Guarda un partido creado manualmente
    public function addManualFixture($data) {
        $cleanData = $this->sanitize($data);
        $this->validate($cleanData, [
            'tournament_id' => 'required|integer',
            'round_order'   => 'required|integer',
            'team_a_id'     => 'required|integer',
            'team_b_id'     => 'required|integer'
        ]);

        try {
            $tId = (int)$cleanData['tournament_id'];
            $roundOrder = (int)$cleanData['round_order'];
            $teamA = (int)$cleanData['team_a_id'];
            $teamB = (int)$cleanData['team_b_id'];

            // 1. Obtener o crear la jornada
            $roundId = $this->repo->getOrCreateRound($tId, "Jornada " . $roundOrder, $roundOrder);

            // 2. Insertar el partido
            $newFixtureId = $this->repo->addManualFixture($tId, $roundId, $teamA, $teamB);

            Response::success('Partido creado exitosamente', ['fixture_id' => $newFixtureId], Response::HTTP_CREATED);
        } catch (Exception $e) {
            Logger::write("Error en addManualFixture: " . $e->getMessage());
            Response::error('Error creando partido manual', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
?>