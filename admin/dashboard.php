<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Basket Pro | Admin Dashboard</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <style>
        :root {
            --primary-color: #FF5722;
            --sidebar-bg: #1e2125;
            --sidebar-width: 280px;
            --bg-body: #f3f4f6;
            --transition-speed: 0.3s;
        }
        
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-body); overflow-x: hidden; }

        /* --- Estructura Principal --- */
        #wrapper {
            display: flex;
            width: 100%;
            position: relative;
        }

        /* --- Sidebar Modernizado --- */
        #sidebar-wrapper {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background-color: var(--sidebar-bg);
            z-index: 1100;
            transition: transform var(--transition-speed) ease;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        /* --- Contenido Principal --- */
        #page-content-wrapper {
            width: 100%;
            padding-left: var(--sidebar-width);
            transition: padding var(--transition-speed) ease;
            min-height: 100vh;
        }

        /* --- Lógica Toggle Desktop/Tablet --- */
        body.toggled #sidebar-wrapper {
            transform: translateX(calc(-1 * var(--sidebar-width)));
        }
        body.toggled #page-content-wrapper {
            padding-left: 0;
        }

        /* --- RESPONSIVIDAD MÓVIL (Off-canvas) --- */
        @media (max-width: 768px) {
            #page-content-wrapper {
                padding-left: 0;
            }
            #sidebar-wrapper {
                transform: translateX(calc(-1 * var(--sidebar-width)));
            }
            /* En móvil, toggled significa MOSTRAR encima */
            body.toggled #sidebar-wrapper {
                transform: translateX(0);
                box-shadow: 10px 0 30px rgba(0,0,0,0.5);
            }
            #sidebar-overlay {
                display: none;
                position: fixed;
                top: 0; left: 0; width: 100%; height: 100%;
                background: rgba(0,0,0,0.4);
                backdrop-filter: blur(2px);
                z-index: 1050;
            }
            body.toggled #sidebar-overlay {
                display: block;
            }
        }

        /* --- Sidebar UI Elements --- */
        .sidebar-header {
            padding: 2rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .sidebar-brand { font-size: 1.25rem; font-weight: 800; color: white; letter-spacing: 1px; }
        
        .nav-link-custom {
            padding: 0.85rem 1.5rem;
            color: #adb5bd;
            font-weight: 500;
            display: flex;
            align-items: center;
            border-radius: 0 50px 50px 0;
            margin-right: 1.5rem;
            transition: all 0.2s;
            text-decoration: none;
            border-left: 4px solid transparent;
        }
        .nav-link-custom i { font-size: 1.2rem; margin-right: 15px; width: 25px; text-align: center; }
        .nav-link-custom:hover { background: rgba(255,255,255,0.05); color: white; }
        .nav-link-custom.active { 
            background: rgba(255, 87, 34, 0.15); 
            color: var(--primary-color); 
            border-left-color: var(--primary-color);
        }

        .logout-btn {
            margin-top: auto;
            border-top: 1px solid rgba(255,255,255,0.05);
            padding: 1.5rem;
        }

        /* --- UI de Tablas Profesional --- */
        .card { border: none; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
        .table thead th { background: #f8f9fa; border-top: none; font-size: 0.75rem; color: #8898aa; text-transform: uppercase; }
        .team-logo-table { width: 44px; height: 44px; object-fit: contain; background: white; border-radius: 10px; border: 1px solid #eee; padding: 4px; }
        .team-name-text { font-weight: 600; color: #32325d; }
    </style>
</head>
<body>

<div id="sidebar-overlay" onclick="toggleMenu()"></div>

<div id="wrapper">
    
    <div id="sidebar-wrapper">
        <div class="sidebar-header">
            <span class="sidebar-brand"><i class="bi bi-basket2-fill text-warning me-2"></i>BASKET PRO</span>
            <button class="btn btn-link text-white d-md-none p-0" onclick="toggleMenu()">
                <i class="bi bi-x-lg fs-4"></i>
            </button>
        </div>
        
        <div class="nav flex-column mt-3">
            <a href="#" class="nav-link-custom active" data-bs-toggle="pill" data-bs-target="#tab-tournaments">
                <i class="bi bi-trophy"></i> Torneos
            </a>
            <a href="#" class="nav-link-custom" data-bs-toggle="pill" data-bs-target="#tab-teams">
                <i class="bi bi-shield-shaded"></i> Equipos
            </a>
            <a href="#" class="nav-link-custom" data-bs-toggle="pill" data-bs-target="#tab-players">
                <i class="bi bi-people"></i> Jugadores
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
            <button class="btn btn-light rounded-circle shadow-sm me-3" id="menu-toggle">
                <i class="bi bi-list fs-4"></i>
            </button>
            <h5 class="mb-0 fw-bold d-none d-sm-block">Panel de Administración</h5>
            
            <div class="ms-auto d-flex align-items-center">
                <div class="text-end me-3 d-none d-md-block">
                    <p class="mb-0 small fw-bold text-dark">Administrador</p>
                    <p class="mb-0 x-small text-muted">admin@basketpro.com</p>
                </div>
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 40px; height: 40px; font-weight: 700;">
                    A
                </div>
            </div>
        </nav>

        <div class="container-fluid p-4">
            
            <div class="filter-container d-flex align-items-center p-3 mb-4 shadow-sm border-0">
                <div class="bg-primary bg-opacity-10 p-2 rounded-3 text-primary me-3">
                    <i class="bi bi-funnel-fill fs-5"></i>
                </div>
                <div class="flex-grow-1" style="max-width: 350px;">
                    <label class="x-small text-muted fw-bold mb-1 d-block">FILTRAR POR TORNEO</label>
                    <select id="dashboardFilterTournament" class="form-select border-0 bg-light fw-600" onchange="filterDashboard()">
                        <option value="0">Cargando...</option>
                    </select>
                </div>
            </div>

            <div class="tab-content">
                
                <div class="tab-pane fade show active" id="tab-tournaments">
                    <div class="d-flex justify-content-between align-items-end mb-4">
                        <div>
                            <h3 class="fw-bold mb-1">Torneos</h3>
                            <p class="text-muted mb-0">Gestión global de competiciones</p>
                        </div>
                        <button class="btn btn-primary rounded-pill px-4 shadow" onclick="openModal('modalTournament')">
                            <i class="bi bi-plus-lg me-2"></i>Nuevo Torneo
                        </button>
                    </div>
                    <div class="card overflow-hidden">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead><tr><th class="ps-4">ID</th><th>Nombre</th><th>Categoría</th><th class="text-end pe-4">Acciones</th></tr></thead>
                                <tbody id="tableTournaments"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-teams">
                    <div class="d-flex justify-content-between align-items-end mb-4">
                        <div>
                            <h3 class="fw-bold mb-1">Equipos</h3>
                            <p class="text-muted mb-0" id="teamSubtitle">Explora los clubes inscritos</p>
                        </div>
                        <button class="btn btn-primary rounded-pill px-4 shadow" onclick="openModal('modalTeam')">
                            <i class="bi bi-plus-lg me-2"></i>Nuevo Equipo
                        </button>
                    </div>
                    <div class="card overflow-hidden">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead><tr><th class="ps-4">ID</th><th>Equipo</th><th>Coach</th><th class="text-end pe-4">Acciones</th></tr></thead>
                                <tbody id="tableTeams"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-players">
                    <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-3">
                        <div>
                            <h3 class="fw-bold mb-1">Jugadores</h3>
                            <p class="text-muted mb-0" id="playerSubtitle">Directorio de atletas</p>
                        </div>
                        <div class="d-flex gap-2">
                            <select id="filterPlayersByTeam" class="form-select rounded-pill border-0 shadow-sm" style="width: 220px;" onchange="applyLocalPlayerFilter()">
                                <option value="0">Todos los Equipos</option>
                            </select>
                            <button class="btn btn-primary rounded-pill px-4 shadow" onclick="openModal('modalPlayer')">
                                <i class="bi bi-plus-lg me-2"></i>Nuevo Jugador
                            </button>
                        </div>
                    </div>
                    <div class="card overflow-hidden">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead><tr><th class="ps-4">ID</th><th>Nombre</th><th>Dorsal</th><th>Equipo</th><th class="text-end pe-4">Acciones</th></tr></thead>
                                <tbody id="tablePlayers"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTournament" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form id="formTournament" class="modal-content border-0 shadow-lg"><input type="hidden" name="id" id="tourn_id"><div class="modal-header bg-light"><h5 class="modal-title fw-bold" id="titleTournament">Nuevo Torneo</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body p-4"><div class="form-floating mb-3"><input type="text" name="name" id="tourn_name" class="form-control" placeholder="Nombre" required><label>Nombre del Torneo</label></div><div class="form-floating"><input type="text" name="category" id="tourn_cat" class="form-control" placeholder="Categoría"><label>Categoría</label></div></div><div class="modal-footer border-0 pt-0 pb-4 pe-4"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary px-4">Guardar</button></div></form></div></div>

<div class="modal fade" id="modalTeam" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form id="formTeam" class="modal-content border-0 shadow-lg" enctype="multipart/form-data"><input type="hidden" name="id" id="team_id"><div class="modal-header bg-light"><h5 class="modal-title fw-bold" id="titleTeam">Nuevo Equipo</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body p-4"><div class="d-flex align-items-center mb-4 p-3 bg-light rounded-3 border"><div class="me-3"><img id="previewLogo" src="https://placehold.co/80?text=Logo" class="team-logo-table" style="width: 80px; height: 80px;"></div><div class="flex-grow-1"><label class="form-label small text-muted fw-bold mb-1">LOGO</label><input type="file" name="logo" class="form-control form-control-sm" accept="image/*" onchange="previewImage(this)"></div></div><div class="form-floating mb-3"><input type="text" name="name" id="team_name" class="form-control" placeholder="Nombre" required><label>Nombre del Equipo</label></div><div class="row g-2 mb-3"><div class="col-md-6"><div class="form-floating"><input type="text" name="shortName" id="team_short" class="form-control" placeholder="Abrev"><label>Abrev.</label></div></div><div class="col-md-6"><div class="form-floating"><input type="text" name="coachName" id="team_coach" class="form-control" placeholder="Coach"><label>Entrenador</label></div></div></div><div class="form-floating"><select name="tournament_id" id="selectTournamentForTeam" class="form-select"></select><label>Asignar Torneo</label></div></div><div class="modal-footer border-0 pt-0 pb-4 pe-4"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary px-4">Guardar</button></div></form></div></div>

<div class="modal fade" id="modalPlayer" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form id="formPlayer" class="modal-content border-0 shadow-lg"><input type="hidden" name="id" id="player_id"><div class="modal-header bg-light"><h5 class="modal-title fw-bold" id="titlePlayer">Nuevo Jugador</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body p-4"><div class="row g-2 mb-3"><div class="col-9"><div class="form-floating"><input type="text" name="name" id="player_name" class="form-control" placeholder="Nombre" required><label>Nombre Completo</label></div></div><div class="col-3"><div class="form-floating"><input type="number" name="number" id="player_num" class="form-control" placeholder="#"><label>#</label></div></div></div><div class="form-floating"><select name="teamId" id="selectTeamForPlayer" class="form-select" required></select><label>Equipo</label></div></div><div class="modal-footer border-0 pt-0 pb-4 pe-4"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary px-4">Guardar</button></div></form></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/admin-crud.js"></script>
<script>
    // Toggle Sidebar Inteligente
    document.getElementById("menu-toggle").addEventListener("click", function(e) {
        e.preventDefault();
        document.body.classList.toggle("toggled");
    });

    function toggleMenu() {
        document.body.classList.toggle("toggled");
    }

    // Estilo activo para los links del sidebar
    const navLinks = document.querySelectorAll('.nav-link-custom');
    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            navLinks.forEach(l => l.classList.remove('active'));
            link.classList.add('active');
            // En móvil, cerramos el menú al hacer click en una opción
            if(window.innerWidth <= 768) {
                document.body.classList.remove("toggled");
            }
        });
    });

    async function logout() {
        if(confirm('¿Cerrar sesión?')) {
            await fetch('../api.php?action=admin_logout');
            window.location.href = 'login.php';
        }
    }

    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) { document.getElementById('previewLogo').src = e.target.result; }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
</body>
</html>