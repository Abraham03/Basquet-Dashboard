<?php
class PlayerController extends BaseController {
    private $repo;

    public function __construct() {
        // Instancia el repositorio automáticamente
        $this->repo = new PlayerRepository(Database::getInstance());
    }
    
    // Helper para procesar la foto y organizarla por carpeta de equipo
    private function processPhotoUpload($teamId) {
        if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        // Crear una carpeta específica para el equipo (Ej: equipo_15)
        $folderName = 'equipo_' . (int)$teamId;
        $dir = __DIR__ . '/../../../assets/player_photos/' . $folderName . '/';
        
        $filename = FileUploader::uploadImage($_FILES['photo'], $dir, 'player_');
        
        return $filename ? ('../assets/player_photos/' . $folderName . '/' . $filename) : null;
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
            $photoUrl = $this->processPhotoUpload($cleanData['teamId']);
            // 4. Guardar en Base de Datos
            $newId = $this->repo->createPlayer(
                (int)$cleanData['teamId'],
                $upperName,
                $number,
                $photoUrl
            );

            // 5. Respuesta de Éxito
            Response::success('Jugador agregado exitosamente', ['newId' => $newId], Response::HTTP_CREATED);

        } catch (Exception $e) {
            Logger::write("Error en addPlayer: " . $e->getMessage());
            Response::error('No se pudo guardar el jugador debido a un error interno.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update($data) {
        // Verificamos quién está haciendo la petición
    if (session_status() === PHP_SESSION_NONE) session_start();
    
    $userRole = $_SESSION['admin_role'] ?? 'guest';
    $userTeam = $_SESSION['team_id'] ?? null;

    // Si es un coach, verificamos que el teamId que intenta modificar sea el suyo
    if ($userRole === 'coach') {
        if ($data['teamId'] != $userTeam) {
            Response::error("No tienes permisos para modificar jugadores de otros equipos.", 403);
        }
    }
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
            $photoUrl = $this->processPhotoUpload($cleanData['teamId']);
            $this->repo->update(
                (int)$cleanData['id'],
                (int)$cleanData['teamId'],
                $upperName,
                $number,
                $photoUrl
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