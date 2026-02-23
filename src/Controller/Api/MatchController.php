<?php
class MatchController extends BaseController {
    private $repo;

    public function __construct() {
        $this->repo = new MatchRepository(Database::getInstance());
    }

    public function sync($data) {
        // 1. Sanitizar entrada
        $cleanData = $this->sanitize($data);

        // 2. Validación estricta
        // match_id es string (si es partido rápido en Flutter, genera un UUID temporal)
        $this->validate($cleanData, [
            'match_id'  => 'required', 
            'team_a_id' => 'required|integer',
            'team_b_id' => 'required|integer',
            'score_a'   => 'required|numeric',
            'score_b'   => 'required|numeric'
        ]);

        // 3. Convertir a mayúsculas los campos de texto
        $cleanData['team_a_name']  = isset($cleanData['team_a_name'])  ? mb_strtoupper($cleanData['team_a_name'], 'UTF-8') : '';
        $cleanData['team_b_name']  = isset($cleanData['team_b_name'])  ? mb_strtoupper($cleanData['team_b_name'], 'UTF-8') : '';
        $cleanData['main_referee'] = isset($cleanData['main_referee']) ? mb_strtoupper($cleanData['main_referee'], 'UTF-8') : '';
        $cleanData['aux_referee']  = isset($cleanData['aux_referee'])  ? mb_strtoupper($cleanData['aux_referee'], 'UTF-8') : '';
        $cleanData['scorekeeper']  = isset($cleanData['scorekeeper'])  ? mb_strtoupper($cleanData['scorekeeper'], 'UTF-8') : '';

        // Restaurar eventos y firmas (pueden haberse alterado por el sanitize)
        $cleanData['events'] = $data['events'] ?? [];
        $cleanData['signature_base64'] = $data['signature_base64'] ?? null;

        try {
            // 4. Manejo del PDF usando FileUploader
            $pdfPath = null;
            if (isset($_FILES['pdf_report'])) {
                $uploadDir = __DIR__ . '/../../../assets/match_reports/';
                // Generar un prefijo seguro y descriptivo
                $prefix = "match_{$cleanData['match_id']}_";
                
                $filename = FileUploader::uploadFile($_FILES['pdf_report'], $uploadDir, ['pdf'], $prefix);
                if ($filename) {
                    $pdfPath = "../assets/match_reports/" . $filename;
                }
            }

            if ($pdfPath) {
                $cleanData['pdf_url'] = $pdfPath;
            }

            // 5. Enviar a Repositorio
            $result = $this->repo->syncMatch($cleanData);

            // 6. Retornar éxito
            Response::success('Partido sincronizado correctamente', ['real_match_id' => $result['real_match_id']]);

        } catch (Exception $e) {
            Logger::write("Error en sync Match: " . $e->getMessage());
            Response::error($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
?>