<?php
/**
 * Clase FileUploader
 * Responsabilidad: Manejar la subida de archivos de forma segura.
 */
class FileUploader
{
    /**
     * Sube una imagen y retorna su ruta relativa.
     */
    public static function uploadImage(array $fileArray, string $targetDir, string $prefix = 'img_'): ?string
    {
        if (!isset($fileArray) || $fileArray['error'] !== UPLOAD_ERR_OK) {
            return null; // No se subió archivo o hubo un error
        }

        // Crear carpeta si no existe
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        // Validar extensión segura
        $ext = strtolower(pathinfo($fileArray['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        if (!in_array($ext, $allowed)) {
            throw new Exception("Formato de imagen no permitido.");
        }

        $filename = uniqid($prefix) . '.' . $ext;
        $targetPath = $targetDir . $filename;

        if (move_uploaded_file($fileArray['tmp_name'], $targetPath)) {
            return $filename; // Retornamos solo el nombre para construir la ruta después
        }

        throw new Exception("Error del servidor al guardar la imagen.");
    }

    /**
     * Sube un archivo validando su extensión.
     */
    public static function uploadFile(array $fileArray, string $targetDir, array $allowedExts, string $prefix = 'file_'): ?string {
        if (!isset($fileArray) || $fileArray['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $ext = strtolower(pathinfo($fileArray['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowedExts)) {
            throw new Exception("Formato de archivo no permitido. Solo se aceptan: " . implode(', ', $allowedExts));
        }

        $filename = uniqid($prefix) . '.' . $ext;
        $targetPath = $targetDir . $filename;

        if (move_uploaded_file($fileArray['tmp_name'], $targetPath)) {
            return $filename;
        }

        throw new Exception("Error del servidor al guardar el archivo.");
    }
}
?>