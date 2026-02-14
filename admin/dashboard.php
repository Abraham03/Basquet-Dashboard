<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
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
            --sidebar-width: 260px;
            --bg-body: #f3f4f6;
        }
        
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-body); overflow-x: hidden; }

        /* --- Layout --- */
        #wrapper { display: flex; width: 100%; overflow-x: hidden; }

        #sidebar-wrapper {
            min-width: var(--sidebar-width); max-width: var(--sidebar-width);
            min-height: 100vh; background-color: var(--sidebar-bg);
            transition: margin .25s ease-out; position: fixed; z-index: 1000;
        }

        #page-content-wrapper {
            width: 100%; margin-left: var(--sidebar-width);
            transition: margin .25s ease-out; min-height: 100vh;
        }

        /* --- Sidebar Styles --- */
        .sidebar-brand {
            padding: 1.5rem; font-size: 1.25rem; font-weight: 700; color: white;
            border-bottom: 1px solid rgba(255,255,255,0.1); text-align: center;
        }
        .list-group-item {
            border: none; padding: 1rem 1.5rem; background-color: transparent;
            color: #adb5bd; font-weight: 500; transition: all 0.2s;
        }
        .list-group-item:hover { color: #fff; background-color: rgba(255,255,255,0.05); padding-left: 1.75rem; }
        .list-group-item.active { background-color: var(--primary-color); color: white; border-radius: 0 25px 25px 0; }
        .list-group-item i { width: 24px; text-align: center; margin-right: 10px; }

        /* --- Mobile Responsive --- */
        @media (max-width: 768px) {
            #sidebar-wrapper { margin-left: calc(-1 * var(--sidebar-width)); }
            #page-content-wrapper { margin-left: 0; }
            body.toggled #sidebar-wrapper { margin-left: 0; }
            body.toggled #page-content-wrapper::before {
                content: ""; position: fixed; top: 0; left: 0; right: 0; bottom: 0;
                background: rgba(0,0,0,0.5); z-index: 999;
            }
        }

        /* --- General UI --- */
        .card { border: none; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.04); }
        .filter-container { background: white; border-radius: 12px; padding: 1rem; margin-bottom: 1.5rem; box-shadow: 0 2px 12px rgba(0,0,0,0.04); }
        .col-id { display: none; }
        @media(min-width: 992px) { .col-id { display: table-cell; width: 60px; color: #ced4da; font-size: 0.85rem; } }
        
        /* --- ESTILOS DE TABLA PRO --- */
        .table td {
            vertical-align: middle; /* Centrado vertical perfecto */
            padding: 1rem 0.75rem;
        }
        .table th {
            font-weight: 600; text-transform: uppercase; font-size: 0.75rem; 
            color: #6c757d; background-color: #f9fafb; border-bottom: 1px solid #edf2f7;
        }

        /* Logo Profesional */
        .team-logo-table {
            width: 48px;
            height: 48px;
            object-fit: contain;    /* CRÍTICO: No recorta la imagen, la ajusta */
            background-color: #fff; /* Fondo blanco limpio */
            border: 1px solid #eaecf0; /* Borde sutil gris */
            border-radius: 8px;     /* Bordes redondeados suaves */
            padding: 4px;           /* Espacio interno para que el logo no toque el borde */
        }

        /* Tipografía de Tabla */
        .team-name-text {
            font-weight: 600; color: #101828; font-size: 0.95rem; display: block;
        }
        
        .team-meta-text {
            font-size: 0.75rem; color: #667085; font-weight: 500;
            text-transform: uppercase; letter-spacing: 0.5px;
        }

        /* Botones de acción más limpios */
        .btn-icon {
            width: 32px; height: 32px; 
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 6px; transition: all 0.2s; border: 1px solid transparent; background: transparent;
        }
        .btn-icon:hover { background-color: #f3f4f6; border-color: #d0d5dd; }
    </style>
</head>
<body>

<div id="wrapper">
    
    <div id="sidebar-wrapper">
        <div class="sidebar-brand"><i class="bi bi-basket2-fill text-warning me-2"></i>BASKET PRO</div>
        <div class="list-group list-group-flush my-3 pe-2">
            <a href="#" class="list-group-item list-group-item-action active" data-bs-toggle="pill" data-bs-target="#tab-tournaments"><i class="bi bi-trophy"></i> Torneos</a>
            <a href="#" class="list-group-item list-group-item-action" data-bs-toggle="pill" data-bs-target="#tab-teams"><i class="bi bi-shield-shaded"></i> Equipos</a>
            <a href="#" class="list-group-item list-group-item-action" data-bs-toggle="pill" data-bs-target="#tab-players"><i class="bi bi-people"></i> Jugadores</a>
            <a href="#" class="list-group-item list-group-item-action mt-5 text-danger" onclick="logout()"><i class="bi bi-box-arrow-left"></i> Cerrar Sesión</a>
        </div>
    </div>

    <div id="page-content-wrapper">
        <nav class="navbar navbar-light bg-white border-bottom px-3 py-3 shadow-sm d-md-none">
            <button class="btn btn-light border" id="menu-toggle"><i class="bi bi-list fs-4"></i></button>
            <span class="navbar-brand ms-3 fw-bold">Admin Panel</span>
        </nav>

        <div class="container-fluid px-4 py-4">
            
            <div class="filter-container d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center flex-grow-1">
                    <div class="bg-primary bg-opacity-10 p-2 rounded me-3 text-primary"><i class="bi bi-funnel-fill fs-5"></i></div>
                    <div class="w-100" style="max-width: 400px;">
                        <label class="small text-muted fw-bold mb-1">TORNEO ACTIVO</label>
                        <select id="dashboardFilterTournament" class="form-select border-0 bg-light fw-bold" onchange="filterDashboard()">
                            <option value="0">Cargando...</option>
                        </select>
                    </div>
                </div>
                <div class="text-end d-none d-md-block">
                    <small class="text-muted d-block">Usuario</small>
                    <span class="fw-bold"><?php echo htmlspecialchars($_SESSION['admin_user'] ?? 'Admin'); ?></span>
                </div>
            </div>

            <div class="tab-content">
                
                <div class="tab-pane fade show active" id="tab-tournaments">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="fw-bold mb-0">Gestión de Torneos</h4>
                        <button class="btn btn-primary" onclick="openModal('modalTournament')"><i class="bi bi-plus-lg me-2"></i>Nuevo Torneo</button>
                    </div>
                    <div class="card">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead><tr><th class="col-id">ID</th><th>Nombre</th><th>Categoría</th><th class="text-end pe-4">Acciones</th></tr></thead>
                                <tbody id="tableTournaments"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-teams">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h4 class="fw-bold mb-0">Equipos</h4>
                            <small class="text-muted" id="teamSubtitle">Cargando...</small>
                        </div>
                        <button class="btn btn-primary" onclick="openModal('modalTeam')"><i class="bi bi-plus-lg me-2"></i>Nuevo Equipo</button>
                    </div>
                    <div class="card">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead><tr><th class="col-id">ID</th><th>Equipo</th><th>Coach</th><th class="text-end pe-4">Acciones</th></tr></thead>
                                <tbody id="tableTeams"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-players">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-3 flex-grow-1">
                            <div>
                                <h4 class="fw-bold mb-0">Jugadores</h4>
                                <small class="text-muted" id="playerSubtitle">Cargando...</small>
                            </div>
                            <div class="input-group input-group-sm shadow-sm" style="max-width: 250px;">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-filter"></i></span>
                                <select id="filterPlayersByTeam" class="form-select border-start-0" onchange="applyLocalPlayerFilter()">
                                    <option value="0">Todos los Equipos</option>
                                </select>
                            </div>
                        </div>
                        <button class="btn btn-primary" onclick="openModal('modalPlayer')"><i class="bi bi-plus-lg me-2"></i>Nuevo Jugador</button>
                    </div>
                    <div class="card">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead><tr><th class="col-id">ID</th><th>Nombre</th><th>Dorsal</th><th>Equipo</th><th class="text-end pe-4">Acciones</th></tr></thead>
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

<div class="modal fade" id="modalTeam" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form id="formTeam" class="modal-content border-0 shadow-lg" enctype="multipart/form-data">
            <input type="hidden" name="id" id="team_id">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="titleTeam">Nuevo Equipo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                
                <div class="d-flex align-items-center mb-4 p-3 bg-light rounded-3 border">
                    <div class="me-3">
                        <img id="previewLogo" src="https://placehold.co/80?text=Logo" class="team-logo-table" style="width: 80px; height: 80px;">
                    </div>
                    <div class="flex-grow-1">
                        <label class="form-label small text-muted fw-bold mb-1">LOGO DEL EQUIPO</label>
                        <input type="file" name="logo" class="form-control form-control-sm" accept="image/*" onchange="previewImage(this)">
                        <div class="form-text small">Se recomienda PNG con fondo transparente.</div>
                    </div>
                </div>

                <div class="form-floating mb-3">
                    <input type="text" name="name" id="team_name" class="form-control" placeholder="Nombre" required>
                    <label>Nombre del Equipo</label>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" name="shortName" id="team_short" class="form-control" placeholder="Abrev">
                            <label>Abrev. (3 letras)</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" name="coachName" id="team_coach" class="form-control" placeholder="Coach">
                            <label>Entrenador</label>
                        </div>
                    </div>
                </div>
                <div class="form-floating">
                    <select name="tournament_id" id="selectTournamentForTeam" class="form-select"></select>
                    <label>Inscribir en Torneo (Opcional)</label>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 pb-4 pe-4">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary px-4">Guardar</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalPlayer" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form id="formPlayer" class="modal-content border-0 shadow-lg"><input type="hidden" name="id" id="player_id"><div class="modal-header bg-light"><h5 class="modal-title fw-bold" id="titlePlayer">Nuevo Jugador</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body p-4"><div class="row g-2 mb-3"><div class="col-9"><div class="form-floating"><input type="text" name="name" id="player_name" class="form-control" placeholder="Nombre" required><label>Nombre Completo</label></div></div><div class="col-3"><div class="form-floating"><input type="number" name="number" id="player_num" class="form-control" placeholder="#"><label>#</label></div></div></div><div class="form-floating"><select name="teamId" id="selectTeamForPlayer" class="form-select" required><option value="">Seleccione Equipo...</option></select><label>Equipo</label></div></div><div class="modal-footer border-0 pt-0 pb-4 pe-4"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary px-4">Guardar</button></div></form></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/admin-crud.js"></script>
<script>
    document.getElementById("menu-toggle").onclick = function(e) {
        e.preventDefault(); document.body.classList.toggle("toggled");
    };

    async function logout() {
        if(confirm('¿Cerrar sesión?')) {
            await fetch('../api.php?action=admin_logout');
            window.location.href = 'index.php';
        }
    }

    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('previewLogo').src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
</body>
</html>