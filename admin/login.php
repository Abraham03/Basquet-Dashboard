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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f3f4f6; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-card { 
            width: 100%; max-width: 400px; 
            border: none; border-radius: 15px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.05); 
            background: white; padding: 2rem; 
        }
        .btn-primary { background-color: #FF5722; border-color: #FF5722; }
        .btn-primary:hover { background-color: #E64A19; border-color: #E64A19; }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="text-center mb-4">
            <h4 class="fw-bold">Basket Pro Admin</h4>
            <p class="text-muted small">Ingresa tus credenciales</p>
        </div>

        <form id="loginForm">
            <div class="mb-3">
                <label class="form-label">Usuario</label>
                <input type="text" name="user" class="form-control" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label">Contraseña</label>
                <input type="password" name="pass" class="form-control" required>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary" id="btnSubmit">
                    Entrar
                </button>
            </div>
            <div id="errorMsg" class="mt-3 text-danger text-center small" style="display:none;"></div>
        </form>
    </div>

    <script>
        const API_URL = '../api.php'; // Sube un nivel para encontrar api.php

        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmit');
            const errorDiv = document.getElementById('errorMsg');
            
            // UI Loading state
            btn.disabled = true;
            btn.innerText = "Verificando...";
            errorDiv.style.display = 'none';

            const formData = new FormData(this);
            // Convertir a objeto JSON
            const data = {};
            formData.forEach((value, key) => data[key] = value);

            try {
                const response = await fetch(`${API_URL}?action=admin_login`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.status === 'success') {
                    // Login correcto, PHP ya creó la cookie de sesión.
                    // Redirigimos al dashboard.
                    window.location.href = 'dashboard.php';
                } else {
                    throw new Error(result.message || 'Error desconocido');
                }

            } catch (error) {
                errorDiv.innerText = error.message;
                errorDiv.style.display = 'block';
                btn.disabled = false;
                btn.innerText = "Entrar";
            }
        });
    </script>
</body>
</html>