<?php
class TeamController {
    private $repo;

    public function __construct() {
        // Instancia el repositorio automáticamente
        $this->repo = new TeamRepository(Database::getInstance());
    }
    
    // --- HELPER PARA SUBIR IMAGEN ---
    private function handleFileUpload() {
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../../assets/team_logo/'; 
            
            // Crear carpeta si no existe
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('team_') . '.' . $ext; // Nombre único
            $targetPath = $uploadDir . $filename;

            if (move_uploaded_file($_FILES['logo']['tmp_name'], $targetPath)) {
                return '../assets/team_logo/' . $filename; // Retornamos la ruta relativa para la BD
            }
        }
        return null;
    }

    public function create($data) {
        // 1. Validación básica
        if (empty($data['name'])) {
            Response::json(['status' => 'error', 'message' => 'Team name is required'], 400);
        }
        // Procesar imagen
        $logoUrl = $this->handleFileUpload();

        // 2. Crear el equipo (Lógica original)
        $newId = $this->repo->createTeam(
            $data['name'],
            $data['shortName'] ?? '',
            $data['coachName'] ?? '',
            $logoUrl
        );

        // 3. ¡IMPORTANTE! Vincular al torneo si viene el ID (Esto faltaba)
        if (!empty($data['tournament_id'])) {
            $this->repo->attachTeamToTournament((int)$newId, (int)$data['tournament_id']);
        }

        // 4. Respuesta con "newId" como espera Flutter
        Response::json([
            'status' => 'success', 
            'message' => 'Team created successfully', 
            'newId' => $newId
        ]);
    }
    
    public function update($data) {
        if (empty($data['id']) || empty($data['name'])) {
            Response::json(['status' => 'error', 'message' => 'ID and Name required'], 400);
        }
        
        // Procesar imagen (si se subió una nueva)
        $logoUrl = $this->handleFileUpload();

        // 1. Actualizar datos básicos
        $this->repo->update(
            (int)$data['id'],
            $data['name'],
            $data['shortName'] ?? '',
            $data['coachName'] ?? '',
            $logoUrl
        );

        // 2. Actualizar Torneo (Si viene en el JSON)
        if (!empty($data['tournament_id'])) {
            $this->repo->updateTeamTournament((int)$data['id'], (int)$data['tournament_id']);
        }

        Response::json(['status' => 'success', 'message' => 'Equipo actualizado']);
    }
    
    public function detach($data) {
        // Validamos que vengan el ID del equipo Y el ID del torneo
        if (empty($data['id']) || empty($data['tournament_id'])) {
            Response::json(['status' => 'error', 'message' => 'Se requiere ID del Equipo y ID del Torneo'], 400);
        }

        $this->repo->detachTeamFromTournament((int)$data['id'], (int)$data['tournament_id']);
        
        Response::json(['status' => 'success', 'message' => 'Equipo retirado del torneo']);
    }
    
    public function delete($id) {
        if (!$id) Response::json(['status' => 'error', 'message' => 'ID required'], 400);
        $this->repo->delete($id);
        Response::json(['status' => 'success', 'message' => 'Equipo eliminado']);
}

}
?>