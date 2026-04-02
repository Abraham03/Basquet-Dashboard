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
    
    <link rel="icon" type="image/ico" href="../assets/imagenes/favicon.ico">
    
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
        
        #page-content-wrapper {
            width: 100%; padding-left: var(--sidebar-width);
            transition: padding var(--transition-speed) ease; min-height: 100vh;
            display: flex; 
            flex-direction: column;
        }

        /* --- Layout --- */
        #wrapper { display: flex; width: 100%; position: relative; }
        
        #sidebar-wrapper {
            position: fixed; top: 0; left: 0; width: var(--sidebar-width); height: 100vh;
            background-color: var(--sidebar-bg); z-index: 1100;
            transition: transform var(--transition-speed) ease;
            overflow-y: auto; display: flex; flex-direction: column;
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
        .table-hover tbody tr:hover { background-color: #fafafa; }
        .team-logo-table { width: 40px; height: 40px; object-fit: contain; background: white; border-radius: 50%; border: 1px solid #eee; padding: 2px; }
        
        /* Botones de Acción */
        .btn-icon { width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; transition: all 0.2s; }
        .btn-icon:hover { background-color: #f1f5f9; transform: translateY(-1px); }
        .btn-action-group { background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 4px; display: inline-flex; }

        /* Paginación Personalizada */
        .pagination-container { padding: 1rem; display: flex; justify-content: space-between; align-items: center; }
        .page-info { font-size: 0.85rem; color: #6c757d; }
        .btn-page { border: 1px solid #e2e8f0; background: white; color: #1e293b; padding: 0.25rem 0.75rem; border-radius: 6px; font-size: 0.85rem; margin-left: 0.25rem; }
        .btn-page:hover:not(:disabled) { background: #f8f9fa; border-color: #cbd5e1; }
        .btn-page:disabled { opacity: 0.5; cursor: not-allowed; }

        /* Estilos de la Galería */
        .admin-gallery-img { width: 100%; height: 180px; object-fit: cover; border-radius: 12px; border: 1px solid #dee2e6; }
        .img-delete-btn { position: absolute; top: 10px; right: 10px; border-radius: 50%; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0,0,0,0.2); }

        /* --- FOOTER MODERNO DASHBOARD --- */
        .footer-admin {
            margin-top: auto;
            background-color: #ffffff;
            border-top: 1px solid #e2e8f0;
            padding: 1.25rem 0;
        }
        .footer-dev-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: #f8f9fa;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            color: #6c757d;
            border: 1px solid transparent;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .footer-dev-badge:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
            background: #fff5f2;
        }
        /* --- ESTILOS DEL EXPLORADOR DE ARCHIVOS --- */
        .folder-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            padding: 1.5rem;
            height: 100%;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        .folder-card::before {
            content: ''; position: absolute; top: 0; left: 0; width: 4px; height: 100%;
            background: var(--border-color); transition: background-color 0.3s;
        }
        .folder-card:hover { 
            transform: translateY(-4px); 
            box-shadow: var(--hover-shadow); 
            border-color: var(--primary-color);
        }
        .folder-card:hover::before { background: var(--primary-color); }
        
        .file-icon-box {
            width: 56px; height: 56px;
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem;
        }
        .file-path-badge {
            font-size: 0.7rem; font-weight: 700; color: var(--text-muted);
            letter-spacing: 1px; text-transform: uppercase;
            background: var(--badge-bg); padding: 4px 8px; border-radius: 6px;
            display: inline-block; margin-bottom: 12px;
        }
        
        /* Animación para la carga de archivos */
        .fade-in { animation: fadeIn 0.3s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
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
            <a href="#" class="nav-link-custom" data-bs-toggle="pill" data-bs-target="#tab-users">
                <i class="bi bi-person-badge"></i> Usuarios / Accesos
            </a>
            <a href="#" class="nav-link-custom" data-bs-toggle="pill" data-bs-target="#tab-venues">
                <i class="bi bi-geo-alt"></i> Canchas / Sedes
            </a>
            <a href="#" class="nav-link-custom" data-bs-toggle="pill" data-bs-target="#tab-files" onclick="loadFilesDashboard()">
                <i class="bi bi-folder2-open"></i> Archivos / PDF
            </a>
            <a href="#" class="nav-link-custom" data-bs-toggle="pill" data-bs-target="#tab-gallery" onclick="loadGallery()">
                <i class="bi bi-images"></i> Slider Home
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
            <h5 class="mb-0 fw-bold d-none d-sm-block">Panel de Administración</h5>
            <div class="ms-auto d-flex align-items-center">
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 40px; height: 40px; font-weight: 700;">A</div>
            </div>
        </nav>

        <div class="container-fluid p-4">
            
            <div class="filter-container d-flex align-items-center p-3 mb-4 shadow-sm border-0 bg-white rounded-4" id="mainFilterBar">
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
                            <i class="bi bi-plus-lg me-2"></i>Nuevo
                        </button>
                    </div>
                    <div class="card overflow-hidden">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th class="ps-4">ID</th>
                                        <th>Nombre</th>
                                        <th>Categoría</th>
                                        <th>Calendario Partidos</th>
                                        <th class="text-end pe-4">Ajustes</th>
                                    </tr>
                                </thead>
                                <tbody id="tableTournaments"></tbody>
                            </table>
                        </div>
                        <div class="pagination-container border-top" id="paginationTournaments"></div>
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
                            <table class="table table-hover align-middle">
                                <thead><tr><th class="ps-4">ID</th><th>Equipo</th><th>Coach</th><th class="text-end pe-4">Acciones</th></tr></thead>
                                <tbody id="tableTeams"></tbody>
                            </table>
                        </div>
                        <div class="pagination-container border-top" id="paginationTeams"></div>
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
                            <table class="table table-hover align-middle">
                                <thead><tr><th class="ps-4">ID</th><th>Nombre</th><th>Número de jugador</th><th>Equipo</th><th class="text-end pe-4">Acciones</th></tr></thead>
                                <tbody id="tablePlayers"></tbody>
                            </table>
                        </div>
                        <div class="pagination-container border-top" id="paginationPlayers"></div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-gallery">
                    <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-3">
                        <div>
                            <h3 class="fw-bold mb-1">Galería Pública (Slider)</h3>
                            <p class="text-muted mb-0">Administra las fotos de portada de la web pública.</p>
                        </div>
                        <button class="btn btn-primary rounded-pill px-4 shadow" onclick="document.getElementById('uploadGalleryInput').click()">
                            <i class="bi bi-cloud-arrow-up-fill me-2"></i>Subir Imagen
                        </button>
                        <input type="file" id="uploadGalleryInput" accept="image/jpeg, image/png, image/webp" style="display: none;" onchange="uploadGalleryImage(this)">
                    </div>
                    <div class="row g-4" id="galleryContainer"></div>
                </div>
                
                <div class="tab-pane fade" id="tab-files">
                    <div class="d-flex justify-content-between align-items-end mb-4">
                        <div>
                            <h3 class="fw-bold mb-1" id="fileManagerTitle">Archivos y Documentos</h3>
                            <p class="text-muted mb-0" id="fileManagerSubtitle">Explora las actas y reportes generados en el servidor.</p>
                        </div>
                        <div id="fileManagerActions" style="display: none;" class="d-flex gap-2">
                            <button class="btn btn-outline-danger rounded-pill px-3 shadow-sm fw-bold d-none" id="btnDeleteMultiple" onclick="deleteMultipleFiles()">
                                <i class="bi bi-trash me-1"></i>Eliminar (<span id="selectedCount">0</span>)
                            </button>
                            <button class="btn btn-outline-primary rounded-pill px-4 shadow-sm fw-bold" onclick="loadFilesDashboard()" id="btnBackFiles">
                                <i class="bi bi-arrow-left me-2"></i>Volver al Inicio
                            </button>
                        </div>
                    </div>

                    <div class="row g-4 fade-in" id="viewFolders">
                        <div class="col-md-6">
                            <div class="folder-card" style="cursor: pointer;" onclick="openFolder('downloads', 'Reportes de Equipos')">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="file-path-badge">/assets/downloads/</span>
                                        <h4 class="fw-bold mb-2 text-dark">Reportes (ZIP)</h4>
                                        <p class="text-muted small mb-0">Carpetas comprimidas descargadas por los usuarios.</p>
                                    </div>
                                    <div class="file-icon-box text-success" style="background-color: rgba(16, 185, 129, 0.1);">
                                        <i class="bi bi-file-earmark-zip-fill"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="folder-card" style="cursor: pointer;" onclick="openFolder('match_reports', 'Actas de Partidos')">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="file-path-badge">/assets/match_reports/</span>
                                        <h4 class="fw-bold mb-2 text-dark">Actas de Juegos</h4>
                                        <p class="text-muted small mb-0">Formatos PDF individuales organizados por torneo.</p>
                                    </div>
                                    <div class="file-icon-box text-danger" style="background-color: rgba(239, 68, 68, 0.1);">
                                        <i class="bi bi-file-earmark-pdf-fill"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card overflow-hidden shadow-sm fade-in" id="viewFiles" style="display: none; border-radius: 16px;">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle table-pro">
                                <thead>
                                    <tr>
                                        <th class="ps-4 py-3" style="width: 40px;">
                                            <input class="form-check-input" type="checkbox" id="selectAllFiles" onchange="toggleSelectAllFiles(this)">
                                        </th>
                                        <th class="py-3">Nombre del Archivo</th>
                                        <th class="py-3">Tamaño</th>
                                        <th class="py-3">Modificado el</th>
                                        <th class="text-end pe-4 py-3">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tableFiles"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="tab-pane fade" id="tab-users">
                    <div class="d-flex justify-content-between align-items-end mb-4">
                        <div>
                            <h3 class="fw-bold mb-1">Usuarios y Accesos</h3>
                            <p class="text-muted mb-0">Administra quién puede acceder al sistema (Coaches/Admins).</p>
                        </div>
                        <button class="btn btn-primary rounded-pill px-4 shadow" onclick="openUserModal()">
                            <i class="bi bi-person-plus-fill me-2"></i>Nuevo Usuario
                        </button>
                    </div>
                    <div class="card overflow-hidden">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th class="ps-4">Usuario</th>
                                        <th>Rol</th>
                                        <th>Equipo Vinculado</th>
                                        <th class="text-end pe-4">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tableUsers"></tbody>
                            </table>
                        </div>
                        <div class="pagination-container border-top" id="paginationUsers"></div>
                    </div>
                </div>
                
                <div class="tab-pane fade" id="tab-venues">
                    <div class="d-flex justify-content-between align-items-end mb-4">
                        <div>
                            <h3 class="fw-bold mb-1">Canchas y Sedes</h3>
                            <p class="text-muted mb-0">Administra los lugares de juego.</p>
                        </div>
                        <button class="btn btn-primary rounded-pill px-4 shadow" onclick="openModal('modalVenue')">
                            <i class="bi bi-plus-lg me-2"></i>Nueva Sede
                        </button>
                    </div>
                    <div class="card overflow-hidden">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th class="ps-4">ID</th>
                                        <th>Nombre de la Sede</th>
                                        <th>Dirección / Ubicación</th>
                                        <th class="text-end pe-4">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tableVenues"></tbody>
                            </table>
                        </div>
                        <div class="pagination-container border-top" id="paginationVenues"></div>
                    </div>
                </div>
                
                <div class="modal fade" id="modalVenue" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form id="formVenue" class="modal-content border-0 shadow-lg">
            <input type="hidden" name="id" id="venue_id">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="titleVenue">Nueva Sede</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="form-floating mb-3">
                    <input type="text" name="name" id="venue_name" class="form-control fw-bold" placeholder="Nombre" required>
                    <label>Nombre de la Sede/Cancha</label>
                </div>
                
                <div class="form-floating">
                    <textarea name="address" id="venue_address" class="form-control" placeholder="Dirección" style="height: 100px"></textarea>
                    <label>Dirección / Notas de ubicación</label>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 pb-4 pe-4">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary px-4">Guardar Sede</button>
            </div>
        </form>
    </div>
</div>

            </div>
        </div>

        <footer class="footer-admin">
            <div class="container-fluid px-4 d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="fw-bold text-dark" style="font-size: 0.95rem;">
                        <i class="bi bi-basket2-fill text-primary me-1"></i> Basket Pro
                    </span>
                    <span class="text-muted" style="font-size: 0.85rem;">&copy; <?php echo date('Y'); ?></span>
                </div>

                <div class="d-flex align-items-center gap-4">
                    <div class="text-muted" style="font-size: 0.8rem;">Versión 1.2</div>
                    <div style="width: 1px; height: 16px; background-color: #e2e8f0;"></div>
                    <a href="https://techsolutions.management/" class="footer-dev-badge" target="_blank" rel="noopener">
                        <i class="bi bi-code-slash text-primary"></i> TechSolutions
                    </a>
                </div>
            </div>
        </footer>

    </div>
</div>

<div class="modal fade" id="modalTournament" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg"> <form id="formTournament" class="modal-content border-0 shadow-lg" enctype="multipart/form-data">
            <input type="hidden" name="id" id="tourn_id">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="titleTournament">Nuevo Torneo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center p-3 bg-light rounded-3 border h-100">
                            <div class="me-3">
                                <img id="previewTournLogo" src="https://placehold.co/80?text=Logo" class="team-logo-table" style="width: 70px; height: 70px; object-fit: contain;">
                            </div>
                            <div class="flex-grow-1">
                                <label class="form-label x-small text-muted fw-bold mb-1">LOGO DEL TORNEO</label>
                                <input type="file" name="logo" class="form-control form-control-sm" accept="image/*" onchange="previewImage(this, 'previewTournLogo')">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-center p-3 bg-light rounded-3 border h-100">
                            <div class="me-3">
                                <img id="previewArbitroLogo" src="https://placehold.co/80?text=Ref" class="team-logo-table" style="width: 70px; height: 70px; object-fit: contain;">
                            </div>
                            <div class="flex-grow-1">
                                <label class="form-label x-small text-muted fw-bold mb-1">LOGO ÁRBITRO / SPONSOR</label>
                                <input type="file" name="arbitro_logo" class="form-control form-control-sm" accept="image/*" onchange="previewImage(this, 'previewArbitroLogo')">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-floating mb-3">
                    <input type="text" name="name" id="tourn_name" class="form-control" placeholder="Nombre" required>
                    <label>Nombre del Torneo</label>
                </div>
                <div class="form-floating">
                    <input type="text" name="category" id="tourn_cat" class="form-control" placeholder="Categoría">
                    <label>Categoría (Ej. LIBRE, 1ra Fuerza)</label>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 pb-4 pe-4">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary px-4">Guardar</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalTeam" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form id="formTeam" class="modal-content border-0 shadow-lg" enctype="multipart/form-data"><input type="hidden" name="id" id="team_id"><div class="modal-header bg-light"><h5 class="modal-title fw-bold" id="titleTeam">Nuevo Equipo</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body p-4"><div class="d-flex align-items-center mb-4 p-3 bg-light rounded-3 border"><div class="me-3"><img id="previewLogo" src="https://placehold.co/80?text=Logo" class="team-logo-table" style="width: 80px; height: 80px;"></div><div class="flex-grow-1"><label class="form-label small text-muted fw-bold mb-1">LOGO</label><input type="file" name="logo" class="form-control form-control-sm" accept="image/*" onchange="previewImage(this)"></div></div><div class="form-floating mb-3"><input type="text" name="name" id="team_name" class="form-control" placeholder="Nombre" required><label>Nombre del Equipo</label></div><div class="row g-2 mb-3"><div class="col-md-6"><div class="form-floating"><input type="text" name="shortName" id="team_short" class="form-control" placeholder="Abrev"><label>Abrev.</label></div></div><div class="col-md-6"><div class="form-floating"><input type="text" name="coachName" id="team_coach" class="form-control" placeholder="Coach"><label>Entrenador</label></div></div></div><div class="form-floating"><select name="tournament_id" id="selectTournamentForTeam" class="form-select"></select><label>Asignar Torneo</label></div></div><div class="modal-footer border-0 pt-0 pb-4 pe-4"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary px-4">Guardar</button></div></form></div></div>

<div class="modal fade" id="modalPlayer" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form id="formPlayer" class="modal-content border-0 shadow-lg" enctype="multipart/form-data">
            <input type="hidden" name="id" id="player_id">
            
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="titlePlayer">Nuevo Jugador</h5>
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

                <div class="row g-2 mb-3">
                    <div class="col-9">
                        <div class="form-floating">
                            <input type="text" name="name" id="player_name" class="form-control fw-bold" placeholder="Nombre" required>
                            <label>Nombre Completo</label>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="form-floating">
                            <input type="number" name="number" id="player_num" class="form-control text-center" placeholder="#">
                            <label>Número</label>
                        </div>
                    </div>
                </div>
                <div class="form-floating">
                    <select name="teamId" id="selectTeamForPlayer" class="form-select" required></select>
                    <label>Equipo</label>
                </div>
            </div>
            
            <div class="modal-footer border-0 pt-0 pb-4 pe-4">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary px-4">Guardar</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalFixtureConfig" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form id="formFixture" class="modal-content border-0 shadow-lg">
            <input type="hidden" name="tournament_id" id="fix_tourn_id">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-magic me-2"></i>Generar Calendario</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-info small mb-4"><i class="bi bi-info-circle me-1"></i> Configura las reglas para generar los enfrentamientos.</div>
                
                <h6 class="fw-bold text-primary mb-3">Formato de Juego</h6>
                <div class="form-floating mb-4">
                    <select name="vueltas" id="fix_vueltas" class="form-select">
                        <option value="1">Una Vuelta (Ida)</option>
                        <option value="2">Doble Vuelta (Ida y Vuelta)</option>
                    </select>
                    <label>Enfrentamientos</label>
                </div>
                
                <h6 class="fw-bold text-primary mb-3">Puntuación Estándar</h6>
                <div class="row g-2 mb-4">
                    <div class="col-4"><div class="form-floating"><input type="number" name="pts_victoria" id="fix_win" class="form-control" value="2" required><label>Victoria</label></div></div>
                    <div class="col-4"><div class="form-floating"><input type="number" name="pts_derrota" id="fix_loss" class="form-control" value="1" required><label>Derrota</label></div></div>
                    <div class="col-4"><div class="form-floating"><input type="number" name="pts_empate" id="fix_draw" class="form-control" value="1" required><label>Empate</label></div></div>
                </div>

                <h6 class="fw-bold text-danger mb-3">Puntos por Ausencia (Forfeit)</h6>
                <div class="row g-2">
                    <div class="col-6"><div class="form-floating"><input type="number" name="pts_forfeit_win" id="fix_forfeit_win" class="form-control" value="2" required><label>Victoria (Presente)</label></div></div>
                    <div class="col-6"><div class="form-floating"><input type="number" name="pts_forfeit_loss" id="fix_forfeit_loss" class="form-control text-danger fw-bold" value="0" required><label>Derrota (Ausente)</label></div></div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 pb-4 pe-4">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary px-4"><i class="bi bi-shuffle me-2"></i>Generar</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalUser" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form id="formUser" class="modal-content border-0 shadow-lg">
            <input type="hidden" name="id" id="user_id">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="titleUser">Nuevo Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-warning small mb-3"><i class="bi bi-shield-lock me-1"></i> El rol "Coach" requiere que selecciones a qué equipo pertenece.</div>
                
                <div class="form-floating mb-3">
                    <input type="text" name="username" id="user_username" class="form-control fw-bold" placeholder="Usuario" required>
                    <label>Nombre de Usuario (Login)</label>
                </div>
                
                <div class="form-floating mb-3">
                    <input type="password" name="password" id="user_password" class="form-control" placeholder="Contraseña">
                    <label>Contraseña <span id="passHelp" class="text-muted">(Dejar vacío para no cambiar)</span></label>
                </div>

                <div class="row g-2">
                    <div class="col-md-6">
                        <div class="form-floating">
                            <select name="role" id="user_role" class="form-select fw-bold text-primary" required onchange="toggleUserTeam()">
                                <option value="superadmin">Super Admin</option>
                                <option value="coach" selected>Coach / Capitán</option>
                            </select>
                            <label>Rol del Usuario</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <select name="team_id" id="user_team_id" class="form-select">
                                <option value="">-- Sin Equipo --</option>
                            </select>
                            <label>Vincular a Equipo</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 pb-4 pe-4">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary px-4">Guardar Usuario</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.all.min.js"></script>
<script src="js/admin-crud.js"></script>

<script>
    document.getElementById("menu-toggle").addEventListener("click", (e) => { e.preventDefault(); document.body.classList.toggle("toggled"); });
    function toggleMenu() { document.body.classList.toggle("toggled"); }

    const navLinks = document.querySelectorAll('.nav-link-custom');
    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            navLinks.forEach(l => l.classList.remove('active'));
            link.classList.add('active');
            if(window.innerWidth <= 768) { document.body.classList.remove("toggled"); }
        });
    });

function previewImage(input, imgElementId = 'previewLogo') 
{ if (input.files && input.files[0]) { var reader = new FileReader(); reader.onload = function(e) { document.getElementById(imgElementId).src = e.target.result; }; reader.readAsDataURL(input.files[0]); } }
</script>
</body>
</html>