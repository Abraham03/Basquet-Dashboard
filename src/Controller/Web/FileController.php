<?php
class FileController extends BaseController {

    public function getFiles($folder) {
        $cleanFolder = str_replace(['../', '..\\', './', '.\\'], '', $folder);
        $cleanFolder = trim($cleanFolder, '/');
        
        // 1. Definimos la ruta física esperada
        $expectedPath = __DIR__ . '/../../../assets/' . $cleanFolder;
        
        // 2. LA MAGIA: Si la carpeta solicitada no existe, la creamos automáticamente
        if (!is_dir($expectedPath)) {
            @mkdir($expectedPath, 0755, true);
        }
        
        // 3. Ahora validamos las rutas reales (realpath ya no fallará porque la carpeta existe)
        $targetDir = realpath($expectedPath);
        $baseAssets = realpath(__DIR__ . '/../../../assets');

        // 4. Medida de seguridad y formateo estricto del JSON por si algo falla
        if (!$targetDir || strpos($targetDir, $baseAssets) !== 0 || !is_dir($targetDir)) {
            // Forzamos manualmente la respuesta con un array 'data' vacío para no romper el Javascript
            echo json_encode([
                'status' => 'success', 
                'message' => 'Directorio vacío o recién creado', 
                'data' => []
            ]);
            exit;
        }

        $items = [];
        $files = scandir($targetDir);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;

            $filePath = $targetDir . '/' . $file;
            $isDir = is_dir($filePath);
            $relPath = $cleanFolder . '/' . $file;

            $items[] = [
                'name' => $file,
                'is_dir' => $isDir,
                'path' => $relPath,
                'size' => $isDir ? '-' : $this->formatSize(filesize($filePath)),
                'date' => date("d/m/Y H:i", filemtime($filePath)),
                'url' => '../assets/' . $relPath
            ];
        }

        usort($items, function($a, $b) {
            if ($a['is_dir'] && !$b['is_dir']) return -1;
            if (!$a['is_dir'] && $b['is_dir']) return 1;
            return strcasecmp($a['name'], $b['name']);
        });

        // 5. Si la carpeta está recién creada, $items estará vacío y devolverá un arreglo válido
        if (empty($items)) {
            echo json_encode([
                'status' => 'success', 
                'message' => 'Carpeta vacía', 
                'data' => []
            ]);
            exit;
        }

        Response::success("Archivos obtenidos", $items);
    }

    public function deleteFile($input) {
        // Soporta recibir un string ('path') o un array ('paths')
        $paths = isset($input['paths']) && is_array($input['paths']) ? $input['paths'] : [];
        if (isset($input['path']) && !empty($input['path'])) {
            $paths[] = $input['path'];
        }

        if (empty($paths)) {
            Response::error("No se enviaron archivos para eliminar.");
        }

        $baseAssets = realpath(__DIR__ . '/../../../assets');
        $deletedCount = 0;

        foreach ($paths as $path) {
            $cleanPath = str_replace(['../', '..\\', './', '.\\'], '', $path);
            $targetPath = realpath(__DIR__ . '/../../../assets/' . $cleanPath);

            // Validar que exista y esté dentro de la carpeta assets permitida
            if ($targetPath && strpos($targetPath, $baseAssets) === 0) {
                if (is_dir($targetPath)) {
                    $this->deleteDirectory($targetPath);
                    $deletedCount++;
                } else if (is_file($targetPath)) {
                    unlink($targetPath);
                    $deletedCount++;
                }
            }
        }

        if ($deletedCount > 0) {
            Response::success("$deletedCount elemento(s) eliminado(s) con éxito");
        } else {
            Response::error("No se pudo eliminar ningún archivo (puede que ya no existan).");
        }
    }

    // Helper para borrar carpetas completas de manera recursiva
    private function deleteDirectory($dir) {
        if (!file_exists($dir)) return true;
        if (!is_dir($dir)) return unlink($dir);
        
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') continue;
            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) return false;
        }
        return rmdir($dir);
    }

    private function formatSize($bytes) {
        if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
        return $bytes . ' bytes';
    }
    
    public function uploadRolImage() {
        try {
            // Construimos la ruta hacia assets/imagenes/imagenesRol
            $baseAssets = realpath(__DIR__ . '/../../../assets');
            $targetDir = $baseAssets . '/imagenes/imagenesRol/';
            
            // Crea la carpeta automáticamente si no existe
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            // Usamos tu FileUploader existente para subir la imagen de forma segura
            $filename = FileUploader::uploadImage($_FILES['file'], $targetDir, 'rol_bg_');
            
            Response::success("Imagen subida con éxito", [
                'filename' => $filename, 
                'url' => '../assets/imagenes/imagenesRol/' . $filename
            ]);
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
}
?>