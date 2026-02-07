<?php
/**
 * Clase Database
 * Patrón: Singleton
 * Responsabilidad: Gestionar una única conexión a MySQL para toda la solicitud.
 */
class Database {
    // Propiedad estática para guardar la instancia única
    private static ?mysqli $connection = null;

    // Constructor privado para evitar que se haga "new Database()" desde fuera
    private function __construct() {}

    /**
     * Obtiene la conexión activa o crea una nueva si no existe.
     * @return mysqli Objeto de conexión nativo de PHP.
     */
    public static function getConnection(): mysqli {
        if (self::$connection === null) {
            try {
                // Intenta conectar usando las constantes de configuración
                self::$connection = new mysqli(
                    DatabaseConfig::DB_HOST, 
                    DatabaseConfig::DB_USER, 
                    DatabaseConfig::DB_PASS, 
                    DatabaseConfig::DB_NAME
                );

                // Verificar si hubo error
                if (self::$connection->connect_error) {
                    throw new Exception("Error de conexión: " . self::$connection->connect_error);
                }

                // Asegurar que los caracteres especiales (tildes, ñ) se guarden bien
                self::$connection->set_charset("utf8mb4");

            } catch (Exception $e) {
                // Si falla, detenemos todo y devolvemos error 500
                Response::json(['status' => 'error', 'message' => 'Database connection error'], 500);
            }
        }
        return self::$connection;
    }
}
?>