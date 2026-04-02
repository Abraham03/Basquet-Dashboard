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
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Anton&family=Bebas+Neue&family=Montserrat:wght@700;900&family=Oswald:wght@500;700&family=Playfair+Display:ital,wght@0,700;1,700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/fixture.css">
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
            <div>
                <button class="btn btn-primary rounded-pill shadow-sm fw-bold" onclick="openManualMatchModal()">
                    <i class="bi bi-plus-lg me-1"></i> Partido Manual
                </button>
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

    <div class="modal fade" id="modalManualMatch" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form id="formManualMatch" class="modal-content border-0 shadow-lg">
                <input type="hidden" name="tournament_id" id="manual_tourn_id">
                
                <div class="modal-header border-bottom-0 pb-0">
                    <h6 class="modal-title fw-bold text-white text-uppercase small" style="letter-spacing: 1px;">Añadir Partido Manual</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body p-4 pt-2">
                    <div class="alert small mb-3" style="background: rgba(14, 165, 233, 0.1); color: #38bdf8; border: 1px solid rgba(14, 165, 233, 0.2);">
                        <i class="bi bi-info-circle me-1"></i> Selecciona los equipos. El sistema validará que no rompan el calendario.
                    </div>

                    <div class="form-floating mb-4">
                        <input type="number" name="round_order" id="manual_round" class="form-control fw-bold fs-5" value="1" min="1" required oninput="handleRoundChange()">
                        <label>Número de Jornada</label>
                    </div>
                    
                    <div class="custom-dropdown-container">
                        <span class="custom-dropdown-label text-warning">Equipo Local (A)</span>
                        <div class="custom-dropdown-btn" id="btnDropdownA" onclick="toggleCustomDropdown('A')">
                            <span id="textDropdownA">-- Selecciona Equipo --</span>
                            <i class="bi bi-chevron-down"></i>
                        </div>
                        <div class="custom-dropdown-menu" id="menuDropdownA"></div>
                        <input type="hidden" name="team_a_id" id="valDropdownA" required>
                    </div>
                    
                    <div class="text-center fw-bold text-muted mb-3 fs-5">VS</div>
                    
                    <div class="custom-dropdown-container">
                        <span class="custom-dropdown-label text-info">Equipo Visitante (B)</span>
                        <div class="custom-dropdown-btn" id="btnDropdownB" onclick="toggleCustomDropdown('B')">
                            <span id="textDropdownB">-- Selecciona Equipo --</span>
                            <i class="bi bi-chevron-down"></i>
                        </div>
                        <div class="custom-dropdown-menu" id="menuDropdownB"></div>
                        <input type="hidden" name="team_b_id" id="valDropdownB" required>
                    </div>
                    
                    <div id="manual_warning" class="alert small d-none mb-0 mt-3" style="background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);"></div>
                </div>
                
                <div class="modal-footer border-0 pt-0 pb-4 pe-4">
                    <button type="button" class="btn text-white-50" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold" id="btn_save_manual">Guardar Partido</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="container py-4 main-content">
        <div class="table-actions mb-3"></div> 
        </div>

    <div class="modal fade" id="modalFlyer" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content bg-dark border-secondary shadow-lg" style="border-radius: 20px; overflow: hidden;">
                
                <div class="modal-header border-bottom border-secondary bg-black bg-opacity-50 py-3 px-4">
                    <h5 class="modal-title fw-bold text-white mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-magic text-info"></i> Creador de Carteleras
                    </h5>
                    <div class="d-flex gap-3 align-items-center">
                        <div class="d-flex align-items-center gap-2 bg-secondary bg-opacity-25 px-3 py-1 rounded-pill">
                            <i class="bi bi-image text-muted small"></i>
                            <select id="flyerBgSelect" class="form-select form-select-sm bg-transparent text-white border-0 shadow-none p-0 pe-4" style="cursor: pointer;" onchange="changeFlyerBackground(this.value)">
                                <option value="">-- Fondo Sólido --</option>
                            </select>
                            <div class="position-relative ms-2 border-start border-secondary ps-2" title="Subir Nuevo Fondo">
                                <input type="file" id="uploadRolBg" class="position-absolute top-0 start-0 w-100 h-100" accept="image/*" onchange="uploadFlyerBackground(this)" style="opacity: 0; cursor: pointer; z-index: 2;">
                                <i class="bi bi-cloud-arrow-up text-info" style="cursor: pointer;"></i>
                            </div>
                        </div>
                        <button class="btn btn-sm btn-info fw-bold text-dark rounded-pill px-3" onclick="addTextToFlyer()">
                            <i class="bi bi-fonts me-1"></i> Texto
                        </button>
                        <button type="button" class="btn-close btn-close-white ms-2" data-bs-dismiss="modal"></button>
                    </div>
                </div>

                <div class="modal-body p-0 position-relative d-flex justify-content-center align-items-center" style="background-color: #0f172a; min-height: 70vh; overflow: hidden;">
                    
                    <div id="flyer-design-tools" class="position-absolute top-0 mt-3 start-50 translate-middle-x bg-dark bg-opacity-75 backdrop-blur rounded-pill px-4 py-2 border border-secondary shadow-lg d-none align-items-center gap-3" style="z-index: 1050; backdrop-filter: blur(10px);">
                        
                        <div class="d-flex align-items-center gap-2" title="Color del Texto">
                            <input type="color" id="tool-color" class="form-control form-control-color bg-transparent border-0 p-0" style="width:24px; height:24px; cursor:pointer;">
                        </div>
                        <div class="d-flex align-items-center gap-2" title="Color de Fondo">
                            <i class="bi bi-paint-bucket text-muted small"></i>
                            <input type="color" id="tool-bgcolor" class="form-control form-control-color bg-transparent border-0 p-0" style="width:24px; height:24px; cursor:pointer;">
                        </div>
                        <div class="vr bg-secondary mx-1"></div>
                        <select id="tool-font" class="form-select form-select-sm bg-transparent text-white border-0 shadow-none" style="width: 110px; cursor:pointer;">
                            <option value="'Inter', sans-serif">Inter</option>
                            <option value="'Anton', sans-serif">Anton</option>
                            <option value="'Bebas Neue', sans-serif">Bebas Neue</option>
                            <option value="'Montserrat', sans-serif">Montserrat</option>
                            <option value="'Oswald', sans-serif">Oswald</option>
                            <option value="'Playfair Display', serif">Playfair</option>
                            <option value="'Impact', sans-serif">Impact</option>
                        </select>
                        <div class="vr bg-secondary mx-1"></div>
                        <div class="d-flex align-items-center gap-2" style="width: 120px;">
                            <i class="bi bi-type text-muted small"></i>
                            <input type="range" id="tool-size" class="form-range" min="10" max="150">
                        </div>
                        <div class="vr bg-secondary mx-1"></div>
                        <button class="btn btn-sm btn-link text-danger p-0 text-decoration-none" onclick="deleteActiveFlyerElement()" title="Eliminar">
                            <i class="bi bi-trash fs-5"></i>
                        </button>
                    </div>

                    <div class="position-absolute bottom-0 start-0 m-3 text-muted small" style="z-index: 1000;">
                        <i class="bi bi-info-circle"></i> Doble clic para editar texto. Arrastra para mover.
                    </div>

                    <div id="flyer-container-wrapper" class="d-flex justify-content-center align-items-center w-100 h-100" style="padding: 2rem; overflow: auto;">
                        <div id="flyer-canvas" class="flyer-wrapper text-start shadow-lg position-relative">
                            <img id="flyer-bg-img" class="flyer-bg" crossorigin="anonymous" style="display: none;">
                            <div class="flyer-content" id="flyer-content-area"></div>
                        </div>
                    </div>
                    
                </div>
                
                <div class="modal-footer border-top-0 bg-dark justify-content-center pb-4">
                    <button type="button" class="btn btn-info rounded-circle shadow-lg text-dark d-flex align-items-center justify-content-center" style="width: 65px; height: 65px; font-size: 1.8rem; transition: transform 0.2s;" id="btnDownloadFlyer" onclick="downloadFlyer()" title="Descargar Póster HD" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                        <i class="bi bi-download"></i>
                    </button>
                </div>
            </div>
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
                    <img id="techSolutionsLogo" src="../assets/imagenes/logo2.png" alt="Logo TechSolutions" style="height: 18px; width: auto; margin-right: 4px;">
                    <span style="color: var(--text-main); font-weight: 700; font-size: 0.9rem; letter-spacing: -0.3px;">TechSolutions</span>
                </a>
            </div>
        </div>
    </footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.all.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
    const TOURNAMENT_ID_VAR = <?php echo $tournament_id; ?>;
</script>
<script src="js/fixture.js"></script>
<script src="js/poster.js"></script>

</body>
</html>