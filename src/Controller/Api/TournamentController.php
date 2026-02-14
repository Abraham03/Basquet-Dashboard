<?php
class TournamentController {
    private $repo;

    public function __construct() {
        $this->repo = new TournamentRepository(Database::getInstance());
    }

    public function getOne($id) {
        if (!$id) {
            Response::json(['status' => 'error', 'message' => 'Tournament ID required'], 400);
        }
        $data = $this->repo->getTournamentData((int)$id);
        Response::json(['status' => 'success', 'data' => $data]);
    }

    public function create($data) {
        if (empty($data['name'])) {
            Response::json(['status' => 'error', 'message' => 'Tournament name is required'], 400);
        }

        $newId = $this->repo->createTournament(
            $data['name'],
            $data['category'] ?? 'Libre'
        );

        Response::json([
            'status' => 'success', 
            'message' => 'Tournament created', 
            'newId' => $newId
        ]);
    }
    
    public function update($data) {
        if (empty($data['id']) || empty($data['name'])) {
            Response::json(['status' => 'error', 'message' => 'ID and Name required'], 400);
        }

        $this->repo->update(
            (int)$data['id'],
            $data['name'],
            $data['category'] ?? 'Libre'
        );

        Response::json(['status' => 'success', 'message' => 'Torneo actualizado']);
    }
    
    public function delete($id) {
    if (!$id) Response::json(['status' => 'error', 'message' => 'ID required'], 400);
    $this->repo->delete($id);
    Response::json(['status' => 'success', 'message' => 'Torneo eliminado']);
}
}
?>