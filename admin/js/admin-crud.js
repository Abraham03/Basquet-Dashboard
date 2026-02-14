const API_URL = '../api.php';

let tournamentsData = [];
let currentTeamsData = [];
let currentPlayersData = []; 

document.addEventListener("DOMContentLoaded", () => {
    loadTournamentsList(); 
    loadFilteredData(0); 
    setupForms();
});

// --- LOAD DATA ---
async function loadTournamentsList() {
    try {
        const res = await fetch(`${API_URL}?action=get_tournaments_list`);
        const json = await res.json();
        if (json.status === 'success') {
            tournamentsData = json.data;
            renderTournaments(tournamentsData);
            
            const filterSelect = document.getElementById('dashboardFilterTournament');
            const currentVal = filterSelect.value;
            filterSelect.innerHTML = '<option value="0">⭐ Ver Todos los Torneos</option>';
            tournamentsData.forEach(t => filterSelect.innerHTML += `<option value="${t.id}">${t.name}</option>`);
            if(currentVal) filterSelect.value = currentVal;

            fillSelect('selectTournamentForTeam', tournamentsData, true); 
        }
    } catch (e) { console.error(e); }
}

async function loadFilteredData(tournamentId) {
    document.getElementById('tableTeams').innerHTML = '<tr><td colspan="4" class="text-center py-4 text-muted">Cargando...</td></tr>';
    document.getElementById('tablePlayers').innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">Cargando...</td></tr>';
    
    const subtitle = tournamentId == 0 ? 'Todos los registros' : 'Filtrado por torneo';
    document.getElementById('teamSubtitle').innerText = subtitle;
    document.getElementById('playerSubtitle').innerText = subtitle;

    try {
        const res = await fetch(`${API_URL}?action=get_data_by_tournament&tournament_id=${tournamentId}`);
        const json = await res.json();
        if (json.status === 'success') {
            currentTeamsData = json.data.teams;
            currentPlayersData = json.data.players;
            renderTeams(currentTeamsData);
            renderPlayers(currentPlayersData);
            fillSelectTeams(currentTeamsData, 'selectTeamForPlayer');
            fillSelectTeams(currentTeamsData, 'filterPlayersByTeam', true);
            document.getElementById('filterPlayersByTeam').value = '0';
        }
    } catch (e) { console.error(e); }
}

function filterDashboard() {
    loadFilteredData(document.getElementById('dashboardFilterTournament').value);
}

function applyLocalPlayerFilter() {
    const teamId = document.getElementById('filterPlayersByTeam').value;
    const filtered = teamId == 0 ? currentPlayersData : currentPlayersData.filter(p => p.team_id == teamId);
    renderPlayers(filtered);
}

// --- RENDER TABLES ---
function renderTournaments(list) {
    const tbody = document.getElementById('tableTournaments');
    tbody.innerHTML = list.map(t => `
        <tr>
            <td class="col-id text-muted small fw-bold">${t.id}</td>
            <td class="fw-bold text-dark">${t.name}</td>
            <td><span class="badge bg-secondary bg-opacity-10 text-dark border">${t.category || 'General'}</span></td>
            <td class="text-end pe-4">
                <button class="btn btn-icon text-primary me-1" onclick="editTournament(${t.id})"><i class="bi bi-pencil-square"></i></button>
                <button class="btn btn-icon text-danger" onclick="deleteItem('delete_tournament', ${t.id})"><i class="bi bi-trash"></i></button>
            </td>
        </tr>`).join('');
}

function renderTeams(list) {
    const tbody = document.getElementById('tableTeams');
    if(list.length === 0) { tbody.innerHTML = '<tr><td colspan="4" class="text-center py-5 text-muted"><i class="bi bi-inbox fs-1 d-block mb-2"></i>No hay equipos registrados</td></tr>'; return; }
    
    tbody.innerHTML = list.map(t => {
        // Lógica de Imagen Profesional
        const fallbackAvatar = `https://ui-avatars.com/api/?name=${encodeURIComponent(t.name)}&background=random&color=fff&size=128`;
        const imgUrl = t.logo_url ? `../${t.logo_url}` : fallbackAvatar;
        
        return `
        <tr>
            <td class="col-id text-muted small fw-bold">${t.id}</td>
            <td>
                <div class="d-flex align-items-center">
                    <img src="${imgUrl}" 
                         alt="${t.name}" 
                         class="team-logo-table me-3 shadow-sm"
                         onerror="this.onerror=null; this.src='https://placehold.co/48?text=TM';">
                    
                    <div>
                        <span class="team-name-text">${t.name}</span>
                        <span class="team-meta-text badge bg-light text-secondary border mt-1">
                            ${t.short_name || 'N/A'}
                        </span>
                    </div>
                </div>
            </td>
            <td>
                <div class="d-flex align-items-center text-muted">
                    <i class="bi bi-person-badge me-2"></i>
                    ${t.coach_name || '<span class="small fst-italic">Sin entrenador</span>'}
                </div>
            </td>
            <td class="text-end pe-4">
                <div class="d-inline-flex gap-1">
                    <button class="btn btn-icon text-primary" onclick="editTeam(${t.id})" title="Editar">
                        <i class="bi bi-pencil-square"></i>
                    </button>
                    <button class="btn btn-icon text-danger" onclick="deleteItem('delete_team', ${t.id})" title="Eliminar">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </td>
        </tr>`
    }).join('');
}

function renderPlayers(list) {
    const tbody = document.getElementById('tablePlayers');
    if(list.length === 0) { tbody.innerHTML = '<tr><td colspan="5" class="text-center py-5 text-muted"><i class="bi bi-people fs-1 d-block mb-2"></i>No hay jugadores</td></tr>'; return; }
    
    tbody.innerHTML = list.map(p => {
        const team = currentTeamsData.find(t => t.id == p.team_id);
        const teamName = team ? team.name : '<span class="text-muted small">Sin Equipo</span>';
        return `
        <tr>
            <td class="col-id text-muted small fw-bold">${p.id}</td>
            <td class="fw-bold text-dark">${p.name}</td>
            <td><span class="badge bg-primary rounded-pill px-3">${p.default_number || '#'}</span></td>
            <td class="small fw-medium">${teamName}</td>
            <td class="text-end pe-4">
                <button class="btn btn-icon text-primary me-1" onclick="editPlayer(${p.id})"><i class="bi bi-pencil-square"></i></button>
                <button class="btn btn-icon text-danger" onclick="deleteItem('delete_player', ${p.id})"><i class="bi bi-trash"></i></button>
            </td>
        </tr>`
    }).join('');
}

// --- MODALS & FORMS ---
function openModal(id) {
    const modal = new bootstrap.Modal(document.getElementById(id));
    const form = document.querySelector(`#${id} form`);
    form.reset();
    form.querySelector(`input[name='id']`).value = '';
    
    // Reset logo preview if applicable
    if(id === 'modalTeam') document.getElementById('previewLogo').src = 'https://placehold.co/80?text=Logo';

    if(id==='modalTournament') document.getElementById('titleTournament').innerText = 'Nuevo Torneo';
    if(id==='modalTeam') {
        document.getElementById('titleTeam').innerText = 'Nuevo Equipo';
        const filter = document.getElementById('dashboardFilterTournament').value;
        if(filter != 0) document.getElementById('selectTournamentForTeam').value = filter;
    }
    if(id==='modalPlayer') document.getElementById('titlePlayer').innerText = 'Nuevo Jugador';
    
    modal.show();
}

function editTournament(id) {
    const item = tournamentsData.find(x => x.id == id);
    if(!item) return;
    document.getElementById('tourn_id').value = item.id;
    document.getElementById('tourn_name').value = item.name;
    document.getElementById('tourn_cat').value = item.category;
    document.getElementById('titleTournament').innerText = 'Editar Torneo';
    new bootstrap.Modal(document.getElementById('modalTournament')).show();
}

function editTeam(id) {
    const item = currentTeamsData.find(x => x.id == id);
    if(!item) return;
    document.getElementById('team_id').value = item.id;
    document.getElementById('team_name').value = item.name;
    document.getElementById('team_short').value = item.short_name;
    document.getElementById('team_coach').value = item.coach_name;
    
    // Preview Logo
    document.getElementById('previewLogo').src = item.logo_url ? `../${item.logo_url}` : 'https://placehold.co/80?text=Logo';

    document.getElementById('titleTeam').innerText = 'Editar Equipo';
    new bootstrap.Modal(document.getElementById('modalTeam')).show();
}

function editPlayer(id) {
    const item = currentPlayersData.find(x => x.id == id);
    if(!item) return;
    document.getElementById('player_id').value = item.id;
    document.getElementById('player_name').value = item.name;
    document.getElementById('player_num').value = item.default_number;
    document.getElementById('selectTeamForPlayer').value = item.team_id;
    document.getElementById('titlePlayer').innerText = 'Editar Jugador';
    new bootstrap.Modal(document.getElementById('modalPlayer')).show();
}

// --- SUBMIT ---
function setupForms() {
    handleFormSubmit('formTournament', 'create_tournament', 'update_tournament', '#modalTournament');
    handleFormSubmit('formTeam', 'create_team', 'update_team', '#modalTeam');
    handleFormSubmit('formPlayer', 'add_player', 'update_player', '#modalPlayer');
}

function handleFormSubmit(formId, createAction, updateAction, modalId) {
    document.getElementById(formId).addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const id = formData.get('id');
        const action = id ? updateAction : createAction;

        try {
            const res = await fetch(`${API_URL}?action=${action}`, {
                method: 'POST',
                body: formData 
            });
            const json = await res.json();
            
            if(json.status === 'success') {
                const modal = bootstrap.Modal.getInstance(document.querySelector(modalId));
                modal.hide();
                if (formId === 'formTournament') loadTournamentsList(); 
                else loadFilteredData(document.getElementById('dashboardFilterTournament').value);
            } else {
                alert('Error: ' + json.message);
            }
        } catch (err) { alert('Error de conexión'); }
    });
}

// --- DELETE / DETACH ---
async function deleteItem(action, id) {
    if (action === 'delete_team') {
        const filter = document.getElementById('dashboardFilterTournament').value;
        if (filter != 0) {
            if(!confirm('¿Quitar equipo de este torneo?')) return;
            try {
                await fetch(`${API_URL}?action=detach_team`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ id: id, tournament_id: filter })
                });
                loadFilteredData(filter);
                return;
            } catch (e) { alert('Error'); return; }
        }
    }

    if(!confirm('¿Eliminar permanentemente?')) return;
    try {
        await fetch(`${API_URL}?action=${action}`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ id: id })
        });
        const filter = document.getElementById('dashboardFilterTournament').value;
        loadFilteredData(filter);
        if(action === 'delete_tournament') loadTournamentsList();
    } catch (e) { alert('Error'); }
}

// --- HELPERS ---
function fillSelect(id, items, includeEmpty) {
    const select = document.getElementById(id);
    let opts = includeEmpty ? '<option value="">-- Ninguno --</option>' : '';
    items.forEach(i => opts += `<option value="${i.id}">${i.name}</option>`);
    select.innerHTML = opts;
}
function fillSelectTeams(items, selectId, includeAll) {
    const select = document.getElementById(selectId);
    if(!select) return;
    let opts = includeAll ? '<option value="0">Todos los Equipos</option>' : '<option value="">Seleccione...</option>';
    items.forEach(i => opts += `<option value="${i.id}">${i.name}</option>`);
    select.innerHTML = opts;
}