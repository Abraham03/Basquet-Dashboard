<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header("Location: login.php"); exit; }
$tournament_id = $_GET['id'] ?? 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rol de juegos | Basket Pro Admin</title>
    
    <link rel="icon" type="image/ico" href="../assets/imagenes/favicon.ico">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary: #FF5722;
            --bg-light: #f3f4f6;
            --card-border: #eef0f3;
        }
        
        /* --- AJUSTES FLEX PARA EL FOOTER STICKY --- */
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: var(--bg-light); 
            color: #1e293b; 
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .main-content {
            flex-grow: 1; /* Esto hace que el contenido empuje al footer hacia abajo */
        }

        /* HEADER STICKY */
        .top-bar {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        /* NAVEGACIÓN JORNADAS */
        .rounds-nav-container {
            position: relative;
            margin-bottom: 1.5rem;
        }
        .nav-pills-scroll {
            display: flex;
            flex-wrap: nowrap;
            overflow-x: auto;
            gap: 10px;
            padding-bottom: 5px;
            -webkit-overflow-scrolling: touch; 
            scrollbar-width: none; 
        }
        .nav-pills-scroll::-webkit-scrollbar { display: none; }
        
        .nav-pills-scroll .nav-link {
            border-radius: 50px;
            padding: 0.5rem 1.2rem;
            color: #64748b;
            background: white;
            border: 1px solid #e2e8f0;
            font-weight: 600;
            font-size: 0.9rem;
            white-space: nowrap;
            transition: all 0.2s;
        }
        .nav-pills-scroll .nav-link.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            box-shadow: 0 4px 10px rgba(255, 87, 34, 0.3);
        }

        /* TARJETA DEL PARTIDO */
        .match-card {
            background: white;
            border-radius: 16px;
            border: 1px solid var(--card-border);
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            transition: transform 0.2s, box-shadow 0.2s;
            overflow: hidden;
            height: 100%;
            position: relative;
        }
        .match-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.08);
            border-color: #ffd8cc;
        }

        /* Estado del partido (Badge superior) */
        .match-status {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 4px 10px;
            border-radius: 0 0 0 12px;
            position: absolute;
            top: 0;
            right: 0;
        }
        .status-scheduled { background: #f1f5f9; color: #64748b; }
        .status-playing { background: #fff7ed; color: #ff5722; animation: pulse 2s infinite; }
        .status-finished { background: #ecfdf5; color: #10b981; }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.6; }
            100% { opacity: 1; }
        }

        /* Logos y Nombres */
        .team-container {
            text-align: center;
            width: 35%;
            padding: 1rem 0.5rem;
        }
        .team-logo {
            width: 55px;
            height: 55px;
            object-fit: contain;
            margin-bottom: 0.5rem;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
        }
        .team-name {
            font-size: 0.9rem;
            font-weight: 700;
            line-height: 1.2;
            color: #334155;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        /* Sección Central (VS y Fecha) */
        .match-info {
            width: 30%;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .vs-text {
            font-size: 1rem;
            font-weight: 900;
            color: #cbd5e1;
            margin-bottom: 2px;
        }
        .match-time {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--primary);
            background: #fff1ec;
            padding: 2px 8px;
            border-radius: 6px;
        }
        .score-display {
            font-size: 1.4rem;
            font-weight: 900;
            color: #1e293b;
            letter-spacing: -1px;
        }

        /* Footer de la tarjeta */
        .match-footer {
            border-top: 1px solid var(--card-border);
            padding: 0.8rem 1rem;
            background: #fcfcfc;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.8rem;
            color: #64748b;
        }
        
        .action-btn {
            width: 32px; height: 32px;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 8px;
            transition: all 0.2s;
            background: white;
            border: 1px solid #e2e8f0;
            color: #64748b;
            cursor: pointer;
            text-decoration: none;
        }
        .action-btn:hover { background: var(--primary); color: white; border-color: var(--primary); }
        
        .btn-pdf { color: #dc3545; border-color: #f8d7da; background: #fff5f5; }
        .btn-pdf:hover { background: #dc3545; color: white; border-color: #dc3545; }

        @media (max-width: 576px) {
            .team-logo { width: 45px; height: 45px; }
            .team-name { font-size: 0.8rem; }
            .score-display { font-size: 1.2rem; }
        }

        /* --- FOOTER MODERNO --- */
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
            border-color: var(--primary);
            color: var(--primary);
            background: #fff5f2;
        }
    </style>
</head>
<body>

    <div class="top-bar">
        <div class="container d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center">
                <a href="dashboard.php" class="btn btn-light btn-sm rounded-circle me-3 shadow-sm border">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <div>
                    <h5 class="mb-0 fw-bold" id="tournamentName">Cargando...</h5>
                    <small class="text-muted">Calendario Oficial</small>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-4 main-content">
        <div class="rounds-nav-container">
            <ul class="nav nav-pills nav-pills-scroll" id="roundTabs" role="tablist"></ul>
            <div style="position: absolute; right: 0; top:0; bottom:5px; width: 30px; background: linear-gradient(to right, transparent, var(--bg-light)); pointer-events: none;"></div>
        </div>

        <div class="tab-content" id="roundsContent">
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="text-muted mt-2 small">Cargando fixture...</p>
            </div>
        </div>
    </div>
    <div class="modal fade" id="modalEditMatch" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form id="formEditMatch" class="modal-content border-0 shadow-lg">
                <input type="hidden" name="match_id" id="edit_match_id">
                
                <div class="modal-header bg-white border-bottom-0 pb-0">
                    <h6 class="modal-title fw-bold text-muted text-uppercase small" style="letter-spacing: 1px;">Editar Encuentro</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body p-4 pt-2">
                    <h4 class="fw-bold text-center mb-4" id="edit_match_title">Equipo A vs Equipo B</h4>
                    
                    <div class="row g-3">
                        <div class="col-7">
                            <div class="form-floating">
                                <input type="date" name="date" id="edit_date" class="form-control fw-bold">
                                <label>Fecha</label>
                            </div>
                        </div>
                        <div class="col-5">
                            <div class="form-floating">
                                <input type="time" name="time" id="edit_time" class="form-control fw-bold">
                                <label>Hora</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-floating">
                                <select name="venue_id" id="edit_venue" class="form-select">
                                    <option value="">-- Sin Sede --</option>
                                </select>
                                <label>Sede / Cancha</label>
                            </div>
                        </div>
                        <div class="col-12">
                             <div class="form-floating">
                                <select name="status" id="edit_status" class="form-select">
                                    <option value="SCHEDULED">Programado</option>
                                    <option value="PLAYING">En Juego</option>
                                    <option value="FINISHED">Finalizado</option>
                                    <option value="CANCELLED">Cancelado</option>
                                </select>
                                <label>Estado</label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer border-0 pt-0 pb-4 pe-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary px-4">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>

    <footer class="footer-admin">
        <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.all.min.js"></script>

<script>
    const TOURNAMENT_ID = <?php echo $tournament_id; ?>;
    const API_URL = '../api.php';
    let venuesData = [];

    document.addEventListener("DOMContentLoaded", () => {
        loadVenues();
        loadFixture();
        setupEditForm();
    });

    async function loadVenues() {
        try {
            const res = await fetch(`${API_URL}?action=get_data`);
            const json = await res.json();
            if(json.status === 'success') {
                venuesData = json.data.venues;
            }
        } catch(e) { console.error("Error cargando sedes", e); }
    }

    async function loadFixture() {
        if(TOURNAMENT_ID == 0) { 
            Swal.fire('Error', 'ID de torneo no válido', 'error').then(() => window.location.href='dashboard.php'); 
            return; 
        }

        try {
            const res = await fetch(`${API_URL}?action=get_fixture&tournament_id=${TOURNAMENT_ID}`);
            const json = await res.json();

            if(json.status === 'success') {
                const { tournament_name, rounds } = json.data;
                document.getElementById('tournamentName').innerText = tournament_name;
                renderRounds(rounds);
            } else {
                showError(json.message);
            }
        } catch(e) {
            console.error(e);
            showError("Error de conexión con el servidor.");
        }
    }

    function renderRounds(rounds) {
        const tabsContainer = document.getElementById('roundTabs');
        const contentContainer = document.getElementById('roundsContent');
        
        tabsContainer.innerHTML = '';
        contentContainer.innerHTML = '';

        if (!rounds || Object.keys(rounds).length === 0) {
            contentContainer.innerHTML = `
                <div class="text-center py-5">
                    <i class="bi bi-calendar-x opacity-25" style="font-size: 5rem; color: var(--primary)"></i>
                    <h5 class="text-muted fw-bold mt-3">Sin partidos programados</h5>
                    <p class="text-muted small">Genera el calendario desde el Dashboard principal.</p>
                </div>`;
            return;
        }

        let isFirst = true;
        for (const [roundName, matches] of Object.entries(rounds)) {
            const safeId = roundName.replace(/[^a-zA-Z0-9]/g, '_');
            const activeClass = isFirst ? 'active' : '';
            const showClass = isFirst ? 'show active' : '';

            // 1. Tab (Botón de Jornada)
            tabsContainer.innerHTML += `
                <button class="nav-link ${activeClass}" data-bs-toggle="pill" data-bs-target="#tab_${safeId}" type="button">
                    ${roundName}
                </button>`;

            // 2. Lista de Partidos
            let matchesHtml = matches.map(m => {
                // Formato Fecha
                let dateDisplay = '<span class="text-muted fst-italic" style="font-size:0.75rem">Sin fecha</span>';
                let timeDisplay = '';
                
                if(m.scheduled_datetime) {
                    const date = new Date(m.scheduled_datetime);
                    const day = date.toLocaleDateString('es-ES', { weekday: 'short', day: 'numeric', month: 'short' });
                    const time = date.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
                    
                    dateDisplay = `<span class="fw-bold text-dark"><i class="bi bi-calendar3 me-1"></i>${day}</span>`;
                    timeDisplay = `<div class="match-time mt-1">${time}</div>`;
                }

                // Logos (Fallback)
                const logoA = m.logo_a ? `../${m.logo_a}` : `https://ui-avatars.com/api/?name=${encodeURIComponent(m.team_a)}&background=f1f5f9&color=64748b`;
                const logoB = m.logo_b ? `../${m.logo_b}` : `https://ui-avatars.com/api/?name=${encodeURIComponent(m.team_b)}&background=f1f5f9&color=64748b`;

                // Badge Estado
                let statusBadge = '<span class="match-status status-scheduled">Pendiente</span>';
                let isFinished = false;

                if(m.status === 'FINISHED') { statusBadge = '<span class="match-status status-finished">Finalizado</span>'; isFinished = true; }
                if(m.status === 'PLAYING') { statusBadge = '<span class="match-status status-playing">En Juego</span>'; isFinished = true; }
                if(m.status === 'CANCELLED') statusBadge = '<span class="match-status bg-danger text-white">Cancelado</span>';

                // Lógica de Marcador vs "VS"
                let centerContent = `<div class="vs-text">VS</div>${timeDisplay}`;
                if(isFinished) {
                    const scA = m.score_a !== null ? m.score_a : 0;
                    const scB = m.score_b !== null ? m.score_b : 0;
                    centerContent = `
                        <div class="d-flex align-items-center justify-content-center gap-2 mb-1">
                            <span class="score-display">${scA}</span>
                            <span class="text-muted fs-6">-</span>
                            <span class="score-display">${scB}</span>
                        </div>
                        ${timeDisplay}
                    `;
                }

                // Botón PDF si existe
                let pdfButton = '';
                if(m.pdf_url) {
                    pdfButton = `<a href="${m.pdf_url}" target="_blank" class="action-btn btn-pdf" data-bs-toggle="tooltip" title="Ver Acta PDF"><i class="bi bi-filetype-pdf"></i></a>`;
                }

                const matchJson = JSON.stringify(m).replace(/'/g, "&#39;");

                return `
                <div class="col-md-6 col-xl-4 mb-4">
                    <div class="match-card">
                        ${statusBadge}
                        
                        <div class="d-flex align-items-center justify-content-between p-3 pb-1 mt-3">
                            <div class="team-container">
                                <img src="${logoA}" class="team-logo rounded-3" onerror="this.src='https://placehold.co/60x60?text=A'">
                                <div class="team-name" title="${m.team_a}">${m.team_a}</div>
                            </div>

                            <div class="match-info">
                                ${centerContent}
                            </div>

                            <div class="team-container">
                                <img src="${logoB}" class="team-logo rounded-3" onerror="this.src='https://placehold.co/60x60?text=B'">
                                <div class="team-name" title="${m.team_b}">${m.team_b}</div>
                            </div>
                        </div>

                        <div class="match-footer mt-2">
                            <div class="d-flex flex-column">
                                ${dateDisplay}
                                <small class="text-truncate mt-1" style="max-width: 160px;">
                                    <i class="bi bi-geo-alt-fill text-danger me-1"></i>${m.venue_name || 'Sede por definir'}
                                </small>
                            </div>
                            <div class="d-flex gap-2">
                                ${pdfButton}
                                <button class="action-btn" data-bs-toggle="tooltip" title="Configurar Partido" onclick='openEditModal(${matchJson})'>
                                    <i class="bi bi-gear"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>`;
            }).join('');

            contentContainer.innerHTML += `
                <div class="tab-pane fade ${showClass}" id="tab_${safeId}">
                    <div class="row">${matchesHtml}</div>
                </div>`;
            
            isFirst = false;
        }

        // Re-inicializar tooltips generados dinámicamente
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    }

    // --- FUNCIONES DE EDICIÓN ---
    window.openEditModal = function(match) {
        document.getElementById('edit_match_id').value = match.id;
        document.getElementById('edit_match_title').innerText = `${match.team_a} vs ${match.team_b}`;
        document.getElementById('edit_status').value = match.status;

        const venueSelect = document.getElementById('edit_venue');
        venueSelect.innerHTML = '<option value="">-- Sin Sede --</option>';
        venuesData.forEach(v => {
            const selected = v.id == match.venue_id ? 'selected' : '';
            venueSelect.innerHTML += `<option value="${v.id}" ${selected}>${v.name}</option>`;
        });

        if(match.scheduled_datetime) {
            const dt = new Date(match.scheduled_datetime);
            const dateVal = dt.toISOString().split('T')[0];
            const timeVal = dt.toTimeString().substring(0, 5);
            document.getElementById('edit_date').value = dateVal;
            document.getElementById('edit_time').value = timeVal;
        } else {
            document.getElementById('edit_date').value = '';
            document.getElementById('edit_time').value = '';
        }

        new bootstrap.Modal(document.getElementById('modalEditMatch')).show();
    }

    function setupEditForm() {
        document.getElementById('formEditMatch').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = e.target.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';

            const formData = new FormData(e.target);
            const data = {};
            formData.forEach((value, key) => data[key] = value);
            data.action = 'update_fixture_match'; 

            try {
                const res = await fetch(API_URL, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });
                const json = await res.json();

                if(json.status === 'success') {
                    bootstrap.Modal.getInstance(document.getElementById('modalEditMatch')).hide();
                    
                    // Alerta elegante tipo Toast
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Partido actualizado',
                        showConfirmButton: false,
                        timer: 3000
                    });
                    
                    loadFixture();
                } else {
                    Swal.fire('Error', json.message, 'error');
                }
            } catch(err) {
                Swal.fire('Error', 'Fallo de conexión al servidor.', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        });
    }

    function showError(msg) {
        document.getElementById('roundsContent').innerHTML = `
            <div class="alert alert-danger mx-auto mt-4 text-center shadow-sm" style="max-width:500px; border-radius: 12px;">
                <i class="bi bi-exclamation-triangle-fill fs-2 d-block mb-2"></i>
                <strong>¡Ups! Algo salió mal.</strong><br>${msg}
            </div>`;
    }
</script>
</body>
</html>