<?php
require_once __DIR__ . '/../../Repository/OfficialRepository.php';

class OfficialController extends BaseController {
    private $repo;

    public function __construct() {
        $this->repo = new OfficialRepository(Database::getInstance());
    }

    public function getAll() {
        try {
            $data = $this->repo->getAllActive();
            Response::success('Oficiales obtenidos', $data);
        } catch (Exception $e) {
            Logger::write("Error en OfficialController getAll: " . $e->getMessage());
            Response::error($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function create($data) {
        $cleanData = $this->sanitize($data);
        $this->validate($cleanData, ['name' => 'required', 'role' => 'required']);

        $role = mb_strtoupper($cleanData['role'], 'UTF-8');
        $name = mb_strtoupper($cleanData['name'], 'UTF-8');
        $signature = $data['signature'] ?? null; // Recibimos el string largo de la firma

        try {
            $newId = $this->repo->create($name, $role, $signature);
            Response::success('Oficial creado', ['id' => $newId], Response::HTTP_CREATED);
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
        public function update($data) {
        $cleanData = $this->sanitize($data);
        
        $this->validate($cleanData, [
            'id'   => 'required',
            'name' => 'required',
            'role' => 'required'
        ]);
    
        $validRoles = ['ARBITRO_PRINCIPAL', 'ARBITRO_AUXILIAR', 'ANOTADOR'];
        $role = mb_strtoupper($cleanData['role'], 'UTF-8');
        $name = mb_strtoupper($cleanData['name'], 'UTF-8');
        
        // --- LÍNEA CLAVE AGREGADA ---
        $signature = $data['signature'] ?? null; 
    
        if (!in_array($role, $validRoles)) {
            Response::error("Rol inválido.", 400);
        }
    
        try {
            // --- SE AGREGA EL CUARTO PARÁMETRO AL REPO ---
            $this->repo->update((int)$cleanData['id'], $name, $role, $signature);
            Response::success('Oficial actualizado con éxito');
        } catch (Exception $e) {
            Logger::write("Error en OfficialController update: " . $e->getMessage());
            Response::error('Error al actualizar el oficial', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function delete($id) {
        $this->validate(['id' => $id], ['id' => 'required|integer']);

        try {
            $this->repo->delete((int)$id);
            Response::success('Oficial eliminado');
        } catch (Exception $e) {
            Logger::write("Error en OfficialController delete: " . $e->getMessage());
            Response::error('Error al eliminar el oficial', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
?>