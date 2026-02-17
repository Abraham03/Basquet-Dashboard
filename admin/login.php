<?php
session_start();
// Si ya está logueado, mandar al dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Basket Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        :root {
            --primary-color: #FF5722;
            --primary-hover: #E64A19;
            --bg-gradient: linear-gradient(135deg, #1e2125 0%, #3a3f44 100%);
        }

        body { 
            font-family: 'Inter', sans-serif; 
            background: var(--bg-gradient);
            height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            margin: 0;
        }

        .login-card { 
            width: 100%; 
            max-width: 420px; 
            border: none; 
            border-radius: 20px; 
            box-shadow: 0 15px 35px rgba(0,0,0,0.3); 
            background: white; 
            padding: 2.5rem; 
            margin: 15px;
        }

        .brand-logo {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .form-label {
            font-weight: 600;
            font-size: 0.85rem;
            color: #4a5568;
        }

        .form-control {
            padding: 0.75rem 1rem;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(255, 87, 34, 0.1);
        }

        .btn-primary { 
            background-color: var(--primary-color); 
            border: none; 
            padding: 0.8rem;
            border-radius: 10px;
            font-weight: 700;
            transition: all 0.3s ease;
        }

        .btn-primary:hover { 
            background-color: var(--primary-hover); 
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(255, 87, 34, 0.2);
        }

        .input-group-text {
            background: white;
            border-left: none;
            cursor: pointer;
            color: #a0aec0;
        }

        .password-toggle .form-control {
            border-right: none;
        }

        /* Animación suave para el mensaje de error */
        #errorMsg {
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="text-center mb-4">
            <div class="brand-logo">
                <i class="bi bi-basket2-fill"></i>
            </div>
            <h4 class="fw-bold m-0">Basket Pro</h4>
            <p class="text-muted">Panel de Administración</p>
        </div>

        <div id="errorMsg" class="alert alert-danger d-none small mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <span id="errorText"></span>
        </div>

        <form id="loginForm">
            <div class="mb-3">
                <label class="form-label">Nombre de Usuario</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-person text-muted"></i></span>
                    <input type="text" name="user" class="form-control bg-light border-start-0" placeholder="Ej: admin" required autofocus>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Contraseña</label>
                <div class="input-group password-toggle">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
                    <input type="password" id="passInput" name="pass" class="form-control bg-light border-start-0 border-end-0" placeholder="••••••••" required>
                    <span class="input-group-text bg-light border-start-0" onclick="togglePassword()">
                        <i class="bi bi-eye" id="eyeIcon"></i>
                    </span>
                </div>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary" id="btnSubmit">
                    <span>Iniciar Sesión</span>
                    <div id="spinner" class="spinner-border spinner-border-sm d-none ms-2" role="status"></div>
                </button>
            </div>
        </form>

        <div class="text-center mt-4">
            <small class="text-muted">© 2026 TechSolutions Management</small>
        </div>
    </div>

    <script>
        const API_URL = '../api.php';

        function togglePassword() {
            const passInput = document.getElementById('passInput');
            const eyeIcon = document.getElementById('eyeIcon');
            if (passInput.type === 'password') {
                passInput.type = 'text';
                eyeIcon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                passInput.type = 'password';
                eyeIcon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        }

        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmit');
            const spinner = document.getElementById('spinner');
            const errorDiv = document.getElementById('errorMsg');
            const errorText = document.getElementById('errorText');
            
            // Estado de carga
            btn.disabled = true;
            spinner.classList.remove('d-none');
            errorDiv.classList.add('d-none');

            const formData = new FormData(this);
            const data = {};
            formData.forEach((value, key) => data[key] = value);

            try {
                const response = await fetch(`${API_URL}?action=admin_login`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                if (!response.ok) throw new Error('Error en el servidor');
                
                const result = await response.json();

                if (result.status === 'success') {
                    window.location.href = 'dashboard.php';
                } else {
                    throw new Error(result.message || 'Credenciales incorrectas');
                }

            } catch (error) {
                errorText.innerText = error.message;
                errorDiv.classList.remove('d-none');
                btn.disabled = false;
                spinner.classList.add('d-none');
            }
        });
    </script>
</body>
</html>