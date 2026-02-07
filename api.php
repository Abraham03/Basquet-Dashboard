<?php
/**
 * Punto de Entrada (Entry Point)
 * Responsabilidad: Iniciar el sistema.
 */

// 1. Cargar dependencias
require_once __DIR__ . '/bootstrap.php';

// 2. Instanciar el controlador y procesar la petición
$api = new ApiController();
$api->handleRequest();
?>