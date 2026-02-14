<?php
// 1. Cargar Configuración
require_once __DIR__ . '/config/DatabaseConfig.php';
require_once __DIR__ . '/src/Controller/Api/AuthController.php'; // <--- AGREGAR ESTO

// 2. Autoloader (Carga clases automáticamente)
spl_autoload_register(function ($className) {
    $baseDir = __DIR__ . '/src/';
    $folders = [
        '',                     // 
        'Controller/Api/',
        'Controller/Web/',
        'Repository/',        // Repositories/
        'Utils/',
        'assets/team_logo'// Utils/
    ];

    foreach ($folders as $folder) {
        $file = $baseDir . $folder . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});
?>