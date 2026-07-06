<?php
class CatalogController {
    private $repo;
    public function __construct() { $this->repo = new CatalogRepository(Database::getInstance()); }

    public function getAll() {
        Response::json(['status' => 'success', 'data' => $this->repo->getAllCatalogs()]);
    }
    
    public function getTournamentsList() {
        $publicOnly = isset($_GET['public_only']) && $_GET['public_only'] == '1';
        $data = $this->repo->getTournamentsList($publicOnly);
        Response::json(['status' => 'success', 'data' => $data]);
    }

    public function getDataByTournament() {
        $id = isset($_GET['tournament_id']) ? (int)$_GET['tournament_id'] : 0;
        $data = $this->repo->getDataByTournament($id);
        Response::json(['status' => 'success', 'data' => $data]);
    }
    
    public function getSyncData($tournamentId) {
        // repo debe ser una instancia de CatalogRepository
        $data = $this->repo->getSyncData((int)$tournamentId);
        Response::json(['status' => 'success', 'data' => $data]);
    }
}
?>