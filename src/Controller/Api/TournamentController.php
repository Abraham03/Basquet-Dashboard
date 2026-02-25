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
            Response::error('No se pudo eliminar el torneo', Response::HTTP_INTERNAL_SERVER_ERROR);
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

            // --- NUEVA VALIDACIÓN DE NEGOCIO ---
            if ($this->repo->hasPlayedMatches($tIdInt)) {
                // Si ya empezó, enviamos error 400 y detenemos la ejecución
                Response::error(
                    'No se puede regenerar el fixture porque ya existen partidos en juego o finalizados. Debes cancelar los partidos primero.', 
                    Response::HTTP_BAD_REQUEST
                );
            }
            // -----------------------------------

            $configRaw = $input['config'] ?? [];
            if (is_string($configRaw)) {
                $configRaw = json_decode($configRaw, true) ?? [];
            }

            $config = [
                'matchups_per_pair'   => (int)($configRaw['vueltas'] ?? 1),
                'points_win'          => (int)($configRaw['pts_victoria'] ?? 2),
                'points_draw'         => (int)($configRaw['pts_empate'] ?? 0),
                'points_loss'         => (int)($configRaw['pts_derrota'] ?? 1),
                'points_forfeit_win'  => 2,
                'points_forfeit_loss' => 0
            ];

            $generator = new FixtureGenerator();
            // Asegúrate de que tu FixtureGenerator esté llamando a clearFixture() 
            // antes de empezar a insertar los nuevos datos.
            $result = $generator->generate($tIdInt, $config);

            Response::json($result);

        } catch (Exception $e) {
            Logger::write("Error generateFixture: " . $e->getMessage());
            Response::error($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function updateFixtureMatch($input) {
        $cleanData = $this->sanitize($input);

        // Aprovechamos la nueva regla 'in:' del BaseController para el status
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
                // Validación básica de formato fecha-hora
                $datetime = $cleanData['date'] . ' ' . $cleanData['time'] . ':00';
            }

            $this->repo->updateFixtureMatch($matchId, $datetime, $venueId, $status);
            Response::success('Partido actualizado correctamente');

        } catch (Exception $e) {
            Logger::write("Error updateFixtureMatch: " . $e->getMessage());
            Response::error('No se pudo actualizar el partido', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
?>