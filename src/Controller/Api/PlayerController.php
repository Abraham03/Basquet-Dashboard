<?php
class PlayerController extends BaseController {
    private $repo;

    public function __construct() {
        // Instancia el repositorio automáticamente
        $this->repo = new PlayerRepository(Database::getInstance());
    }

    public function addPlayer($data) {
        // 1. Sanitizar la entrada para evitar XSS
        $cleanData = $this->sanitize($data);

        // 2. Validar usando BaseController
        // Según BD: teamId y name son requeridos. number es opcional.
        $this->validate($cleanData, [
            'teamId' => 'required|integer',
            'name'   => 'required',
            'number' => 'integer'
        ]);

        // 3. Convertir a mayúsculas (soportando acentos y caracteres latinos)
        $upperName = mb_strtoupper($cleanData['name'], 'UTF-8');
        $number = isset($cleanData['number']) && $cleanData['number'] !== '' ? (int)$cleanData['number'] : 0;

        try {
            // 4. Guardar en Base de Datos
            $newId = $this->repo->createPlayer(
                (int)$cleanData['teamId'],
                $upperName,
                $number
            );

            // 5. Respuesta de Éxito
            Response::success('Jugador agregado exitosamente', ['newId' => $newId], Response::HTTP_CREATED);

        } catch (Exception $e) {
            Logger::write("Error en addPlayer: " . $e->getMessage());
            Response::error('No se pudo guardar el jugador debido a un error interno.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update($data) {
        $cleanData = $this->sanitize($data);

        $this->validate($cleanData, [
            'id'     => 'required|integer',
            'teamId' => 'required|integer',
            'name'   => 'required',
            'number' => 'integer'
        ]);

        $upperName = mb_strtoupper($cleanData['name'], 'UTF-8');
        $number = isset($cleanData['number']) && $cleanData['number'] !== '' ? (int)$cleanData['number'] : 0;

        try {
            $this->repo->update(
                (int)$cleanData['id'],
                (int)$cleanData['teamId'],
                $upperName,
                $number
            );

            Response::success('Jugador actualizado correctamente');

        } catch (Exception $e) {
            Logger::write("Error en update Player: " . $e->getMessage());
            Response::error('No se pudo actualizar el jugador.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function delete($id) {
        // Envolvemos el ID en un array para poder validarlo con nuestro BaseController
        $this->validate(['id' => $id], [
            'id' => 'required|integer'
        ]);

        try {
            $this->repo->delete((int)$id);
            Response::success('Jugador eliminado correctamente');
        } catch (Exception $e) {
            Logger::write("Error en delete Player: " . $e->getMessage());
            Response::error('No se pudo eliminar el jugador.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
?>