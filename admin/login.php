<?php
session_start();
// Si ya está logueado, mandar al dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    if ($_SESSION['admin_role'] === 'coach' || $_SESSION['admin_role'] === 'captain') {
        header("Location: coach_dashboard.php");
    } else {
        header("Location: dashboard.php");
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | VanBall</title>
    
    <link rel="icon" type="image/ico" href="../assets/imagenes/favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        :root {
            --primary-color: #FF5722;
            --primary-hover: #E64A19;
            --bg-gradient: linear-gradient(135deg, #16181b 0%, #2c3035 100%);
            --card-bg: #ffffff;
            --text-dark: #1e293b;
            --text-muted: #64748b;
        }

        body { 
            font-family: 'Inter', sans-serif; 
            background: var(--bg-gradient);
            min-height: 100vh; 
            display: flex; 
            flex-direction: column; /* Cambiado para empujar el footer abajo */
            margin: 0;
        }

        /* Contenedor principal que empuja el footer hacia abajo */
        .main-wrapper {
            flex-grow: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .login-card { 
            width: 100%; 
            max-width: 400px; 
            border: none; 
            border-radius: 24px; /* Más redondeado y moderno */
            box-shadow: 0 20px 40px rgba(0,0,0,0.4); /* Sombra más suave y profunda */
            background: var(--card-bg); 
            padding: 2.5rem 2rem; 
        }

        .brand-icon-wrapper {
            width: 70px;
            height: 70px;
            background: rgba(255, 87, 34, 0.1);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem auto;
            color: var(--primary-color);
            font-size: 2.2rem;
        }

        h4.fw-bold {
            color: var(--text-dark);
            letter-spacing: -0.5px;
        }

        .form-label {
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 0.4rem;
        }

        .input-group {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .input-group:focus-within {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(255, 87, 34, 0.15);
        }

        .form-control {
            padding: 0.8rem 1rem;
            border: none; /* El borde lo maneja el input-group */
            background: #f8fafc;
            color: var(--text-dark);
            font-weight: 500;
        }

        .form-control:focus {
            box-shadow: none;
            background: #ffffff;
        }

        .input-group-text {
            background: #f8fafc;
            border: none;
            color: #94a3b8;
            padding-left: 1.2rem;
            padding-right: 0.8rem;
        }

        .input-group-text.clickable {
            cursor: pointer;
            transition: color 0.2s;
        }
        
        .input-group-text.clickable:hover {
            color: var(--primary-color);
        }

        .btn-primary { 
            background-color: var(--primary-color); 
            border: none; 
            padding: 0.85rem;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1rem;
            letter-spacing: 0.3px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(255, 87, 34, 0.3);
        }

        .btn-primary:hover { 
            background-color: var(--primary-hover); 
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(255, 87, 34, 0.4);
        }

        .btn-primary:active {
            transform: translateY(0);
        }
        

        /* Animación suave para el mensaje de error */
        #errorMsg {
            animation: fadeIn 0.3s ease-in-out;
            border-radius: 10px;
            font-weight: 500;
        }

        /* --- ESTILOS DEL FOOTER --- */
        .footer-custom {
            background: rgba(28, 30, 35, 0.85); /* Fondo oscuro semitransparente */
            backdrop-filter: blur(10px); /* Efecto cristal moderno */
            -webkit-backdrop-filter: blur(10px); /* Soporte para Safari */
            border-top: 1px solid rgba(255, 255, 255, 0.08) !important;
            position: sticky;
            bottom: 0;
            z-index: 1020;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

    <div class="main-wrapper">
        <div class="login-card">
            <div class="text-center mb-4">
                <img src="../assets/imagenes/Logo.png" alt="Logo Basket Pro" style="max-height: 85px; width: auto; margin-bottom: 1rem; object-fit: contain;">
                
                <h4 class="fw-bold m-0">VanBall</h4>
                <p class="text-muted small mt-1">Panel de Administración</p>
            </div>

            <div id="errorMsg" class="alert alert-danger d-none small mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <span id="errorText"></span>
            </div>

            <form id="loginForm">
                <div class="mb-3">
                    <label class="form-label">Nombre de Usuario</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" name="user" class="form-control" placeholder="Ej: admin" required autofocus>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" id="passInput" name="pass" class="form-control border-end-0" placeholder="••••••••" required>
                        <span class="input-group-text clickable border-start-0 bg-transparent" onclick="togglePassword()">
                            <i class="bi bi-eye" id="eyeIcon"></i>
                        </span>
                    </div>
                </div>

                <div class="d-grid mt-2">
                    <button type="submit" class="btn btn-primary d-flex justify-content-center align-items-center" id="btnSubmit">
                        <span>Iniciar Sesión</span>
                        <div id="spinner" class="spinner-border spinner-border-sm d-none ms-2" role="status"></div>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <footer class="footer-custom py-4 mt-auto w-100">
        <div class="container px-4 d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
            
            <div class="text-white-50" style="font-size: 0.85rem;">
                &copy; <?php echo date('Y'); ?> Basket Pro. Todos los derechos reservados.
            </div>

            <div class="d-flex align-items-center gap-2">
                <span class="text-white-50" style="font-size: 0.85rem;">Desarrollado por</span>
                <a href="https://techsolutions.management/" class="d-flex align-items-center text-decoration-none" target="_blank" rel="noopener" style="opacity: 0.85; transition: opacity 0.2s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.85'">
                    <img id="techSolutionsLogo" src="../assets/imagenes/logo1.png" alt="Logo TechSolutions" style="height: 20px; width: auto; margin-right: 6px;">
                    <span class="text-white" style="font-weight: 700; font-size: 0.95rem; letter-spacing: -0.3px;">TechSolutions</span>
                </a>
            </div>

        </div>
    </footer>

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
                    // REDIRECCIÓN INTELIGENTE SEGÚN EL ROL
                    if (result.role === 'coach' || result.role === 'captain') {
                        window.location.href = 'coach_dashboard.php';
                    } else {
                        window.location.href = 'dashboard.php';
                    }
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