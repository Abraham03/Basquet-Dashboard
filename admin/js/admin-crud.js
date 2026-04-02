const API_URL = '../api.php';

// Estado Global de Datos
let allTournaments = [];
let allTeams = [];
let allPlayers = [];
let filteredPlayers = [];

let allUsers = [];
let pageUsers = 1;

let allVenues = [];
let pageVenues = 1;

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
    loadVenuesList();

    // Escuchar el cambio de pestañas para ocultar la barra de filtros si estamos en Galería
    document.querySelectorAll('button[data-bs-toggle="pill"], a[data-bs-toggle="pill"]').forEach(tab => {
        tab.addEventListener('shown.bs.tab', event => {
            const targetId = event.target.getAttribute('data-bs-target');
            const filterBar = document.getElementById('mainFilterBar');
            if(filterBar) filterBar.style.display = (targetId === '#tab-gallery') ? 'none' : 'flex';
            if(targetId === '#tab-gallery') {
            loadGallery();
        }
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

function fillTournamentsSelect(selectId, tournaments, hasAllOption = false) {
    const select = document.getElementById(selectId);
    if (!select) return;

    let html = hasAllOption ? '<option value="0">⭐ Ver Todos los Torneos</option>' : '<option value="">-- Seleccionar Torneo --</option>';
    
    tournaments.forEach(t => {
        const categoryLabel = t.category ? ` (${t.category})` : '';
        html += `<option value="${t.id}">${t.name}${categoryLabel}</option>`;
    });
    
    select.innerHTML = html;
}

// --- CARGA DE DATOS ---
async function loadTournamentsList() {
    showTableSkeleton('tableTournaments', 5);
    try {
        const res = await fetch(`${API_URL}?action=get_tournaments_list`);
        const json = await res.json();
        if (json.status === 'success') {
            allTournaments = json.data;
            pageTournaments = 1; 
            renderTournamentsTable(); 
            
            // Actualizar select de filtro principal y select de asignación en equipos
            fillTournamentsSelect('dashboardFilterTournament', allTournaments, true);
            fillTournamentsSelect('selectTournamentForTeam', allTournaments, false);
            
            loadGallery();
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
    if(select) {
        const tournamentId = select.value;
        
        // Carga equipos y jugadores (lo que ya hacía)
        loadFilteredData(tournamentId);
        
        // NUEVO: Si estamos en la pestaña de galería o si queremos que se 
        // actualice en segundo plano, llamamos a loadGallery
        loadGallery(); 
    }
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

async function loadVenuesList() {
    showTableSkeleton('tableVenues', 4);
    try {
        // Aprovechamos el endpoint get_data que ya tienes y que devuelve 'venues'
        const res = await fetch(`${API_URL}?action=get_data`);
        const json = await res.json();
        if (json.status === 'success') {
            allVenues = json.data.venues;
            pageVenues = 1;
            renderVenuesTable();
        }
    } catch (e) { console.error("Error cargando sedes:", e); }
}

function editVenue(id) {
    const v = allVenues.find(x => x.id == id);
    if(!v) return;
    
    document.getElementById('venue_id').value = v.id;
    document.getElementById('venue_name').value = v.name;
    document.getElementById('venue_address').value = v.address || '';
    
    document.getElementById('titleVenue').innerText = 'Editar Sede';
    new bootstrap.Modal(document.getElementById('modalVenue')).show();
}

function renderVenuesTable() {
    renderPaginatedList(allVenues, pageVenues, 'tableVenues', 'paginationVenues', rowVenueTemplate, (newPage) => {
        pageVenues = newPage; renderVenuesTable();
    });
}

const rowVenueTemplate = (v) => `
    <tr>
        <td class="ps-4 fw-bold text-muted small">#${v.id}</td>
        <td class="fw-bold text-dark"><i class="bi bi-geo-alt-fill text-danger me-2 opacity-75"></i>${v.name}</td>
        <td class="text-muted small">${v.address || 'Sin especificar'}</td>
        <td class="text-end pe-4">
            <div class="btn-action-group shadow-sm">
                <button class="btn btn-icon text-secondary" onclick="editVenue(${v.id})" title="Editar Sede"><i class="bi bi-pencil"></i></button>
                <button class="btn btn-icon text-danger" onclick="deleteItem('delete_venue', ${v.id})" title="Eliminar"><i class="bi bi-trash"></i></button>
            </div>
        </td>
    </tr>`;


const rowTournamentTemplate = (t) => {
    const imgUrl = t.logo_url ? `../${t.logo_url}` : `https://ui-avatars.com/api/?name=${encodeURIComponent(t.name)}&background=random&color=fff&size=128`;
    
    return `
    <tr>
        <td class="ps-4 text-muted small fw-bold">#${t.id}</td>
        <td>
            <div class="d-flex align-items-center">
                <img src="${imgUrl}" alt="${t.name}" class="team-logo-table me-3 shadow-sm" onerror="this.onerror=null; this.src='https://placehold.co/48?text=TR';">
                <span class="fw-bold text-dark">${t.name}</span>
            </div>
        </td>
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
};

const rowTeamTemplate = (t) => {
    const imgUrl = t.logo_url ? `../${t.logo_url}` : `https://ui-avatars.com/api/?name=${encodeURIComponent(t.name)}&background=random&color=fff&size=128`;
    
    // Solo mostrar botón de descarga de actas si el admin ha seleccionado un torneo específico
    const currentFilter = document.getElementById('dashboardFilterTournament').value;
    let downloadBtn = '';
    if (currentFilter != 0) {
        downloadBtn = `<button class="btn btn-icon text-success" onclick="downloadTeamReports(${t.id})" data-bs-toggle="tooltip" title="Descargar Todas las Actas (ZIP)"><i class="bi bi-file-earmark-zip-fill fs-5"></i></button>`;
    }

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
                ${downloadBtn}
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

// --- FUNCION DE DESCARGA ZIP ---
async function downloadTeamReports(teamId) {
    const tournamentId = document.getElementById('dashboardFilterTournament').value;
    
    if(tournamentId == 0) {
        Swal.fire('Atención', 'Por favor, selecciona un torneo en el filtro superior primero.', 'warning');
        return;
    }

    Swal.fire({
        title: 'Recopilando Actas...',
        text: 'Generando archivo comprimido, esto puede tardar un momento.',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    try {
        const res = await fetch(`${API_URL}?action=download_team_reports`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({team_id: teamId, tournament_id: tournamentId})
        });
        
        const json = await res.json();

        if (json.status === 'success') {
            Swal.close();
            // Forzar descarga silenciosa mediante la creación de un enlace <a> temporal
            const link = document.createElement('a');
            link.href = json.data.download_url;
            link.setAttribute('download', '');
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        } else {
            Swal.fire({ title: 'Aviso', text: json.message, icon: 'info' });
        }
    } catch (e) {
        Swal.fire('Error', 'Hubo un problema de conexión al generar el archivo.', 'error');
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
    
    if(id === 'modalTournament') {
        document.getElementById('previewTournLogo').src = 'https://placehold.co/80?text=Logo';
        document.getElementById('previewArbitroLogo').src = 'https://placehold.co/80?text=Ref';
        document.getElementById('titleTournament').innerText = 'Nuevo Torneo';
    }
    
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
    
    // Cargar Previsualización de Logo Torneo
    const imgTourn = document.getElementById('previewTournLogo');
    if(imgTourn) {
        imgTourn.src = item.logo_url ? `../${item.logo_url}` : 'https://placehold.co/80?text=Logo';
    }

    // Cargar Previsualización de Logo Árbitro (guardado en url_arbitro)
    const imgRef = document.getElementById('previewArbitroLogo');
    if(imgRef) {
        imgRef.src = item.url_arbitro ? `../${item.url_arbitro}` : 'https://placehold.co/80?text=Ref';
    }

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
    const selectTourn = document.getElementById('selectTournamentForTeam').disabled = true;
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
    ['formTournament', 'formTeam', 'formPlayer', 'formUser', 'formVenue'].forEach(id => {
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
                    if(id === 'formVenue') loadVenuesList();
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
    const tournamentId = document.getElementById('dashboardFilterTournament').value;
    const container = document.getElementById('galleryContainer');
    
    if (!tournamentId || tournamentId == 0) {
        container.innerHTML = `
            <div class="col-12 text-center py-5 text-muted">
                <i class="bi bi-funnel fs-1 d-block mb-3" style="opacity: 0.3;"></i>
                <h4 class="fw-bold">Galería por Torneo</h4>
                <p>Selecciona un torneo en la parte superior para administrar sus fotos de portada.</p>
            </div>`;
        return;
    }

    container.innerHTML = '<div class="col-12 text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-2">Cargando slider del torneo...</p></div>';
    
    try {
        const res = await fetch(`${API_URL}?action=get_slider_images&tournament_id=${tournamentId}`);
        const json = await res.json();
        
        if (json.status === 'success') {
            if(json.data.length === 0) {
                container.innerHTML = `
                    <div class="col-12 text-center py-5 text-muted">
                        <i class="bi bi-images fs-1 d-block mb-3" style="opacity: 0.3;"></i>
                        <h5 class="fw-bold">Sin imágenes</h5>
                        <p>Este torneo aún no tiene fotos en el slider público.</p>
                    </div>`;
                return;
            }

            container.innerHTML = json.data.map(img => `
                <div class="col-md-4 col-lg-3 fade-in">
                    <div class="position-relative border rounded-3 overflow-hidden shadow-sm">
                        <img src="../${img.url}" class="admin-gallery-img" alt="Slider">
                        <button class="btn btn-danger img-delete-btn" onclick="deleteGalleryImage('${img.filename}')" title="Eliminar">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            `).join('');
        }
    } catch(e) { 
        container.innerHTML = '<div class="alert alert-danger mx-3">Error al conectar con el servidor de imágenes.</div>';
    }
}

// Asegúrate de que la función previewImage sea genérica (como la tienes al final de tu script):
function previewImage(input, imgElementId = 'previewLogo') { 
    if (input.files && input.files[0]) { 
        var reader = new FileReader(); 
        reader.onload = function(e) { 
            document.getElementById(imgElementId).src = e.target.result; 
        }; 
        reader.readAsDataURL(input.files[0]); 
    } 
}

async function uploadGalleryImage(input) {
    const tournamentId = document.getElementById('dashboardFilterTournament').value;
    
    // VALIDACIÓN: Si no hay torneo seleccionado (valor 0)
    if (!tournamentId || tournamentId == 0) {
        Swal.fire({
            title: 'Torneo no seleccionado',
            text: 'Por favor, selecciona un torneo en el filtro superior antes de subir imágenes para el slider.',
            icon: 'warning',
            confirmButtonColor: '#FF5722'
        });
        input.value = ''; // Limpiar el input file
        return;
    }

    if (!input.files || input.files.length === 0) return;
    
    // Mostrar loading
    Swal.fire({
        title: 'Subiendo imagen...',
        didOpen: () => { Swal.showLoading(); },
        allowOutsideClick: false
    });

    const formData = new FormData();
    formData.append('image', input.files[0]);
    formData.append('tournament_id', tournamentId);
    formData.append('action', 'upload_slider_image');
    input.value = ''; 
    
    try {
        const res = await fetch(API_URL, { method: 'POST', body: formData });
        const json = await res.json();
        if(json.status === 'success') {
            Swal.fire({ 
                toast: true, 
                position: 'top-end', 
                icon: 'success', 
                title: 'Imagen subida al torneo', 
                showConfirmButton: false, 
                timer: 3000 
            });
            loadGallery(); // Recargar la vista actual
        } else {
            Swal.fire('Error', json.message, 'error');
        }
    } catch(e) { 
        Swal.fire('Error', 'Error de conexión al subir la imagen', 'error'); 
    }
}

// =========================================================
// --- EXPLORADOR DE ARCHIVOS PROFESIONAL ---
// =========================================================

let currentFileFolder = '';
let selectedFilesList = [];

function loadFilesDashboard() {
    document.getElementById('viewFolders').style.display = 'flex';
    document.getElementById('viewFiles').style.display = 'none';
    document.getElementById('fileManagerActions').style.display = 'none';
    
    document.getElementById('fileManagerTitle').innerText = 'Archivos y Documentos';
    document.getElementById('fileManagerSubtitle').innerText = 'Explora las actas y reportes generados en el servidor.';
    currentFileFolder = '';
    resetFileSelection();
}

async function openFolder(folderPath, folderDisplayName) {
    document.getElementById('viewFolders').style.display = 'none';
    document.getElementById('viewFiles').style.display = 'block';
    document.getElementById('fileManagerActions').style.display = 'flex';
    
    document.getElementById('fileManagerTitle').innerText = folderDisplayName;
    document.getElementById('fileManagerSubtitle').innerText = `/assets/${folderPath}/`;
    
    currentFileFolder = folderPath;
    resetFileSelection();
    
    const tbody = document.getElementById('tableFiles');
    tbody.innerHTML = `
        <tr>
            <td colspan="5" class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
            </td>
        </tr>`;

    try {
        const res = await fetch(`${API_URL}?action=get_files&folder=${folderPath}`);
        const json = await res.json();
        
        if (json.status === 'success') {
            if (json.data.length === 0) {
                // Modificación 1: Diseño limpio y amigable cuando no hay datos
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <div class="text-muted p-4">
                                <i class="bi bi-folder2-open" style="font-size: 3.5rem; opacity: 0.5;"></i>
                                <h5 class="fw-bold mt-3 mb-1">Carpeta Vacía</h5>
                                <p class="small mb-0">Aún no se han generado archivos en este directorio.</p>
                            </div>
                        </td>
                    </tr>`;
                document.getElementById('selectAllFiles').disabled = true;
                return;
            }

            document.getElementById('selectAllFiles').disabled = false;
            
            tbody.innerHTML = json.data.map(item => {
                let icon = '<i class="bi bi-file-earmark fs-4 text-secondary"></i>';
                let isZip = false;
                
                if (item.is_dir) {
                    icon = '<i class="bi bi-folder-fill fs-3 text-warning"></i>';
                } else if (item.name.toLowerCase().endsWith('.pdf')) {
                    icon = '<i class="bi bi-file-earmark-pdf-fill fs-3 text-danger"></i>';
                } else if (item.name.toLowerCase().endsWith('.zip')) {
                    icon = '<i class="bi bi-file-earmark-zip-fill fs-3 text-success"></i>';
                    isZip = true;
                }

                let onClickAction = '';
                if (item.is_dir) onClickAction = `onclick="openFolder('${item.path}', '${item.name}')"`;
                else if (isZip) onClickAction = `onclick="triggerDownload('${item.url}')"`;
                else onClickAction = `onclick="window.open('${item.url}', '_blank')"`;

                let actionBtns = '';
                if (item.is_dir) {
                    actionBtns = `<button class="btn btn-sm btn-light border fw-bold text-muted" ${onClickAction}>Abrir <i class="bi bi-arrow-right ms-1"></i></button>`;
                } else {
                    actionBtns = `<a href="${item.url}" download class="btn btn-icon text-primary" title="Descargar"><i class="bi bi-download"></i></a>`;
                }

                // Agregamos el botón de eliminar a TODOS (Carpetas y Archivos)
                actionBtns += `<button class="btn btn-icon text-danger ms-1" onclick="deleteSystemFile('${item.path}', ${item.is_dir})" title="Eliminar"><i class="bi bi-trash"></i></button>`;

                return `
                <tr class="clickable-row">
                    <td class="ps-4" onclick="event.stopPropagation();">
                        <input class="form-check-input file-checkbox" type="checkbox" value="${item.path}" onchange="updateFileSelection()">
                    </td>
                    <td ${onClickAction}>
                        <div class="d-flex align-items-center">
                            <div class="me-3">${icon}</div>
                            <span class="fw-bold text-dark">${item.name}</span>
                        </div>
                    </td>
                    <td class="text-muted fw-bold small" ${onClickAction}>${item.size}</td>
                    <td class="text-muted small" ${onClickAction}>${item.date}</td>
                    <td class="text-end pe-4">
                        <div class="btn-action-group shadow-sm">
                            ${actionBtns}
                        </div>
                    </td>
                </tr>`;
            }).join('');
        }
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger py-4 fw-bold"><i class="bi bi-exclamation-triangle me-2"></i>Error al leer la carpeta.</td></tr>`;
    }
}

function triggerDownload(url) {
    const link = document.createElement('a');
    link.href = url; link.setAttribute('download', '');
    document.body.appendChild(link); link.click(); document.body.removeChild(link);
}

// Lógica de Selección Múltiple
function toggleSelectAllFiles(checkbox) {
    const checkboxes = document.querySelectorAll('.file-checkbox');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
    updateFileSelection();
}

function updateFileSelection() {
    const checkboxes = document.querySelectorAll('.file-checkbox:checked');
    selectedFilesList = Array.from(checkboxes).map(cb => cb.value);
    
    const btnDelete = document.getElementById('btnDeleteMultiple');
    const countSpan = document.getElementById('selectedCount');
    
    countSpan.innerText = selectedFilesList.length;
    if (selectedFilesList.length > 0) {
        btnDelete.classList.remove('d-none');
    } else {
        btnDelete.classList.add('d-none');
        document.getElementById('selectAllFiles').checked = false;
    }
}

function resetFileSelection() {
    selectedFilesList = [];
    document.getElementById('selectAllFiles').checked = false;
    document.getElementById('btnDeleteMultiple').classList.add('d-none');
}

// Eliminación Individual o Múltiple
async function deleteSystemFile(path, isDir = false) {
    executeFileDeletion([path], isDir ? '¿Eliminar carpeta completa?' : '¿Eliminar documento?', isDir ? 'Se borrará la carpeta y todos los archivos en su interior.' : 'Se borrará físicamente del servidor.');
}

async function deleteMultipleFiles() {
    if (selectedFilesList.length === 0) return;
    executeFileDeletion(selectedFilesList, `¿Eliminar ${selectedFilesList.length} elementos?`, 'Las carpetas seleccionadas también serán borradas junto con su contenido.');
}

async function executeFileDeletion(pathsArray, titleText, descText) {
    const result = await Swal.fire({
        title: titleText,
        text: descText,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
        try {
            const res = await fetch(`${API_URL}?action=delete_file`, { 
                method: 'POST', 
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ paths: pathsArray })
            });
            const json = await res.json();
            
            if (json.status === 'success') {
                Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: json.message, showConfirmButton: false, timer: 3000 });
                openFolder(currentFileFolder, document.getElementById('fileManagerTitle').innerText); 
            } else {
                Swal.fire('Error', json.message, 'error');
            }
        } catch (e) {
            Swal.fire('Error', 'Fallo al comunicarse con el servidor.', 'error');
        }
    }
}

async function deleteGalleryImage(filename) {
    const tournamentId = document.getElementById('dashboardFilterTournament').value;
    const result = await Swal.fire({
        title: '¿Eliminar imagen?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar'
    });

    if(result.isConfirmed) {
        try {
            const res = await fetch(`${API_URL}?action=delete_slider_image`, { 
                method: 'POST', 
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({filename: filename, tournament_id: tournamentId})
            });
            loadGallery(); 
        } catch(e) { console.error(e); }
    }
}