const API_URL = '../api.php';

// Estado Global de Datos
let allTournaments = [];
let allTeams = [];
let allPlayers = [];
let filteredPlayers = []; // Para el filtro local de jugadores

// Configuración de Paginación
const ITEMS_PER_PAGE = 5;
let pageTournaments = 1;
let pageTeams = 1;
let pagePlayers = 1;

document.addEventListener("DOMContentLoaded", () => {
    loadTournamentsList(); 
    loadFilteredData(0); 
    setupForms();

    // Escuchar el cambio de pestañas para ocultar la barra de filtros si estamos en Galería
    document.querySelectorAll('button[data-bs-toggle="pill"], a[data-bs-toggle="pill"]').forEach(tab => {
        tab.addEventListener('shown.bs.tab', event => {
            const targetId = event.target.getAttribute('data-bs-target');
            document.getElementById('mainFilterBar').style.display = (targetId === '#tab-gallery') ? 'none' : 'flex';
        });
    });
});

// --- HELPER: SKELETON LOADER ---
function showTableSkeleton(tableId, columns) {
    const tbody = document.getElementById(tableId);
    if (!tbody) return;
    let html = '';
    for(let i=0; i<5; i++) {
        html += `<tr class="placeholder-glow">`;
        for(let j=0; j<columns; j++) {
            html += `<td><span class="placeholder col-10 rounded"></span></td>`;
        }
        html += `</tr>`;
    }
    tbody.innerHTML = html;
}

// --- CARGA DE DATOS ---

// 1. Cargar Torneos
async function loadTournamentsList() {
    showTableSkeleton('tableTournaments', 4);
    try {
        const res = await fetch(`${API_URL}?action=get_tournaments_list`);
        const json = await res.json();
        if (json.status === 'success') {
            allTournaments = json.data;
            pageTournaments = 1; // Resetear página
            renderTournamentsTable(); 
            
            // Llenar select de filtro principal
            const filterSelect = document.getElementById('dashboardFilterTournament');
            if (filterSelect) {
                const currentVal = filterSelect.value;
                filterSelect.innerHTML = '<option value="0">⭐ Ver Todos los Torneos</option>';
                allTournaments.forEach(t => filterSelect.innerHTML += `<option value="${t.id}">${t.name}</option>`);
                if(currentVal) filterSelect.value = currentVal;
            }

            // Llenar select del modal de equipos
            fillSelect('selectTournamentForTeam', allTournaments, true); 
        }
    } catch (e) { console.error("Error cargando torneos:", e); }
}

// 2. Cargar Datos Filtrados (Equipos y Jugadores)
async function loadFilteredData(tournamentId) {
    showTableSkeleton('tableTeams', 4);
    showTableSkeleton('tablePlayers', 5);
    
    const subtitle = tournamentId == 0 ? 'Todos los registros' : 'Filtrado por torneo';
    const subTeam = document.getElementById('teamSubtitle');
    const subPlayer = document.getElementById('playerSubtitle');
    if(subTeam) subTeam.innerText = subtitle;
    if(subPlayer) subPlayer.innerText = subtitle;

    try {
        const res = await fetch(`${API_URL}?action=get_data_by_tournament&tournament_id=${tournamentId}`);
        const json = await res.json();
        if (json.status === 'success') {
            allTeams = json.data.teams;
            allPlayers = json.data.players;
            filteredPlayers = [...allPlayers]; // Inicializar filtro local

            // Resetear páginas
            pageTeams = 1;
            pagePlayers = 1;

            renderTeamsTable();
            renderPlayersTable();
            
            // Actualizar selects de filtros locales y modales
            fillSelectTeams(allTeams, 'selectTeamForPlayer');
            fillSelectTeams(allTeams, 'filterPlayersByTeam', true);
            
            const filterPlayersSelect = document.getElementById('filterPlayersByTeam');
            if(filterPlayersSelect) filterPlayersSelect.value = '0';
        }
    } catch (e) { console.error("Error cargando datos filtrados:", e); }
}

function filterDashboard() {
    const select = document.getElementById('dashboardFilterTournament');
    if(select) loadFilteredData(select.value);
}

function applyLocalPlayerFilter() {
    const select = document.getElementById('filterPlayersByTeam');
    if(!select) return;
    
    const teamId = select.value;
    // Filtrar sobre la copia local 'filteredPlayers'
    if (teamId == 0) {
        filteredPlayers = [...allPlayers];
    } else {
        filteredPlayers = allPlayers.filter(p => p.team_id == teamId);
    }
    
    pagePlayers = 1; // Resetear página al filtrar
    renderPlayersTable();
}

// --- RENDERIZADO CON PAGINACIÓN (WRAPPERS) ---

function renderTournamentsTable() {
    renderPaginatedList(allTournaments, pageTournaments, 'tableTournaments', 'paginationTournaments', rowTournamentTemplate, (newPage) => {
        pageTournaments = newPage;
        renderTournamentsTable();
    });
}

function renderTeamsTable() {
    renderPaginatedList(allTeams, pageTeams, 'tableTeams', 'paginationTeams', rowTeamTemplate, (newPage) => {
        pageTeams = newPage;
        renderTeamsTable();
    });
}

function renderPlayersTable() {
    renderPaginatedList(filteredPlayers, pagePlayers, 'tablePlayers', 'paginationPlayers', rowPlayerTemplate, (newPage) => {
        pagePlayers = newPage;
        renderPlayersTable();
    });
}

/**
 * Lógica Core de Paginación
 */
function renderPaginatedList(data, currentPage, tableId, paginationId, rowTemplateFunc, onPageChange) {
    const tbody = document.getElementById(tableId);
    const pagContainer = document.getElementById(paginationId);
    
    if (!tbody || !pagContainer) return;

    if(data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="100%" class="text-center py-4 text-muted">No hay datos disponibles</td></tr>';
        pagContainer.innerHTML = '';
        return;
    }

    // Calcular índices
    const start = (currentPage - 1) * ITEMS_PER_PAGE;
    const end = start + ITEMS_PER_PAGE;
    const pageData = data.slice(start, end);
    const totalPages = Math.ceil(data.length / ITEMS_PER_PAGE);

    // 1. Renderizar Filas
    tbody.innerHTML = pageData.map(rowTemplateFunc).join('');
    
    // 2. Renderizar Controles de Paginación
    if(data.length > 0) {
        let controlsHtml = '';
        
        if (totalPages > 1) {
            controlsHtml = `
                <div>
                    <button class="btn-page" ${currentPage === 1 ? 'disabled' : ''} onclick="window.changePage('${paginationId}', ${currentPage - 1})">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <span class="mx-2 small text-muted fw-bold">${currentPage} / ${totalPages}</span>
                    <button class="btn-page" ${currentPage === totalPages ? 'disabled' : ''} onclick="window.changePage('${paginationId}', ${currentPage + 1})">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            `;
        }

        pagContainer.innerHTML = `
            <div class="d-flex justify-content-between align-items-center w-100 mt-2">
                <span class="page-info">Mostrando ${start + 1}-${Math.min(end, data.length)} de ${data.length}</span>
                ${controlsHtml}
            </div>
        `;
    } else {
        pagContainer.innerHTML = '';
    }
    
    // Guardar callback globalmente
    if (!window.pageCallbacks) window.pageCallbacks = {};
    window.pageCallbacks[paginationId] = onPageChange;

    // Reactivar Tooltips
    initializeTooltips();
}

window.changePage = function(paginationId, newPage) {
    if (window.pageCallbacks && window.pageCallbacks[paginationId]) {
        window.pageCallbacks[paginationId](newPage);
    }
};

function initializeTooltips() {
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
}


// --- TEMPLATES HTML ---

const rowTournamentTemplate = (t) => `
    <tr>
        <td class="ps-4 text-muted small fw-bold">#${t.id}</td>
        <td class="fw-bold text-dark">${t.name}</td>
        <td><span class="badge bg-light text-dark border px-3 py-2">${t.category || 'General'}</span></td>
        <td class="text-end pe-4">
            <div class="btn-action-group shadow-sm">
                <a href="fixture.php?id=${t.id}" class="btn btn-icon text-primary" data-bs-toggle="tooltip" title="Ver Calendario">
                    <i class="bi bi-calendar-event"></i>
                </a>
                <button class="btn btn-icon text-warning" onclick="openFixtureConfig(${t.id})" data-bs-toggle="tooltip" title="Generar Sorteo">
                    <i class="bi bi-magic"></i>
                </button>
                <div class="vr my-1 mx-1 text-muted opacity-25"></div>
                <button class="btn btn-icon text-secondary" onclick="editTournament(${t.id})" data-bs-toggle="tooltip" title="Editar Info">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-icon text-danger" onclick="deleteItem('delete_tournament', ${t.id})" data-bs-toggle="tooltip" title="Eliminar">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </td>
    </tr>`;

const rowTeamTemplate = (t) => {
    const imgUrl = t.logo_url ? `../${t.logo_url}` : `https://ui-avatars.com/api/?name=${encodeURIComponent(t.name)}&background=random&color=fff&size=128`;
    return `
    <tr>
        <td class="ps-4 text-muted small fw-bold">#${t.id}</td>
        <td>
            <div class="d-flex align-items-center">
                <img src="${imgUrl}" alt="${t.name}" class="team-logo-table me-3 shadow-sm" onerror="this.onerror=null; this.src='https://placehold.co/48?text=TM';">
                <div>
                    <span class="team-name-text">${t.name}</span>
                    <span class="team-meta-text badge bg-light text-secondary border mt-1">${t.short_name || 'N/A'}</span>
                </div>
            </div>
        </td>
        <td><div class="text-muted small fw-medium"><i class="bi bi-person-badge me-1 text-primary opacity-50"></i>${t.coach_name || 'Sin coach'}</div></td>
        <td class="text-end pe-4">
            <div class="btn-action-group shadow-sm">
                <button class="btn btn-icon text-secondary" onclick="editTeam(${t.id})" title="Editar"><i class="bi bi-pencil"></i></button>
                <button class="btn btn-icon text-danger" onclick="deleteItem('delete_team', ${t.id})" title="Eliminar"><i class="bi bi-trash"></i></button>
            </div>
        </td>
    </tr>`;
};

const rowPlayerTemplate = (p) => {
    const team = allTeams.find(t => t.id == p.team_id);
    const teamName = team ? team.name : '<span class="text-muted small">Sin Equipo</span>';
    
    return `
    <tr>
        <td class="ps-4 text-muted small fw-bold">#${p.id}</td>
        <td class="fw-bold text-dark">${p.name}</td>
        <td><span class="badge bg-white text-dark border shadow-sm rounded-pill px-3"># ${p.default_number || '?'}</span></td>
        <td class="small fw-medium text-muted">${teamName}</td>
        <td class="text-end pe-4">
            <div class="btn-action-group shadow-sm">
                <button class="btn btn-icon text-secondary" onclick="editPlayer(${p.id})"><i class="bi bi-pencil"></i></button>
                <button class="btn btn-icon text-danger" onclick="deleteItem('delete_player', ${p.id})"><i class="bi bi-trash"></i></button>
            </div>
        </td>
    </tr>`;
};

// --- MODALES & FORMS ---
function openModal(id) {
    const modalEl = document.getElementById(id);
    if(!modalEl) return;
    const modal = new bootstrap.Modal(modalEl);
    const form = document.querySelector(`#${id} form`);
    if(form) {
        form.reset();
        const idInput = form.querySelector(`input[name='id']`);
        if(idInput) idInput.value = '';
    }
    if(id === 'modalTeam') {
        const img = document.getElementById('previewLogo');
        if(img) img.src = 'https://placehold.co/80?text=Logo';
    }
    modal.show();
}

function openFixtureConfig(tournamentId) {
    const form = document.getElementById('formFixture');
    if(form) form.reset();
    document.getElementById('fix_tourn_id').value = tournamentId;
    new bootstrap.Modal(document.getElementById('modalFixtureConfig')).show();
}

function editTournament(id) {
    const item = allTournaments.find(x => x.id == id);
    if(!item) return;
    document.getElementById('tourn_id').value = item.id;
    document.getElementById('tourn_name').value = item.name;
    document.getElementById('tourn_cat').value = item.category;
    document.getElementById('titleTournament').innerText = 'Editar Torneo';
    new bootstrap.Modal(document.getElementById('modalTournament')).show();
}

function editTeam(id) {
    const item = allTeams.find(x => x.id == id);
    if(!item) return;
    document.getElementById('team_id').value = item.id;
    document.getElementById('team_name').value = item.name;
    document.getElementById('team_short').value = item.short_name;
    document.getElementById('team_coach').value = item.coach_name;
    const img = document.getElementById('previewLogo');
    if(img) img.src = item.logo_url ? `../${item.logo_url}` : 'https://placehold.co/80?text=Logo';
    document.getElementById('titleTeam').innerText = 'Editar Equipo';
    new bootstrap.Modal(document.getElementById('modalTeam')).show();
}

function editPlayer(id) {
    const item = filteredPlayers.find(x => x.id == id);
    if(!item) return;
    document.getElementById('player_id').value = item.id;
    document.getElementById('player_name').value = item.name;
    document.getElementById('player_num').value = item.default_number;
    document.getElementById('selectTeamForPlayer').value = item.team_id;
    document.getElementById('titlePlayer').innerText = 'Editar Jugador';
    new bootstrap.Modal(document.getElementById('modalPlayer')).show();
}

function setupForms() {
    ['formTournament', 'formTeam', 'formPlayer'].forEach(id => {
        const formEl = document.getElementById(id);
        if(!formEl) return;
        
        formEl.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const isUpdate = formData.get('id');
            let actionPrefix = isUpdate ? 'update_' : (id === 'formPlayer' ? 'add_' : 'create_');
            let finalAction = actionPrefix + (id === 'formPlayer' ? 'player' : id.replace('form','').toLowerCase());

            try {
                const res = await fetch(`${API_URL}?action=${finalAction}`, { method: 'POST', body: formData });
                const json = await res.json();
                if(json.status === 'success') {
                    bootstrap.Modal.getInstance(document.querySelector(`#${id}`).closest('.modal')).hide();
                    loadTournamentsList();
                    loadFilteredData(document.getElementById('dashboardFilterTournament').value);
                } else alert(json.message);
            } catch (err) { console.error(err); }
        });
    });

    const formFixture = document.getElementById('formFixture');
    if (formFixture) {
        formFixture.addEventListener('submit', async (e) => {
            e.preventDefault();
            const tournamentId = parseInt(document.getElementById('fix_tourn_id').value);
            const config = {
                vueltas: document.getElementById('fix_vueltas').value,
                pts_victoria: document.getElementById('fix_win').value,
                pts_derrota: document.getElementById('fix_loss').value,
                pts_empate: document.getElementById('fix_draw').value
            };

            const btnSubmit = formFixture.querySelector('button[type="submit"]');
            const originalText = btnSubmit.innerHTML;
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Generando...';

            try {
                const payload = {
                    action: 'generate_fixture',
                    tournament_id: tournamentId,
                    config: config
                };
                const res = await fetch(API_URL, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(payload)
                });
                const json = await res.json();
                if(json.status === 'success') {
                    alert('¡Calendario generado exitosamente!');
                    bootstrap.Modal.getInstance(document.getElementById('modalFixtureConfig')).hide();
                    window.location.href = `fixture.php?id=${tournamentId}`;
                } else { alert('Error: ' + json.message); }
            } catch (err) { console.error(err); alert('Error de conexión.'); } 
            finally { btnSubmit.disabled = false; btnSubmit.innerHTML = originalText; }
        });
    }
}

async function deleteItem(action, id) {
    const filter = document.getElementById('dashboardFilterTournament').value;
    if (action === 'delete_team' && filter != 0) {
        if(!confirm('¿Quitar del torneo?')) return;
        await fetch(`${API_URL}?action=detach_team`, { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({id, tournament_id: filter}) });
    } else {
        if(!confirm('¿Eliminar permanentemente?')) return;
        await fetch(`${API_URL}?action=${action}`, { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({id}) });
    }
    loadFilteredData(filter);
    if(action === 'delete_tournament') loadTournamentsList();
}

function fillSelect(id, items, empty) {
    const s = document.getElementById(id); if(!s) return;
    s.innerHTML = (empty ? '<option value="">-- Seleccionar --</option>' : '') + items.map(i => `<option value="${i.id}">${i.name}</option>`).join('');
}

function fillSelectTeams(items, id, all) {
    const s = document.getElementById(id); if(!s) return;
    s.innerHTML = (all ? '<option value="0">Todos los Equipos</option>' : '<option value="">Seleccione Equipo...</option>') + items.map(i => `<option value="${i.id}">${i.name}</option>`).join('');
}

// --- NUEVO: FUNCIONES DE GALERÍA ---
async function loadGallery() {
    const container = document.getElementById('galleryContainer');
    container.innerHTML = '<div class="col-12 text-center py-5"><div class="spinner-border text-primary"></div></div>';
    
    try {
        const res = await fetch(`${API_URL}?action=get_slider_images`);
        const json = await res.json();
        
        if (json.status === 'success') {
            if(json.data.length === 0) {
                container.innerHTML = '<div class="col-12 text-center py-5 text-muted"><i class="bi bi-images fs-1 d-block mb-3"></i>No hay imágenes en la galería. Sube una.</div>';
                return;
            }

            container.innerHTML = json.data.map(img => `
                <div class="col-md-4 col-lg-3">
                    <div class="position-relative">
                        <img src="../${img.url}" class="admin-gallery-img shadow-sm" alt="Slider">
                        <button class="btn btn-danger img-delete-btn" onclick="deleteGalleryImage('${img.filename}')" title="Eliminar">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            `).join('');
        }
    } catch(e) { console.error("Error cargando galería:", e); }
}

async function uploadGalleryImage(input) {
    if (!input.files || input.files.length === 0) return;
    
    const formData = new FormData();
    formData.append('image', input.files[0]);
    formData.append('action', 'upload_slider_image');

    input.value = ''; // Resetear input
    
    try {
        const res = await fetch(API_URL, { method: 'POST', body: formData });
        const json = await res.json();
        if(json.status === 'success') {
            loadGallery(); // Recargar imágenes
        } else {
            alert(json.message);
        }
    } catch(e) { alert("Error al subir imagen"); }
}

async function deleteGalleryImage(filename) {
    if(!confirm('¿Eliminar esta imagen del slider web?')) return;
    
    try {
        const res = await fetch(`${API_URL}?action=delete_slider_image`, { 
            method: 'POST', 
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({filename: filename})
        });
        const json = await res.json();
        if(json.status === 'success') {
            loadGallery(); 
        } else {
            alert(json.message);
        }
    } catch(e) { alert("Error al eliminar"); }
}