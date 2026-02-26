<?php
class MatchController extends BaseController {
    private $repo;

    public function __construct() {
        $this->repo = new MatchRepository(Database::getInstance());
    }

    public function sync($data) {
        $cleanData = $this->sanitize($data);

        $this->validate($cleanData, [
            'match_id'  => 'required', 
            'team_a_id' => 'required|integer',
            'team_b_id' => 'required|integer',
            'score_a'   => 'required|numeric',
            'score_b'   => 'required|numeric'
        ]);

        $cleanData['team_a_name']  = isset($cleanData['team_a_name'])  ? mb_strtoupper($cleanData['team_a_name'], 'UTF-8') : '';
        $cleanData['team_b_name']  = isset($cleanData['team_b_name'])  ? mb_strtoupper($cleanData['team_b_name'], 'UTF-8') : '';
        $cleanData['main_referee'] = isset($cleanData['main_referee']) ? mb_strtoupper($cleanData['main_referee'], 'UTF-8') : '';
        $cleanData['aux_referee']  = isset($cleanData['aux_referee'])  ? mb_strtoupper($cleanData['aux_referee'], 'UTF-8') : '';
        $cleanData['scorekeeper']  = isset($cleanData['scorekeeper'])  ? mb_strtoupper($cleanData['scorekeeper'], 'UTF-8') : '';

        $cleanData['events'] = $data['events'] ?? [];
        $cleanData['signature_base64'] = $data['signature_base64'] ?? null;

        try {
            $pdfPath = null;
            if (isset($_FILES['pdf_report'])) {
                
                // 1. Obtener nombre del torneo para la carpeta
                $tId = !empty($cleanData['tournament_id']) ? (int)$cleanData['tournament_id'] : 0;
                $folderName = 'Partidos_Generales'; // Fallback
                
                if ($tId > 0) {
                    $tournRepo = new TournamentRepository(Database::getInstance());
                    $tournData = $tournRepo->getTournamentData($tId);
                    if ($tournData && !empty($tournData['name'])) {
                        $folderName = FileUploader::sanitizeFolderName($tournData['name']);
                    }
                }

                // 2. Modificar la ruta inyectando la carpeta dinámica
                $uploadDir = __DIR__ . '/../../../assets/match_reports/' . $folderName . '/';
                $prefix = "match_{$cleanData['match_id']}_";
                
                $filename = FileUploader::uploadFile($_FILES['pdf_report'], $uploadDir, ['pdf'], $prefix);
                if ($filename) {
                    // 3. Guardar la ruta relativa correcta en la BD
                    $pdfPath = "../assets/match_reports/" . $folderName . "/" . $filename;
                }
            }

            if ($pdfPath) {
                $cleanData['pdf_url'] = $pdfPath;
            }

            $result = $this->repo->syncMatch($cleanData);
            Response::success('Partido sincronizado correctamente', ['real_match_id' => $result['real_match_id']]);

        } catch (Exception $e) {
            Logger::write("Error en sync Match: " . $e->getMessage());
            Response::error($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
?>