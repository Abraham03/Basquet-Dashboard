<?php
class AuthController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function login($data) {
        // Asegurar que la sesión esté iniciada para poder guardar la variable
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $user = $data['user'] ?? '';
        $pass = $data['pass'] ?? '';

        if (empty($user) || empty($pass)) {
            Response::json(['status' => 'error', 'message' => 'Usuario y contraseña requeridos'], 400);
        }

        try {
            // 1. Buscar usuario
            $stmt = $this->db->prepare("SELECT id, username, password, role FROM admins WHERE username = ?");
            $stmt->bind_param("s", $user);
            $stmt->execute();
            $result = $stmt->get_result();
            $admin = $result->fetch_assoc();

            // 2. Verificar Hash
            if ($admin && password_verify($pass, $admin['password'])) {
                
                // 3. Crear Sesión Segura
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_role'] = $admin['role'];
                $_SESSION['admin_user'] = $admin['username'];

                // Actualizar último login (Opcional pero recomendado)
                $update = $this->db->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
                $update->bind_param("i", $admin['id']);
                $update->execute();

                Response::json(['status' => 'success', 'message' => 'Bienvenido']);
            } else {
                // Error genérico por seguridad (para no revelar si existe el usuario)
                Response::json(['status' => 'error', 'message' => 'Credenciales incorrectas'], 401);
            }

        } catch (Exception $e) {
            Response::json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy();
        Response::json(['status' => 'success', 'message' => 'Sesión cerrada']);
    }
}
?>