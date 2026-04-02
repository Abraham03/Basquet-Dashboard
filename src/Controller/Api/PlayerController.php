<?php
class PlayerController extends BaseController {
    private $repo;

    public function __construct() {
        $this->repo = new PlayerRepository(Database::getInstance());
    }
    
    // Helper para procesar la foto y organizarla por carpeta de equipo
    private function processPhotoUpload($teamId) {
        if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $folderName = 'equipo_' . (int)$teamId;
        $dir = __DIR__ . '/../../../assets/player_photos/' . $folderName . '/';
        
        $filename = FileUploader::uploadImage($_FILES['photo'], $dir, 'player_');
        
        return $filename ? ('../assets/player_photos/' . $folderName . '/' . $filename) : null;
    }

    // --- NUEVO: Helper para borrar físicamente la foto del servidor ---
    private function deleteOldPhoto(?string $photoUrl) {
        if (!empty($photoUrl)) {
            // Limpiamos la ruta relativa (../assets/...) para obtener la absoluta
            $cleanPath = str_replace(['../', './'], '', $photoUrl);
            $absolutePath = realpath(__DIR__ . '/../../../' . $cleanPath);
            
            // Si el archivo existe en el disco duro, lo eliminamos
            if ($absolutePath && file_exists($absolutePath) && is_file($absolutePath)) {
                unlink($absolutePath);
            }
        }
    }

    public function addPlayer($data) {
        $cleanData = $this->sanitize($data);

        $this->validate($cleanData, [
            'teamId' => 'required|integer',
            'name'   => 'required',
            'number' => 'integer'
        ]);

        $upperName = mb_strtoupper($cleanData['name'], 'UTF-8');
        $number = isset($cleanData['number']) && $cleanData['number'] !== '' ? (int)$cleanData['number'] : 0;

        // --- NUEVA VALIDACIÓN: ¿Existe el número de playera en el equipo? ---
        // Solo validamos si enviaron un número mayor a 0
        if ($number > 0 && $this->repo->playerNumberExistsInTeam((int)$cleanData['teamId'], $number)) {
            Response::error("El número de playera '$number' ya está ocupado por otro jugador en este equipo.", 400);
            return; // Detener ejecución
        }

        try {
            $photoUrl = $this->processPhotoUpload($cleanData['teamId']);
            $newId = $this->repo->createPlayer(
                (int)$cleanData['teamId'],
                $upperName,
                $number,
                $photoUrl
            );

            Response::success('Jugador agregado exitosamente', ['newId' => $newId], Response::HTTP_CREATED);

        } catch (Exception $e) {
            Logger::write("Error en addPlayer: " . $e->getMessage());
            Response::error('No se pudo guardar el jugador debido a un error interno.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update($data) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        $userRole = $_SESSION['admin_role'] ?? 'guest';
        $userTeam = $_SESSION['team_id'] ?? null;

        if ($userRole === 'coach') {
            if ($data['teamId'] != $userTeam) {
                Response::error("No tienes permisos para modificar jugadores de otros equipos.", 403);
                return;
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

        // --- NUEVA VALIDACIÓN: ¿Existe el número de playera en el equipo? (Ignorando a sí mismo) ---
        // Solo validamos si enviaron un número mayor a 0
        if ($number > 0 && $this->repo->playerNumberExistsInTeam((int)$cleanData['teamId'], $number, (int)$cleanData['id'])) {
            Response::error("El número de playera '$number' ya está ocupado por otro jugador en este equipo.", 400);
            return;
        }

        try {
            // 1. Obtener la foto actual ANTES de hacer cualquier cambio
            $oldPhotoUrl = $this->repo->getPhotoUrl((int)$cleanData['id']);
            
            // 2. Intentar subir la nueva foto (si el usuario envió una)
            $newPhotoUrl = $this->processPhotoUpload($cleanData['teamId']);
            
            // 3. Actualizar la base de datos
            $this->repo->update(
                (int)$cleanData['id'],
                (int)$cleanData['teamId'],
                $upperName,
                $number,
                $newPhotoUrl
            );

            // 4. Si se subió una NUEVA foto exitosamente, borramos la VIEJA del disco duro
            if ($newPhotoUrl !== null && $oldPhotoUrl !== null) {
                $this->deleteOldPhoto($oldPhotoUrl);
            }

            Response::success('Jugador actualizado correctamente');

        } catch (Exception $e) {
            Logger::write("Error en update Player: " . $e->getMessage());
            Response::error('No se pudo actualizar el jugador.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function delete($id) {
        $this->validate(['id' => $id], [
            'id' => 'required|integer'
        ]);

        try {
            // 1. Obtener la foto antes de borrar el registro
            $oldPhotoUrl = $this->repo->getPhotoUrl((int)$id);
            
            // 2. Borrar de la base de datos
            $this->repo->delete((int)$id);
            
            // 3. Borrar el archivo físico si existía
            if ($oldPhotoUrl !== null) {
                $this->deleteOldPhoto($oldPhotoUrl);
            }
            
            Response::success('Jugador eliminado correctamente');
        } catch (Exception $e) {
            Logger::write("Error en delete Player: " . $e->getMessage());
            Response::error('No se pudo eliminar el jugador.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
?>