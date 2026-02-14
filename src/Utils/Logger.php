<?php
class Logger {
    // Nombre del archivo de log en la raíz de la app
    const LOG_FILE = __DIR__ . '/../../debug_log.txt';

    public static function write($message, $data = null) {
        // Configura la zona horaria (opcional, ajusta a tu país)
        date_default_timezone_set('America/Mexico_City');
        
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] $message";

        if ($data !== null) {
            // Convierte arrays/objetos a texto para leerlos bien
            $logEntry .= " | DATA: " . print_r($data, true);
        }

        $logEntry .= "\n-----------------------------------\n";

        // Escribe en el archivo (FILE_APPEND para no borrar lo anterior)
        file_put_contents(self::LOG_FILE, $logEntry, FILE_APPEND);
    }
}
?>