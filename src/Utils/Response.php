<?php
/**
 * Clase Response
 * Responsabilidad: Estandarizar las respuestas JSON y códigos HTTP.
 */
class Response {
    /**
     * Envía una respuesta JSON y termina la ejecución del script.
     * * @param array $data Los datos a enviar (array asociativo).
     * @param int $code Código HTTP (200 OK, 400 Error, 500 Server Error).
     */
    public static function json(array $data, int $code = 200): void {
        // Limpiamos cualquier salida previa (warnings de PHP, etc.)
        ob_clean(); 
        
        // Establecemos el código HTTP correcto
        http_response_code($code);
        
        // Enviamos el JSON
        echo json_encode($data);
        
        // Matamos el proceso para que no se imprima nada más
        exit();
    }
}
?>