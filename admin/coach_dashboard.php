<?php
session_start();

// 1. Verificación Estricta de Seguridad (RBAC)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

// 2. Solo permitir acceso a coaches o capitanes
$role = $_SESSION['admin_role'] ?? '';
if ($role !== 'coach' && $role !== 'captain') {
    header("Location: dashboard.php"); 
    exit;
}

// 3. Obtener el ID de su equipo desde la sesión
$team_id = (int)($_SESSION['team_id'] ?? 0);
$user_name = $_SESSION['admin_user'] ?? 'Coach';

// SI NO TIENE EQUIPO, CARGAMOS LA PANTALLA DE ERROR ESTILIZADA Y SALIMOS
if ($team_id === 0) {
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Denegado | Basket Pro</title>
    
    <link rel="icon" type="image/ico" href="../assets/imagenes/favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .error-card { background: white; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); padding: 3rem 2rem; max-width: 450px; text-align: center; border: 1px solid #eef0f3; }
        .error-icon { font-size: 5rem; color: #FF5722; margin-bottom: 1rem; line-height: 1; }
        .error-title { font-weight: 800; color: #1e293b; margin-bottom: 0.5rem; }
        .error-text { color: #64748b; font-size: 0.95rem; margin-bottom: 2rem; }
        .btn-primary { background-color: #FF5722; border: none; font-weight: 600; padding: 0.6rem 1.5rem; border-radius: 50px; transition: all 0.3s; }
        .btn-primary:hover { background-color: #E64A19; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(255, 87, 34, 0.3); }
    </style>
</head>
<body>
    <div class="error-card w-100 mx-3">
        <div class="error-icon"><i class="bi bi-shield-lock-fill"></i></div>
        <h3 class="error-title">Equipo no asignado</h3>
        <p class="error-text">Tu cuenta de usuario no está vinculada a ningún club en este momento. Por favor, contacta a la administración de la liga para que asignen tu equipo.</p>
        <button onclick="logout()" class="btn btn-primary w-100"><i class="bi bi-box-arrow-left me-2"></i>Cerrar Sesión</button>
    </div>
    <script>
        async function logout() { 
            await fetch('../api.php?action=admin_logout'); 
            window.location.href = 'login.php'; 
        }
    </script>
</body>
</html>
<?php
    exit; // Detenemos la ejecución del resto del dashboard
}
// SI TIENE EQUIPO, CONTINÚA CARGANDO EL DASHBOARD NORMALMENTE
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Equipo | Basket Pro</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #FF5722;
            --sidebar-bg: #1e2125;
            --sidebar-width: 280px;
            --bg-body: #f3f4f6;
            --transition-speed: 0.3s;
        }
        
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-body); overflow-x: hidden; }

        /* --- Layout --- */
        #wrapper { display: flex; width: 100%; position: relative; }
        
        #sidebar-wrapper {
            position: fixed; top: 0; left: 0; width: var(--sidebar-width); height: 100vh;
            background-color: var(--sidebar-bg); z-index: 1100;
            transition: transform var(--transition-speed) ease;
            overflow-y: auto; display: flex; flex-direction: column;
        }

        #page-content-wrapper {
            width: 100%; padding-left: var(--sidebar-width);
            transition: padding var(--transition-speed) ease; min-height: 100vh;
        }

        body.toggled #sidebar-wrapper { transform: translateX(calc(-1 * var(--sidebar-width))); }
        body.toggled #page-content-wrapper { padding-left: 0; }

        @media (max-width: 768px) {
            #page-content-wrapper { padding-left: 0; }
            #sidebar-wrapper { transform: translateX(calc(-1 * var(--sidebar-width))); }
            body.toggled #sidebar-wrapper { transform: translateX(0); box-shadow: 10px 0 30px rgba(0,0,0,0.5); }
            #sidebar-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); backdrop-filter: blur(2px); z-index: 1050; }
            body.toggled #sidebar-overlay { display: block; }
        }

        /* --- Sidebar UI --- */
        .sidebar-header { padding: 2rem 1.5rem; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .sidebar-brand { font-size: 1.25rem; font-weight: 800; color: white; letter-spacing: 1px; }
        .nav-link-custom { padding: 0.85rem 1.5rem; color: #adb5bd; font-weight: 500; display: flex; align-items: center; border-radius: 0 50px 50px 0; margin-right: 1.5rem; transition: all 0.2s; text-decoration: none; border-left: 4px solid transparent; }
        .nav-link-custom i { font-size: 1.2rem; margin-right: 15px; width: 25px; text-align: center; }
        .nav-link-custom:hover { background: rgba(255,255,255,0.05); color: white; }
        .nav-link-custom.active { background: rgba(255, 87, 34, 0.15); color: var(--primary-color); border-left-color: var(--primary-color); }
        .logout-btn { margin-top: auto; border-top: 1px solid rgba(255,255,255,0.05); padding: 1.5rem; }

        /* --- TABLAS Y CARDS --- */
        .card { border: none; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
        .table { margin-bottom: 0; }
        .table thead th { background: #f8f9fa; border-top: none; font-size: 0.75rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px; padding: 1rem; }
        .table tbody td { padding: 1rem; vertical-align: middle; border-bottom: 1px solid #f1f1f1; }
        
        .btn-icon { width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; transition: all 0.2s; }
        .btn-icon:hover { background-color: #f1f5f9; transform: translateY(-1px); }
        .btn-action-group { background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 4px; display: inline-flex; }

        /* Perfil del Equipo */
        .team-profile-logo {
            width: 150px; height: 150px; object-fit: contain;
            border-radius: 20px; border: 2px dashed #cbd5e1;
            padding: 10px; background: white; transition: all 0.3s;
        }
        .team-profile-logo:hover { border-color: var(--primary-color); background: #fffaf9; cursor: pointer; }
        .upload-overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.5); border-radius: 20px; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; opacity: 0; transition: 0.3s; cursor: pointer; }
        .logo-container:hover .upload-overlay { opacity: 1; }
    </style>
</head>
<body>

<div id="sidebar-overlay" onclick="toggleMenu()"></div>

<div id="wrapper">
    <div id="sidebar-wrapper">
        <div class="sidebar-header">
            <span class="sidebar-brand"><i class="bi bi-basket2-fill text-warning me-2"></i>BASKET PRO</span>
            <button class="btn btn-link text-white d-md-none p-0" onclick="toggleMenu()"><i class="bi bi-x-lg fs-4"></i></button>
        </div>
        <div class="nav flex-column mt-4">
            <h6 class="text-uppercase text-muted fw-bold px-4 mb-3" style="font-size: 0.75rem;">Panel del Coach</h6>
            <a href="#" class="nav-link-custom active" data-bs-toggle="pill" data-bs-target="#tab-my-team">
                <i class="bi bi-shield-shaded"></i> Perfil del Equipo
            </a>
            <a href="#" class="nav-link-custom" data-bs-toggle="pill" data-bs-target="#tab-my-players">
                <i class="bi bi-people"></i> Mis Jugadores
            </a>
        </div>
        <div class="logout-btn">
            <button class="btn btn-outline-danger w-100 border-0 text-start ps-3" onclick="logout()">
                <i class="bi bi-box-arrow-left me-2"></i> Cerrar Sesión
            </button>
        </div>
    </div>

    <div id="page-content-wrapper">
        <nav class="navbar navbar-expand navbar-light bg-white border-bottom px-4 py-3 sticky-top shadow-sm">
            <button class="btn btn-light rounded-circle shadow-sm me-3" id="menu-toggle"><i class="bi bi-list fs-4"></i></button>
            <h5 class="mb-0 fw-bold d-none d-sm-block">Gestión Deportiva</h5>
            <div class="ms-auto d-flex align-items-center gap-3">
                <span class="fw-bold text-muted small">Hola, <?php echo htmlspecialchars($user_name); ?></span>
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 40px; height: 40px; font-weight: 700;">C</div>
            </div>
        </nav>

        <div class="container-fluid p-4 max-w-1200" style="max-width: 1200px;">
            <div class="tab-content">
                
                <div class="tab-pane fade show active" id="tab-my-team">
                    <div class="mb-4">
                        <h3 class="fw-bold mb-1" id="displayTeamName">Cargando Equipo...</h3>
                        <p class="text-muted mb-0">Actualiza la identidad e información general de tu club.</p>
                    </div>

                    <div class="card p-4 p-md-5">
                        <form id="formMyTeam" enctype="multipart/form-data">
                            <input type="hidden" name="id" value="<?php echo $team_id; ?>">
                            
                            <div class="row align-items-center">
                                <div class="col-md-4 col-lg-3 text-center mb-4 mb-md-0">
                                    <div class="position-relative d-inline-block logo-container shadow-sm rounded-4" onclick="document.getElementById('logoInput').click()">
                                        <img id="previewLogo" src="https://placehold.co/150?text=Logo" class="team-profile-logo">
                                        <div class="upload-overlay"><i class="bi bi-camera fs-2"></i></div>
                                    </div>
                                    <input type="file" name="logo" id="logoInput" class="d-none" accept="image/*" onchange="previewImage(this)">
                                    <p class="small text-muted mt-2 mb-0">Click para cambiar logo</p>
                                </div>
                                
                                <div class="col-md-8 col-lg-9">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <div class="form-floating">
                                                <input type="text" name="name" id="team_name" class="form-control fw-bold fs-5" placeholder="Nombre" required>
                                                <label>Nombre Oficial del Equipo</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="text" name="shortName" id="team_short" class="form-control text-uppercase" placeholder="Abrev" maxlength="5">
                                                <label>Abreviatura (Ej: LAL)</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="text" name="coachName" id="team_coach" class="form-control" placeholder="Entrenador" value="<?php echo htmlspecialchars($user_name); ?>">
                                                <label>Entrenador Principal</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-end mt-4 pt-3 border-top">
                                        <button type="submit" class="btn btn-primary px-5 rounded-pill shadow-sm" id="btnSaveTeam">
                                            <i class="bi bi-check2-circle me-2"></i>Guardar Cambios
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-my-players">
                    <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-3">
                        <div>
                            <h3 class="fw-bold mb-1">Mis Jugadores</h3>
                            <p class="text-muted mb-0">Administra el roster o plantilla de tu equipo.</p>
                        </div>
                        <button class="btn btn-primary rounded-pill px-4 shadow" onclick="openPlayerModal()">
                            <i class="bi bi-person-plus-fill me-2"></i>Agregar Jugador
                        </button>
                    </div>
                    
                    <div class="card overflow-hidden">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead><tr><th class="ps-4">Número de jugador</th><th>Jugador</th><th class="text-end pe-4">Acciones</th></tr></thead>
                                <tbody id="tableMyPlayers">
                                    </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalPlayer" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form id="formPlayer" class="modal-content border-0 shadow-lg" enctype="multipart/form-data">
            <input type="hidden" name="id" id="player_id">
            <input type="hidden" name="teamId" value="<?php echo $team_id; ?>">
            
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="titlePlayer">Registrar Jugador</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            
            <div class="modal-body p-4">
                <div class="d-flex align-items-center mb-4 p-3 bg-light rounded-3 border">
                    <div class="me-3">
                        <img id="previewPlayerPhoto" src="https://placehold.co/80?text=Foto" class="rounded-circle object-fit-cover shadow-sm border" style="width: 80px; height: 80px;">
                    </div>
                    <div class="flex-grow-1">
                        <label class="form-label small text-muted fw-bold mb-1">FOTO DE PERFIL (Opcional)</label>
                        <input type="file" name="photo" id="player_photo_input" class="form-control form-control-sm" accept="image/*" onchange="previewPlayerImage(this)">
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-8 col-md-9">
                        <div class="form-floating">
                            <input type="text" name="name" id="player_name" class="form-control fw-bold" placeholder="Nombre" required>
                            <label>Nombre Completo</label>
                        </div>
                    </div>
                    <div class="col-4 col-md-3">
                        <div class="form-floating">
                            <input type="number" name="number" id="player_num" class="form-control text-center fw-bold fs-5" placeholder="#" min="0" max="99">
                            <label>Número de jugador</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 pb-4 pe-4">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary px-4">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.all.min.js"></script>

<script>
    const API_URL = '../api.php';
    const MY_TEAM_ID = <?php echo $team_id; ?>;
    
    let myTeamData = null;
    let myPlayersData = [];

    document.addEventListener("DOMContentLoaded", () => {
        loadMyTeamData();
        setupForms();

        document.getElementById("menu-toggle").addEventListener("click", (e) => { e.preventDefault(); document.body.classList.toggle("toggled"); });
        const navLinks = document.querySelectorAll('.nav-link-custom');
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                navLinks.forEach(l => l.classList.remove('active'));
                link.classList.add('active');
                if(window.innerWidth <= 768) { document.body.classList.remove("toggled"); }
            });
        });
    });

    function toggleMenu() { document.body.classList.toggle("toggled"); }

    // --- CARGA DE DATOS ---
    async function loadMyTeamData() {
        try {
            const res = await fetch(`${API_URL}?action=get_data`);
            const json = await res.json();
            
            if (json.status === 'success') {
                const allTeams = json.data.teams || [];
                const allPlayers = json.data.players || [];

                myTeamData = allTeams.find(t => t.id == MY_TEAM_ID);
                if (myTeamData) {
                    document.getElementById('displayTeamName').innerText = myTeamData.name;
                    document.getElementById('team_name').value = myTeamData.name;
                    document.getElementById('team_short').value = myTeamData.short_name || '';
                    if(myTeamData.coach_name) document.getElementById('team_coach').value = myTeamData.coach_name;
                    
                    const logoSrc = myTeamData.logo_url ? `../${myTeamData.logo_url}` : 'https://placehold.co/150?text=S/L';
                    document.getElementById('previewLogo').src = logoSrc;
                }

                myPlayersData = allPlayers.filter(p => p.team_id == MY_TEAM_ID);
                renderMyPlayers();
            }
        } catch (e) {
            Swal.fire('Error', 'No se pudieron cargar los datos del equipo.', 'error');
        }
    }

    function renderMyPlayers() {
        const tbody = document.getElementById('tableMyPlayers');
        
        if(myPlayersData.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" class="text-center py-5 text-muted"><i class="bi bi-person-slash fs-1 d-block mb-2 opacity-50"></i>Aún no tienes jugadores registrados.</td></tr>';
            return;
        }

        myPlayersData.sort((a, b) => (a.default_number || 0) - (b.default_number || 0));

        tbody.innerHTML = myPlayersData.map(p => {
            const photoUrl = p.photo_url ? `../${p.photo_url}` : `https://ui-avatars.com/api/?name=${encodeURIComponent(p.name)}&background=random&color=fff`;

            return `
            <tr>
                <td class="ps-4"><span class="badge bg-white text-dark border shadow-sm rounded-pill px-3 py-2 fs-6"># ${p.default_number || '?'}</span></td>
                <td>
                    <div class="d-flex align-items-center">
                        <img src="${photoUrl}" alt="${p.name}" class="rounded-circle me-3 shadow-sm border" style="width: 40px; height: 40px; object-fit: cover;" onerror="this.onerror=null; this.src='https://placehold.co/40?text=U';">
                        <span class="fw-bold text-dark fs-6">${p.name}</span>
                    </div>
                </td>
                <td class="text-end pe-4">
                    <div class="btn-action-group shadow-sm">
                        <button class="btn btn-icon text-secondary" onclick="editPlayer(${p.id})" data-bs-toggle="tooltip" title="Editar Jugador"><i class="bi bi-pencil"></i></button>
                        <div class="vr my-1 mx-1 text-muted opacity-25"></div>
                        <button class="btn btn-icon text-danger" onclick="deletePlayer(${p.id})" data-bs-toggle="tooltip" title="Dar de baja"><i class="bi bi-trash"></i></button>
                    </div>
                </td>
            </tr>
            `;
        }).join('');

        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    }


    // --- GESTIÓN DE FORMULARIOS ---
    function previewImage(input) { 
        if (input.files && input.files[0]) { 
            var reader = new FileReader(); 
            reader.onload = function(e) { document.getElementById('previewLogo').src = e.target.result; }; 
            reader.readAsDataURL(input.files[0]); 
        } 
    }

    // Nuevo Helper para la foto del jugador
    function previewPlayerImage(input) {
        if (input.files && input.files[0]) { 
            var reader = new FileReader(); 
            reader.onload = function(e) { document.getElementById('previewPlayerPhoto').src = e.target.result; }; 
            reader.readAsDataURL(input.files[0]); 
        } 
    }

    function openPlayerModal() {
        const form = document.getElementById('formPlayer');
        form.reset();
        document.getElementById('player_id').value = '';
        document.getElementById('previewPlayerPhoto').src = 'https://placehold.co/80?text=Foto'; // Resetear visual de foto
        document.getElementById('titlePlayer').innerText = 'Registrar Jugador';
        new bootstrap.Modal(document.getElementById('modalPlayer')).show();
    }

    function editPlayer(id) {
        const p = myPlayersData.find(x => x.id == id);
        if(!p) return;
        
        document.getElementById('player_id').value = p.id;
        document.getElementById('player_name').value = p.name;
        document.getElementById('player_num').value = p.default_number;

        // Cargar la foto actual en el modal
        const img = document.getElementById('previewPlayerPhoto');
        if(img) {
            img.src = p.photo_url ? `../${p.photo_url}` : 'https://placehold.co/80?text=Foto';
        }

        document.getElementById('titlePlayer').innerText = 'Editar Jugador';
        new bootstrap.Modal(document.getElementById('modalPlayer')).show();
    }

    function setupForms() {
        // 1. Guardar Perfil del Equipo
        document.getElementById('formMyTeam').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btnSaveTeam');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';

            const formData = new FormData(e.target);
            formData.append('tournament_id', 0); 

            try {
                const res = await fetch(`${API_URL}?action=update_team`, { method: 'POST', body: formData });
                const json = await res.json();
                
                if(json.status === 'success') {
                    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Perfil de equipo actualizado', showConfirmButton: false, timer: 3000 });
                    loadMyTeamData();
                } else {
                    Swal.fire('Error', json.message, 'error');
                }
            } catch (err) { Swal.fire('Error', 'Fallo de conexión.', 'error'); }
            finally { btn.disabled = false; btn.innerHTML = originalText; }
        });

        // 2. Guardar Jugador
        document.getElementById('formPlayer').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const isUpdate = formData.get('id');
            const action = isUpdate ? 'update_player' : 'add_player';

            try {
                const res = await fetch(`${API_URL}?action=${action}`, { method: 'POST', body: formData });
                const json = await res.json();
                
                if(json.status === 'success') {
                    bootstrap.Modal.getInstance(document.getElementById('modalPlayer')).hide();
                    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: isUpdate ? 'Jugador actualizado' : 'Jugador registrado', showConfirmButton: false, timer: 2000 });
                    loadMyTeamData(); 
                } else {
                    Swal.fire('Error', json.message, 'error');
                }
            } catch (err) { Swal.fire('Error', 'Fallo al procesar jugador.', 'error'); }
        });
    }

    async function deletePlayer(id) {
        const result = await Swal.fire({
            title: '¿Dar de baja al jugador?',
            text: "Esta acción lo eliminará de tu plantilla actual.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        });

        if (result.isConfirmed) {
            try {
                const res = await fetch(`${API_URL}?action=delete_player`, { 
                    method: 'POST', 
                    headers: {'Content-Type': 'application/json'}, 
                    body: JSON.stringify({id}) 
                });
                const json = await res.json();
                
                if(json.status === 'success') {
                    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Jugador eliminado', showConfirmButton: false, timer: 2000 });
                    loadMyTeamData();
                } else {
                    Swal.fire('Error', json.message, 'error');
                }
            } catch (e) { Swal.fire('Error', 'Hubo un problema de conexión.', 'error'); }
        }
    }

    async function logout() { 
        const result = await Swal.fire({
            title: '¿Cerrar sesión?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#FF5722',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, salir'
        });

        if(result.isConfirmed) { 
            await fetch('../api.php?action=admin_logout'); 
            window.location.href = 'login.php'; 
        } 
    }
</script>
</body>
</html>