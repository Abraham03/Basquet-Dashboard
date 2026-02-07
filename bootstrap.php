<?php
/**
 * Bootstrap
 * Responsabilidad: Cargar todos los archivos necesarios en el orden correcto.
 * Esto evita usar Composer si tu hosting es limitado.
 */

// Cargar Configuración
require_once __DIR__ . '/config/DatabaseConfig.php';

// Cargar Core
require_once __DIR__ . '/src/Response.php';
require_once __DIR__ . '/src/Database.php';

// Cargar Repositorios
require_once __DIR__ . '/src/CatalogRepository.php';
require_once __DIR__ . '/src/MatchRepository.php';

// Cargar Controlador
require_once __DIR__ . '/src/ApiController.php';
?>