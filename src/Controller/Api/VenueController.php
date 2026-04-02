<?php
require_once __DIR__ . '/../../Repository/VenueRepository.php';

class VenueController extends BaseController {
    private $repo;

    public function __construct() {
        $this->repo = new VenueRepository(Database::getInstance());
    }

    public function create($data) {
        $cleanData = $this->sanitize($data);
        
        $this->validate($cleanData, [
            'name' => 'required'
        ]);

        try {
            $newId = $this->repo->create($cleanData['name'], $cleanData['address'] ?? '');
            Response::success('Sede creada exitosamente', ['newId' => $newId], Response::HTTP_CREATED);
        } catch (Exception $e) {
            Logger::write("Error en create Venue: " . $e->getMessage());
            Response::error('No se pudo crear la sede', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update($data) {
        $cleanData = $this->sanitize($data);
        
        $this->validate($cleanData, [
            'id'   => 'required|integer',
            'name' => 'required'
        ]);

        try {
            $this->repo->update((int)$cleanData['id'], $cleanData['name'], $cleanData['address'] ?? '');
            Response::success('Sede actualizada correctamente');
        } catch (Exception $e) {
            Logger::write("Error en update Venue: " . $e->getMessage());
            Response::error('No se pudo actualizar la sede', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function delete($id) {
        $this->validate(['id' => $id], ['id' => 'required|integer']);
        
        try {
            $this->repo->delete((int)$id);
            Response::success('Sede eliminada correctamente');
        } catch (Exception $e) {
            Logger::write("Error en delete Venue: " . $e->getMessage());
            
            // Checar si el error es de clave foránea (MySQL Error 1451)
            if (strpos($e->getMessage(), '1451') !== false || strpos($e->getMessage(), 'foreign key constraint') !== false) {
                 Response::error('No puedes eliminar esta sede porque hay partidos programados en ella.', Response::HTTP_CONFLICT);
            }
            
            Response::error('Ocurrió un error al intentar eliminar la sede.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
?>