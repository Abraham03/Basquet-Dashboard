<?php
class PlayerController {
    private $repo;

    public function __construct() {
        // Instancia el repositorio automáticamente
        $this->repo = new PlayerRepository(Database::getInstance());
    }



    public function addPlayer($data) {
        if (empty($data['teamId']) || empty($data['name'])) {
            Response::json(['status' => 'error', 'message' => 'Team ID and Name requerido'], 400);
        }

        $newId = $this->repo->createPlayer(
            (int)$data['teamId'],
            $data['name'],
            (int)($data['number'] ?? 0)
        );

        Response::json([
            'status' => 'success', 
            'message' => 'Player added', 
            'newId' => $newId
        ]);
    }
    
    public function update($data) {
        if (empty($data['id']) || empty($data['name'])) {
            Response::json(['status' => 'error', 'message' => 'ID and Name required'], 400);
        }

        $this->repo->update(
            (int)$data['id'],
            (int)($data['teamId'] ?? 0), // Si no envía teamId, se podría mantener el anterior, pero aquí pedimos uno nuevo o 0
            $data['name'],
            (int)($data['number'] ?? 0)
        );

        Response::json(['status' => 'success', 'message' => 'Jugador actualizado']);
    }
    
    public function delete($id) {
        if (!$id) Response::json(['status' => 'error', 'message' => 'ID requerido'], 400);
        $this->repo->delete($id);
        Response::json(['status' => 'success', 'message' => 'Jugador eliminado']);
}
}
?>