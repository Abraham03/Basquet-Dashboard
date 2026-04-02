<?php
class GalleryController {
    // Carpeta base para todos los torneos
    private $baseDir = __DIR__ . '/../../../assets/imagenes/torneos/';

    /**
     * Obtiene la ruta física del slider de un torneo específico y la crea si no existe.
     */
    private function getTournamentPath($tId) {
        $path = $this->baseDir . (int)$tId . '/slider/';
        if (!is_dir($path)) {
            // true en el tercer parámetro permite crear carpetas anidadas (torneos -> ID -> slider)
            mkdir($path, 0755, true);
        }
        return $path;
    }

    public function getImages() {
        $tId = isset($_GET['tournament_id']) ? (int)$_GET['tournament_id'] : 0;
        
        if ($tId <= 0) {
            Response::json(['status' => 'error', 'message' => 'ID de torneo requerido para ver la galería'], 400);
        }

        $targetDir = $this->getTournamentPath($tId);
        $publicPath = "assets/imagenes/torneos/$tId/slider/";
        
        $images = [];
        if (is_dir($targetDir)) {
            $files = scandir($targetDir);
            foreach ($files as $file) {
                if (is_file($targetDir . $file) && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $file)) {
                    $images[] = [
                        'filename' => $file,
                        'url' => $publicPath . $file
                    ];
                }
            }
        }
        Response::json(['status' => 'success', 'data' => $images]);
    }

    public function uploadImage() {
        $tId = isset($_POST['tournament_id']) ? (int)$_POST['tournament_id'] : 0;
        
        if ($tId <= 0) {
            Response::json(['status' => 'error', 'message' => 'ID de torneo requerido para subir imágenes'], 400);
        }

        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            Response::json(['status' => 'error', 'message' => 'No se recibió una imagen válida'], 400);
        }

        // Esta función crea la carpeta automáticamente si no existe
        $targetDir = $this->getTournamentPath($tId);
        
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $newFilename = uniqid('slide_') . '.' . $ext;
        $destination = $targetDir . $newFilename;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
            Response::json(['status' => 'success', 'message' => 'Imagen subida correctamente al torneo ' . $tId]);
        } else {
            Response::json(['status' => 'error', 'message' => 'Error al guardar el archivo en el servidor'], 500);
        }
    }

    public function deleteImage($input) {
        $tId = isset($input['tournament_id']) ? (int)$input['tournament_id'] : 0;
        $filename = basename($input['filename'] ?? '');

        if ($tId <= 0 || empty($filename)) {
            Response::json(['status' => 'error', 'message' => 'Faltan datos para eliminar'], 400);
        }

        $filePath = $this->getTournamentPath($tId) . $filename;

        if (file_exists($filePath)) {
            unlink($filePath);
            Response::json(['status' => 'success', 'message' => 'Imagen eliminada']);
        } else {
            Response::json(['status' => 'error', 'message' => 'El archivo no existe en la carpeta del torneo'], 404);
        }
    }
}