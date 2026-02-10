<?php
/**
 * Clase Database
 * Patrón: Singleton
 * Responsabilidad: Gestionar la conexión a MySQL.
 */
class Database {
    private static ?Database $instance = null;
    private mysqli $connection;

    // Constructor privado: Se conecta al instanciarse
    private function __construct() {
        try {
            $this->connection = new mysqli(
                DatabaseConfig::DB_HOST, 
                DatabaseConfig::DB_USER, 
                DatabaseConfig::DB_PASS, 
                DatabaseConfig::DB_NAME
            );

            if ($this->connection->connect_error) {
                throw new Exception("Error de conexión: " . $this->connection->connect_error);
            }

            $this->connection->set_charset("utf8mb4");

        } catch (Exception $e) {
            // Respuesta JSON directa en caso de error crítico de BD
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit;
        }
    }

    // Método estático para obtener LA instancia de la clase Database
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Método público para obtener la conexión mysqli (usado por los repos)
    public function getConnection(): mysqli {
        return $this->connection;
    }
    
    // Evitar clonación
    private function __clone() {}
}
?>