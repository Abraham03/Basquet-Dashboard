<?php
/**
 * Punto de Entrada (Entry Point)
 * Responsabilidad: Iniciar el sistema.
 */

// 1. Cargar dependencias
require_once __DIR__ . '/bootstrap.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$debugMsg = "Peticion recibida: " . date('Y-m-d H:i:s') . " - Metodo: " . $_SERVER['REQUEST_METHOD'] . "\n";
file_put_contents(__DIR__ . '/ALIVE.txt', $debugMsg, FILE_APPEND);
// 2. Instanciar el controlador y procesar la petición
$api = new ApiController();
$api->handleRequest();
?>