<?php
class MatchController {
    private $repo;

    public function __construct() {
        $this->repo = new MatchRepository(Database::getInstance());
    }

public function sync($data) {
        if (empty($data['match_id'])) {
            Response::json(['status' => 'error', 'message' => 'match_id is required'], 400);
        }

        // 1. Manejo del PDF (Si existe en la petición)
        $pdfPath = null;
        if (isset($_FILES['pdf_report']) && $_FILES['pdf_report']['error'] === UPLOAD_ERR_OK) {
            $pdfPath = $this->handlePdfUpload($_FILES['pdf_report'], $data['match_id']);
        }

        // 2. Pasar datos + ruta del PDF al repo
        // Agregamos la ruta al array de datos para que el repo la use
        if ($pdfPath) {
            $data['pdf_url'] = $pdfPath; 
        }

        $result = $this->repo->syncMatch($data);

        if ($result['status'] === 'success') {
            Response::json($result);
        } else {
            Response::json($result, 500);
        }
    }

    private function handlePdfUpload($file, $matchId) {
        // Definir directorio: assets/match_reports/
        $uploadDir = __DIR__ . '/../../../assets/match_reports/';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Nombre seguro: match_123_TIMESTAMP.pdf
        $ext = 'pdf'; // Forzamos PDF
        $filename = "match_{$matchId}_" . time() . ".{$ext}";
        $targetPath = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Retornar ruta relativa para guardar en BD
            return "../assets/match_reports/" . $filename;
        }
        
        Logger::write("Error moviendo PDF subido para match $matchId");
        return null;
    }
}
?>