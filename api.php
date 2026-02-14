<?php
require_once __DIR__ . '/bootstrap.php';

// Configuración de errores (Desactiva en Producción)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Iniciar Router
$router = new Router();
$router->handleRequest();
?>