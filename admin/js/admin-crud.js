const API_URL = '../api.php';

// Estado Global de Datos
let allTournaments = [];
let allTeams = [];
let allPlayers = [];
let filteredPlayers = [];

let allUsers = [];
let pageUsers = 1;

// Configuración de Paginación
const ITEMS_PER_PAGE = 5;
let pageTournaments = 1;
let pageTeams = 1;
let pagePlayers = 1;

document.addEventListener("DOMContentLoaded", () => {
    loadTournamentsList(); 
    loadFilteredData(0); 
    loadUsersList();
    setupForms();

    // Escuchar el cambio de pestañas para ocultar la barra de filtros si estamos en Galería
    document.querySelectorAll('button[data-bs-toggle="pill"], a[data-bs-toggle="pill"]').forEach(tab => {
        tab.addEventListener('shown.bs.tab', event => {
            const targetId = event.target.getAttribute('data-bs-target');
            const filterBar = document.getElementById('mainFilterBar');
            if(filterBar) filterBar.style.display = (targetId === '#tab-gallery') ? 'none' : 'flex';
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
async function loadTournamentsList() {
    showTableSkeleton('tableTournaments', 5); // 5 columnas ajustadas
    try {
        const res = await fetch(`${API_URL}?action=get_tournaments_list`);
        const json = await res.json();
        if (json.status === 'success') {
            allTournaments = json.data;
            pageTournaments = 1; 
            renderTournamentsTable(); 
            
            const filterSelect = document.getElementById('dashboardFilterTournament');
            if (filterSelect) {
                const currentVal = filterSelect.value;
                filterSelect.innerHTML = '<option value="0">⭐ Ver Todos los Torneos</option>';
                allTournaments.forEach(t => filterSelect.innerHTML += `<option value="${t.id}">${t.name}</option>`);
                if(currentVal) filterSelect.value = currentVal;
            }

            fillSelect('selectTournamentForTeam', allTournaments, true); 
        }
    } catch (e) { console.error("Error cargando torneos:", e); }
}

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
            filteredPlayers = [...allPlayers]; 

            pageTeams = 1;
            pagePlayers = 1;

            renderTeamsTable();
            renderPlayersTable();
            
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
    if (teamId == 0) {
        filteredPlayers = [...allPlayers];
    } else {
        filteredPlayers = allPlayers.filter(p => p.team_id == teamId);
    }
    
    pagePlayers = 1;
    renderPlayersTable();
}

// --- RENDERIZADO CON PAGINACIÓN ---
function renderTournamentsTable() {
    renderPaginatedList(allTournaments, pageTournaments, 'tableTournaments', 'paginationTournaments', rowTournamentTemplate, (newPage) => {
        pageTournaments = newPage; renderTournamentsTable();
    });
}
function renderTeamsTable() {
    renderPaginatedList(allTeams, pageTeams, 'tableTeams', 'paginationTeams', rowTeamTemplate, (newPage) => {
        pageTeams = newPage; renderTeamsTable();
    });
}
function renderPlayersTable() {
    renderPaginatedList(filteredPlayers, pagePlayers, 'tablePlayers', 'paginationPlayers', rowPlayerTemplate, (newPage) => {
        pagePlayers = newPage; renderPlayersTable();
    });
}

function renderPaginatedList(data, currentPage, tableId, paginationId, rowTemplateFunc, onPageChange) {
    const tbody = document.getElementById(tableId);
    const pagContainer = document.getElementById(paginationId);
    
    if (!tbody || !pagContainer) return;

    if(data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="100%" class="text-center py-4 text-muted">No hay datos disponibles</td></tr>';
        pagContainer.innerHTML = '';
        return;
    }

    const start = (currentPage - 1) * ITEMS_PER_PAGE;
    const end = start + ITEMS_PER_PAGE;
    const pageData = data.slice(start, end);
    const totalPages = Math.ceil(data.length / ITEMS_PER_PAGE);

    tbody.innerHTML = pageData.map(rowTemplateFunc).join('');
    
    if(data.length > 0) {
        let controlsHtml = '';
        if (totalPages > 1) {
            controlsHtml = `
                <div>
                    <button class="btn-page" ${currentPage === 1 ? 'disabled' : ''} onclick="window.changePage('${paginationId}', ${currentPage - 1})"><i class="bi bi-chevron-left"></i></button>
                    <span class="mx-2 small text-muted fw-bold">${currentPage} / ${totalPages}</span>
                    <button class="btn-page" ${currentPage === totalPages ? 'disabled' : ''} onclick="window.changePage('${paginationId}', ${currentPage + 1})"><i class="bi bi-chevron-right"></i></button>
                </div>`;
        }
        pagContainer.innerHTML = `
            <div class="d-flex justify-content-between align-items-center w-100 mt-2">
                <span class="page-info">Mostrando ${start + 1}-${Math.min(end, data.length)} de ${data.length}</span>
                ${controlsHtml}
            </div>`;
    } else {
        pagContainer.innerHTML = '';
    }
    
    if (!window.pageCallbacks) window.pageCallbacks = {};
    window.pageCallbacks[paginationId] = onPageChange;
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
        
        <td>
            <div class="btn-action-group shadow-sm">
                <a href="fixture.php?id=${t.id}" class="btn btn-icon text-primary" data-bs-toggle="tooltip" title="Ver Calendario">
                    <i class="bi bi-calendar-event"></i>
                </a>
                <button class="btn btn-icon text-warning" onclick="openFixtureConfig(${t.id})" data-bs-toggle="tooltip" title="Generar Sorteo">
                    <i class="bi bi-magic"></i>
                </button>
                <button class="btn btn-icon text-danger" onclick="deleteFixture(${t.id})" data-bs-toggle="tooltip" title="Purgar Calendario">
                    <i class="bi bi-calendar-x"></i>
                </button>
            </div>
        </td>

        <td class="text-end pe-4">
            <div class="btn-action-group shadow-sm">
                <button class="btn btn-icon text-secondary" onclick="editTournament(${t.id})" data-bs-toggle="tooltip" title="Editar Info">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-icon text-danger" onclick="deleteItem('delete_tournament', ${t.id})" data-bs-toggle="tooltip" title="Eliminar Torneo">
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
                <button class="btn btn-icon text-secondary" onclick="editTeam(${t.id})" data-bs-toggle="tooltip" title="Editar Equipo"><i class="bi bi-pencil"></i></button>
                <button class="btn btn-icon text-danger" onclick="deleteItem('delete_team', ${t.id})" data-bs-toggle="tooltip" title="Eliminar/Quitar"><i class="bi bi-trash"></i></button>
            </div>
        </td>
    </tr>`;
};

const rowPlayerTemplate = (p) => {
    const team = allTeams.find(t => t.id == p.team_id);
    const teamName = team ? team.name : '<span class="text-muted small">Sin Equipo</span>';
    
    // Si tiene foto, la mostramos. Si no, generamos un avatar con sus iniciales.
    const photoUrl = p.photo_url ? `../${p.photo_url}` : `https://ui-avatars.com/api/?name=${encodeURIComponent(p.name)}&background=random&color=fff`;

    return `
    <tr>
        <td class="ps-4 text-muted small fw-bold">#${p.id}</td>
        <td>
            <div class="d-flex align-items-center">
                <img src="${photoUrl}" alt="${p.name}" class="rounded-circle me-3 shadow-sm border" style="width: 40px; height: 40px; object-fit: cover;" onerror="this.onerror=null; this.src='https://placehold.co/40?text=U';">
                <span class="fw-bold text-dark">${p.name}</span>
            </div>
        </td>
        <td><span class="badge bg-white text-dark border shadow-sm rounded-pill px-3"># ${p.default_number || '?'}</span></td>
        <td class="small fw-medium text-muted">${teamName}</td>
        <td class="text-end pe-4">
            <div class="btn-action-group shadow-sm">
                <button class="btn btn-icon text-secondary" onclick="editPlayer(${p.id})" data-bs-toggle="tooltip" title="Editar"><i class="bi bi-pencil"></i></button>
                <button class="btn btn-icon text-danger" onclick="deleteItem('delete_player', ${p.id})" data-bs-toggle="tooltip" title="Eliminar"><i class="bi bi-trash"></i></button>
            </div>
        </td>
    </tr>`;
};


// --- SWEETALERT FUNCIONES GLOBALES ---
// --- FUNCIONES PARA LA FOTO DEL JUGADOR ---
function previewPlayerImage(input) {
    if (input.files && input.files[0]) { 
        var reader = new FileReader(); 
        reader.onload = function(e) { document.getElementById('previewPlayerPhoto').src = e.target.result; }; 
        reader.readAsDataURL(input.files[0]); 
    } 
}

async function logout() { 
    const result = await Swal.fire({
        title: '¿Cerrar sesión?',
        text: "Saldrás del panel de administración",
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

async function deleteFixture(tournamentId) {
    const result = await Swal.fire({
        title: '⚠️ ¡ADVERTENCIA CRÍTICA!',
        text: '¿Estás seguro de que deseas ELIMINAR y PURGAR todo el calendario de este torneo? Esta acción borrará: Partidos, Estadísticas y Actas. NO se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, Purgar Torneo',
        cancelButtonText: 'Cancelar'
    });
    
    if(result.isConfirmed) {
        try {
            const res = await fetch(`${API_URL}?action=delete_fixture`, { 
                method: 'POST', 
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ id: tournamentId }) 
            });
            const json = await res.json();
            
            if(json.status === 'success') {
                Swal.fire('¡Purgado!', json.message, 'success');
                loadTournamentsList(); 
            } else {
                Swal.fire('Error', json.message, 'error');
            }
        } catch(e) { 
            Swal.fire('Error de Conexión', 'No se pudo purgar el calendario.', 'error'); 
        }
    }
}

async function deleteItem(action, id) {
    const filter = document.getElementById('dashboardFilterTournament').value;
    let isDetach = (action === 'delete_team' && filter != 0);
    
    const title = isDetach ? '¿Quitar del torneo?' : '¿Eliminar permanentemente?';
    const text = isDetach ? 'El equipo ya no pertenecerá a este torneo, pero seguirá en la base de datos.' : 'Esta acción es irreversible.';
    const btnText = isDetach ? 'Sí, quitar' : 'Sí, eliminar';

    const result = await Swal.fire({
        title: title,
        text: text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: btnText,
        cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
        try {
            let res;
            if (isDetach) {
                res = await fetch(`${API_URL}?action=detach_team`, { 
                    method: 'POST', 
                    headers: {'Content-Type': 'application/json'}, 
                    body: JSON.stringify({id, tournament_id: filter}) 
                });
            } else {
                res = await fetch(`${API_URL}?action=${action}`, { 
                    method: 'POST', 
                    headers: {'Content-Type': 'application/json'}, 
                    body: JSON.stringify({id}) 
                });
            }
            
            const json = await res.json();
            
            // Evaluamos si el servidor nos respondió con éxito
            if(json.status === 'success') {
                Swal.fire('¡Listo!', json.message, 'success');
                loadFilteredData(filter);
                if(action === 'delete_tournament') loadTournamentsList();
            } else {
                // Si el backend mandó un error (como el 1451 de MySQL)
                Swal.fire({
                    title: 'No se pudo eliminar',
                    text: json.message, // Aquí se mostrará tu texto personalizado
                    icon: 'error',
                    confirmButtonColor: '#3085d6'
                });
            }
        } catch (e) {
            Swal.fire('Error crítico', 'Hubo un problema de conexión con el servidor.', 'error');
        }
    }
}

async function loadUsersList() {
    showTableSkeleton('tableUsers', 4);
    try {
        const res = await fetch(`${API_URL}?action=get_users`);
        const json = await res.json();
        if (json.status === 'success') {
            allUsers = json.data;
            pageUsers = 1;
            renderUsersTable();
        }
    } catch (e) { console.error("Error cargando usuarios:", e); }
}

function renderUsersTable() {
    renderPaginatedList(allUsers, pageUsers, 'tableUsers', 'paginationUsers', rowUserTemplate, (newPage) => {
        pageUsers = newPage; renderUsersTable();
    });
}

const rowUserTemplate = (u) => {
    const roleBadge = u.role === 'superadmin' 
        ? '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger">Super Admin</span>' 
        : '<span class="badge bg-primary bg-opacity-10 text-primary border border-primary">Coach</span>';
    
    const teamDisplay = u.team_name 
        ? `<span class="fw-bold text-dark"><i class="bi bi-shield-fill text-muted me-1"></i>${u.team_name}</span>` 
        : '<span class="text-muted small fst-italic">Acceso Global</span>';

    return `
    <tr>
        <td class="ps-4 fw-bold text-dark"><i class="bi bi-person-circle text-muted fs-5 me-2 align-middle"></i>${u.username}</td>
        <td>${roleBadge}</td>
        <td>${teamDisplay}</td>
        <td class="text-end pe-4">
            <div class="btn-action-group shadow-sm">
                <button class="btn btn-icon text-secondary" onclick="editUser(${u.id})" title="Editar"><i class="bi bi-pencil"></i></button>
                <button class="btn btn-icon text-danger" onclick="deleteItem('delete_user', ${u.id})" title="Eliminar"><i class="bi bi-trash"></i></button>
            </div>
        </td>
    </tr>`;
};


// 3. Funciones del Modal de Usuario
function toggleUserTeam() {
    const role = document.getElementById('user_role').value;
    const teamSelect = document.getElementById('user_team_id');
    if (role === 'superadmin') {
        teamSelect.value = '';
        teamSelect.disabled = true;
    } else {
        teamSelect.disabled = false;
    }
}

function openUserModal() {
    const form = document.getElementById('formUser');
    form.reset();
    document.getElementById('user_id').value = '';
    document.getElementById('titleUser').innerText = 'Nuevo Usuario';
    document.getElementById('user_password').required = true;
    document.getElementById('passHelp').innerText = '(Obligatorio)';
    
    // Rellenamos los equipos usando el array global allTeams
    fillSelectTeams(allTeams, 'user_team_id', false); 
    toggleUserTeam();
    
    new bootstrap.Modal(document.getElementById('modalUser')).show();
}

function editUser(id) {
    const u = allUsers.find(x => x.id == id);
    if(!u) return;
    
    document.getElementById('user_id').value = u.id;
    document.getElementById('user_username').value = u.username;
    document.getElementById('user_password').value = '';
    document.getElementById('user_password').required = false;
    document.getElementById('passHelp').innerText = '(Dejar vacío para no cambiar)';
    document.getElementById('user_role').value = u.role;
    
    fillSelectTeams(allTeams, 'user_team_id', false);
    document.getElementById('user_team_id').value = u.team_id || '';
    
    document.getElementById('titleUser').innerText = 'Editar Usuario';
    toggleUserTeam();
    
    new bootstrap.Modal(document.getElementById('modalUser')).show();
}


// --- MODALES & FORMS SETUP ---
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
        const currentFilter = document.getElementById('dashboardFilterTournament').value;
        const selectTourn = document.getElementById('selectTournamentForTeam');
        if(selectTourn) selectTourn.value = currentFilter; 
    }
    
    // Resetear foto de jugador si abrimos el modal de jugador
    if(id === 'modalPlayer') {
        const img = document.getElementById('previewPlayerPhoto');
        if(img) img.src = 'https://placehold.co/80?text=Foto';
        document.getElementById('titlePlayer').innerText = 'Nuevo Jugador';
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
    
    const currentFilter = document.getElementById('dashboardFilterTournament').value;
    const selectTourn = document.getElementById('selectTournamentForTeam');
    if(selectTourn) selectTourn.value = currentFilter;

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
    
    // Cargar la foto actual en la vista previa del modal
    const img = document.getElementById('previewPlayerPhoto');
    if(img) {
        img.src = item.photo_url ? `../${item.photo_url}` : 'https://placehold.co/80?text=Foto';
    }
    
    document.getElementById('titlePlayer').innerText = 'Editar Jugador';
    new bootstrap.Modal(document.getElementById('modalPlayer')).show();
}

function setupForms() {
    ['formTournament', 'formTeam', 'formPlayer', 'formUser'].forEach(id => {
        const formEl = document.getElementById(id);
        if(!formEl) return;
        
        formEl.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const isUpdate = formData.get('id');
            let actionPrefix = isUpdate ? 'update_' : (id === 'formPlayer' ? 'add_' : 'create_');
            let entity = id.replace('form','').toLowerCase();
            if(id === 'formPlayer') entity = 'player';
            let finalAction = actionPrefix + entity;

            try {
                const res = await fetch(`${API_URL}?action=${finalAction}`, { method: 'POST', body: formData });
                const json = await res.json();
                
                if(json.status === 'success') {
                    bootstrap.Modal.getInstance(document.querySelector(`#${id}`).closest('.modal')).hide();
                    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: json.message, showConfirmButton: false, timer: 3000 });
                    
                    loadTournamentsList();
                    loadFilteredData(document.getElementById('dashboardFilterTournament').value);
                    if(id === 'formUser') loadUsersList();
                } else {
                    Swal.fire('Error', json.message, 'error');
                }
            } catch (err) { Swal.fire('Error', 'Fallo al enviar datos.', 'error'); }
        });
    });

    const formFixture = document.getElementById('formFixture');
    if (formFixture) {
        formFixture.addEventListener('submit', async (e) => {
            e.preventDefault();
            const tournamentId = parseInt(document.getElementById('fix_tourn_id').value);
            
            // ACTUALIZADO: Agregamos las dos nuevas variables
            const config = {
                vueltas: document.getElementById('fix_vueltas').value,
                pts_victoria: document.getElementById('fix_win').value,
                pts_derrota: document.getElementById('fix_loss').value,
                pts_empate: document.getElementById('fix_draw').value,
                pts_forfeit_win: document.getElementById('fix_forfeit_win').value,
                pts_forfeit_loss: document.getElementById('fix_forfeit_loss').value
            };

            const btnSubmit = formFixture.querySelector('button[type="submit"]');
            const originalText = btnSubmit.innerHTML;
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Generando...';

            try {
                const payload = { action: 'generate_fixture', tournament_id: tournamentId, config: config };
                const res = await fetch(API_URL, { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(payload) });
                const json = await res.json();
                
                if(json.status === 'success') {
                    bootstrap.Modal.getInstance(document.getElementById('modalFixtureConfig')).hide();
                    await Swal.fire('¡Éxito!', 'Calendario generado exitosamente', 'success');
                    window.location.href = `fixture.php?id=${tournamentId}`;
                } else { 
                    Swal.fire('Error', json.message, 'error'); 
                }
            } catch (err) { Swal.fire('Error', 'Error de conexión.', 'error'); } 
            finally { btnSubmit.disabled = false; btnSubmit.innerHTML = originalText; }
        });
    }
}

function fillSelect(id, items, empty) {
    const s = document.getElementById(id); if(!s) return;
    s.innerHTML = (empty ? '<option value="">-- Seleccionar --</option>' : '') + items.map(i => `<option value="${i.id}">${i.name}</option>`).join('');
}

function fillSelectTeams(items, id, all) {
    const s = document.getElementById(id); if(!s) return;
    s.innerHTML = (all ? '<option value="0">Todos los Equipos</option>' : '<option value="">Seleccione Equipo...</option>') + items.map(i => `<option value="${i.id}">${i.name}</option>`).join('');
}

// --- FUNCIONES DE GALERÍA ---
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
    input.value = ''; 
    
    try {
        const res = await fetch(API_URL, { method: 'POST', body: formData });
        const json = await res.json();
        if(json.status === 'success') {
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Imagen subida', showConfirmButton: false, timer: 3000 });
            loadGallery(); 
        } else {
            Swal.fire('Error', json.message, 'error');
        }
    } catch(e) { Swal.fire('Error', 'Error al subir imagen', 'error'); }
}

async function deleteGalleryImage(filename) {
    const result = await Swal.fire({
        title: '¿Eliminar imagen?',
        text: "Se borrará del slider público",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar'
    });

    if(result.isConfirmed) {
        try {
            const res = await fetch(`${API_URL}?action=delete_slider_image`, { 
                method: 'POST', 
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({filename: filename})
            });
            const json = await res.json();
            if(json.status === 'success') {
                Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Imagen eliminada', showConfirmButton: false, timer: 3000 });
                loadGallery(); 
            } else {
                Swal.fire('Error', json.message, 'error');
            }
        } catch(e) { Swal.fire('Error', 'Error al eliminar', 'error'); }
    }
}