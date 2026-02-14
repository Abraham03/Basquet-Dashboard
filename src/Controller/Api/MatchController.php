<?php
class MatchController {
    private $repo;

    public function __construct() {
        $this->repo = new MatchRepository(Database::getInstance());
    }

    public function sync($data) {
        // Validacion original
        if (empty($data['match_id'])) {
            Response::json(['status' => 'error', 'message' => 'match_id is required'], 400);
        }

        // Llamar al repo (que ya tiene la lógica compleja)
        $result = $this->repo->syncMatch($data);

        // Devolver respuesta según el resultado
        if ($result['status'] === 'success') {
            Response::json($result);
        } else {
            Response::json($result, 500);
        }
    }
}
?>