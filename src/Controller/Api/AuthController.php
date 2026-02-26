<?php
class AuthController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function login($data) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $user = $data['user'] ?? '';
        $pass = $data['pass'] ?? '';

        if (empty($user) || empty($pass)) {
            Response::json(['status' => 'error', 'message' => 'Usuario y contraseña requeridos'], 400);
        }

        try {
            // TRAEMOS TAMBIÉN EL team_id
            $stmt = $this->db->prepare("SELECT id, username, password, role, team_id FROM admins WHERE username = ?");
            $stmt->bind_param("s", $user);
            $stmt->execute();
            $result = $stmt->get_result();
            $admin = $result->fetch_assoc();

            if ($admin && password_verify($pass, $admin['password'])) {
                
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id']   = $admin['id'];
                $_SESSION['admin_role'] = $admin['role'];
                $_SESSION['admin_user'] = $admin['username'];
                $_SESSION['team_id']    = $admin['team_id']; // FUNDAMENTAL PARA SEGURIDAD

                $update = $this->db->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
                $update->bind_param("i", $admin['id']);
                $update->execute();

                // ENVIAMOS EL ROL AL FRONTEND
                Response::json([
                    'status'  => 'success', 
                    'message' => 'Bienvenido',
                    'role'    => $admin['role'] 
                ]);
            } else {
                Response::json(['status' => 'error', 'message' => 'Credenciales incorrectas'], 401);
            }

        } catch (Exception $e) {
            Response::json(['status' => 'error', 'message' => 'Error interno del servidor'], 500);
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