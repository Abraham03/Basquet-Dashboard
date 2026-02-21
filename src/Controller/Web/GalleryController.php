<?php
class GalleryController {
    // Ruta donde se guardarán las fotos del carrusel
    private $targetDir = __DIR__ . '/../../../assets/imagenes/slider/';
    // Ruta pública que devolveremos al front (relativa al index.html)
    private $publicDir = 'assets/imagenes/slider/';

    public function __construct() {
        if (!is_dir($this->targetDir)) {
            mkdir($this->targetDir, 0755, true);
        }
    }

    public function getImages() {
        $images = [];
        $files = scandir($this->targetDir);
        
        foreach ($files as $file) {
            $path = $this->targetDir . $file;
            // Solo tomar archivos que sean imágenes
            if (is_file($path) && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $file)) {
                $images[] = [
                    'filename' => $file,
                    'url' => $this->publicDir . $file
                ];
            }
        }
        Response::json(['status' => 'success', 'data' => $images]);
    }

    public function uploadImage() {
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            Response::json(['status' => 'error', 'message' => 'No se recibió ninguna imagen válida'], 400);
        }

        $fileInfo = pathinfo($_FILES['image']['name']);
        $ext = strtolower($fileInfo['extension']);
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array($ext, $allowed)) {
            Response::json(['status' => 'error', 'message' => 'Formato no permitido'], 400);
        }

        // Nombre único
        $newFilename = uniqid('slide_') . '.' . $ext;
        $destination = $this->targetDir . $newFilename;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
            Response::json(['status' => 'success', 'message' => 'Imagen subida']);
        } else {
            Response::json(['status' => 'error', 'message' => 'Error al guardar la imagen en el servidor'], 500);
        }
    }

    public function deleteImage($input) {
        if (empty($input['filename'])) {
            Response::json(['status' => 'error', 'message' => 'Nombre de archivo requerido'], 400);
        }

        $filename = basename($input['filename']); // Basename evita ataques de salto de directorio
        $filePath = $this->targetDir . $filename;

        if (file_exists($filePath)) {
            unlink($filePath);
            Response::json(['status' => 'success', 'message' => 'Imagen eliminada']);
        } else {
            Response::json(['status' => 'error', 'message' => 'El archivo no existe'], 404);
        }
    }
}
?>