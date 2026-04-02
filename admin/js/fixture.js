const urlParams = new URLSearchParams(window.location.search);
const TOURNAMENT_ID = parseInt(urlParams.get('id')) || 0;
const API_URL = '../api.php';
let venuesData = [];

// --- ESTADO PARA EL ALGORITMO Y UI ---
let tournamentTeams = [];
let playedMatchups = {}; 
let matchesByRound = {}; 
let teamGamesCount = {}; 

let manualSelectedA = null;
let manualSelectedB = null;

document.addEventListener("DOMContentLoaded", () => {
    loadVenues();
    loadTeams(); 
    loadFixture();
    setupEditForm();
    setupManualMatchForm(); 
    
    // Cerrar dropdowns si se hace clic fuera
    document.addEventListener('click', (e) => {
        if(!e.target.closest('.custom-dropdown-container')) {
            const mA = document.getElementById('menuDropdownA');
            const mB = document.getElementById('menuDropdownB');
            if(mA) mA.classList.remove('show');
            if(mB) mB.classList.remove('show');
        }
    });
});

async function loadVenues() {
    try {
        const res = await fetch(`${API_URL}?action=get_data`);
        const json = await res.json();
        if(json.status === 'success') venuesData = json.data.venues;
    } catch(e) {}
}

async function loadTeams() {
    try {
        const res = await fetch(`${API_URL}?action=get_data_by_tournament&tournament_id=${TOURNAMENT_ID}`);
        const json = await res.json();
        if(json.status === 'success') tournamentTeams = json.data.teams;
    } catch(e) {}
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
            
            matchesByRound = rounds;
            playedMatchups = {};
            teamGamesCount = {};
            
            // Inicializar contadores
            tournamentTeams.forEach(t => teamGamesCount[t.id] = 0);

            for (const [roundName, matches] of Object.entries(rounds)) {
                matches.forEach(m => {
                    if(m.status === 'CANCELLED') return;
                    let tA = parseInt(m.team_a_id);
                    let tB = parseInt(m.team_b_id);
                    
                    // Historial de enfrentamientos
                    if(!playedMatchups[tA]) playedMatchups[tA] = [];
                    if(!playedMatchups[tB]) playedMatchups[tB] = [];
                    playedMatchups[tA].push(tB);
                    playedMatchups[tB].push(tA);

                    // Conteo total de juegos
                    teamGamesCount[tA] = (teamGamesCount[tA] || 0) + 1;
                    teamGamesCount[tB] = (teamGamesCount[tB] || 0) + 1;
                });
            }
            renderRounds(rounds);
        }
    } catch(e) { showError("Error de conexión con el servidor."); }
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
                <p class="text-muted small">Haz clic en 'Partido Manual' arriba a la derecha para empezar a construir.</p>
            </div>`;
        return;
    }

    let isFirst = true;
    for (const [roundName, matches] of Object.entries(rounds)) {
        const safeId = roundName.replace(/[^a-zA-Z0-9]/g, '_');
        const activeClass = isFirst ? 'active' : '';
        const showClass = isFirst ? 'show active' : '';

        tabsContainer.innerHTML += `
            <button class="nav-link ${activeClass}" data-bs-toggle="pill" data-bs-target="#tab_${safeId}" type="button">${roundName}</button>`;

        let matchesHtml = matches.map(m => {
            let dateDisplay = '<span class="text-muted fst-italic" style="font-size:0.75rem">Sin fecha</span>';
            let timeDisplay = '';
            
            if(m.scheduled_datetime) {
                const date = new Date(m.scheduled_datetime);
                const day = date.toLocaleDateString('es-ES', { weekday: 'short', day: 'numeric', month: 'short' });
                const time = date.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
                dateDisplay = `<span class="fw-bold text-dark"><i class="bi bi-calendar3 me-1"></i>${day}</span>`;
                timeDisplay = `<div class="match-time mt-1">${time}</div>`;
            }

            const logoA = m.logo_a ? `../${m.logo_a}` : `https://ui-avatars.com/api/?name=${encodeURIComponent(m.team_a)}&background=f1f5f9&color=64748b`;
            const logoB = m.logo_b ? `../${m.logo_b}` : `https://ui-avatars.com/api/?name=${encodeURIComponent(m.team_b)}&background=f1f5f9&color=64748b`;

            let statusBadge = '<span class="match-status status-scheduled">Pendiente</span>';
            let isFinished = false;

            if(m.status === 'FINISHED') { statusBadge = '<span class="match-status status-finished">Finalizado</span>'; isFinished = true; }
            if(m.status === 'PLAYING') { statusBadge = '<span class="match-status status-playing">En Juego</span>'; isFinished = true; }
            if(m.status === 'CANCELLED') statusBadge = '<span class="match-status bg-danger text-white">Cancelado</span>';

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

            let pdfButton = '';
            if(m.pdf_url) pdfButton = `<a href="${m.pdf_url}" target="_blank" class="action-btn btn-pdf" data-bs-toggle="tooltip" title="Ver Acta PDF"><i class="bi bi-filetype-pdf"></i></a>`;

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
                        <div class="match-info">${centerContent}</div>
                        <div class="team-container">
                            <img src="${logoB}" class="team-logo rounded-3" onerror="this.src='https://placehold.co/60x60?text=B'">
                            <div class="team-name" title="${m.team_b}">${m.team_b}</div>
                        </div>
                    </div>
                    <div class="match-footer mt-2">
                        <div class="d-flex flex-column">
                            ${dateDisplay}
                            <small class="text-truncate mt-1" style="max-width: 160px;"><i class="bi bi-geo-alt-fill text-danger me-1"></i>${m.venue_name || 'Sede por definir'}</small>
                        </div>
                        <div class="d-flex gap-2">
                            ${pdfButton}
                            <button class="action-btn" data-bs-toggle="tooltip" title="Configurar Partido" onclick='openEditModal(${matchJson})'><i class="bi bi-gear"></i></button>
                        </div>
                    </div>
                </div>
            </div>`;
        }).join('');

        contentContainer.innerHTML += `
            <div class="tab-pane fade ${showClass}" id="tab_${safeId}">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-muted mb-0">Partidos de la ${roundName}</h5>
                    <button class="btn btn-dark btn-sm fw-bold shadow-sm" onclick="previewFlyer('${roundName}')">
                        <i class="bi bi-palette text-info me-1"></i> Crear Póster
                    </button>
                </div>
                <div class="row">${matchesHtml}</div>
            </div>`;
        isFirst = false;
    }

    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
}

function toggleCustomDropdown(side) {
    const otherSide = side === 'A' ? 'B' : 'A';
    document.getElementById(`menuDropdown${otherSide}`).classList.remove('show');
    document.getElementById(`menuDropdown${side}`).classList.toggle('show');
}

function selectCustomTeam(side, teamId, teamName) {
    if(side === 'A') manualSelectedA = teamId;
    if(side === 'B') manualSelectedB = teamId;
    document.getElementById(`valDropdown${side}`).value = teamId;
    const btnText = document.getElementById(`textDropdown${side}`);
    btnText.innerText = teamName;
    btnText.style.color = "white"; 
    document.getElementById(`menuDropdown${side}`).classList.remove('show');
    updateCustomDropdowns();
    validateManualMatch();
}

function handleRoundChange() {
    updateCustomDropdowns();
    validateManualMatch();
}

function updateCustomDropdowns() {
    renderCustomDropdown('A', manualSelectedA, manualSelectedB);
    renderCustomDropdown('B', manualSelectedB, manualSelectedA);
}

function renderCustomDropdown(side, mySelection, otherSelection) {
    const menu = document.getElementById(`menuDropdown${side}`);
    if(!menu) return;
    let roundNum = document.getElementById('manual_round').value || 1;
    let roundName = "Jornada " + roundNum;
    
    let playedThisRound = [];
    if(matchesByRound[roundName]) {
        matchesByRound[roundName].forEach(m => {
            if(m.status !== 'CANCELLED') {
                playedThisRound.push(parseInt(m.team_a_id));
                playedThisRound.push(parseInt(m.team_b_id));
            }
        });
    }

    let html = '';
    tournamentTeams.forEach(t => {
        const teamId = parseInt(t.id);
        let isSameAsOther = teamId === otherSelection;
        let playedRound = playedThisRound.includes(teamId);
        let playedAgainstOther = otherSelection && playedMatchups[otherSelection] && playedMatchups[otherSelection].includes(teamId);
        
        let isDisabled = isSameAsOther;
        
        let dotClass = 'dot-green';
        let badgeClass = 'badge-green';
        let badgeIcon = 'bi-check2-circle';
        let badgeText = `JJ: ${teamGamesCount[teamId] || 0}`;

        if (isSameAsOther) {
            dotClass = 'dot-orange'; badgeClass = 'badge-orange'; badgeIcon = 'bi-exclamation-triangle'; badgeText = 'EN USO';
        } else if (playedAgainstOther) {
            dotClass = 'dot-red'; badgeClass = 'badge-red'; badgeIcon = 'bi-exclamation-circle'; badgeText = 'YA ENFRENTADOS';
        } else if (playedRound) {
            dotClass = 'dot-red'; badgeClass = 'badge-red'; badgeIcon = 'bi-calendar-x'; badgeText = 'JUGÓ EN JORNADA';
        }

        html += `
            <div class="custom-option ${isDisabled ? 'disabled' : ''}" 
                 ${!isDisabled ? `onclick="selectCustomTeam('${side}', ${teamId}, '${t.name.replace(/'/g, "\\'")}')"` : ''}>
                <div class="option-left">
                    <div class="status-dot ${dotClass}"></div>
                    <span class="option-name">${t.name}</span>
                </div>
                <div class="badge-status ${badgeClass}">
                    <i class="bi ${badgeIcon}"></i> ${badgeText}
                </div>
            </div>
        `;
    });
    menu.innerHTML = html;
}

function openManualMatchModal() {
    document.getElementById('manual_tourn_id').value = TOURNAMENT_ID;
    manualSelectedA = null;
    manualSelectedB = null;
    document.getElementById('valDropdownA').value = '';
    document.getElementById('valDropdownB').value = '';
    document.getElementById('textDropdownA').innerText = '-- Selecciona Equipo --';
    document.getElementById('textDropdownA').style.color = "";
    document.getElementById('textDropdownB').innerText = '-- Selecciona Equipo --';
    document.getElementById('textDropdownB').style.color = "";
    
    let latestRound = 1;
    if(Object.keys(matchesByRound).length > 0) {
        let roundsStr = Object.keys(matchesByRound);
        roundsStr.forEach(r => {
            let match = r.match(/\d+/);
            if(match) {
                let num = parseInt(match[0]);
                if(num > latestRound) latestRound = num;
            }
        });
    }
    document.getElementById('manual_round').value = latestRound;
    
    // El botón se inicia apagado hasta que se seleccionen equipos
    document.getElementById('btn_save_manual').disabled = true;
    
    updateCustomDropdowns();
    new bootstrap.Modal(document.getElementById('modalManualMatch')).show();
}

function validateManualMatch() {
    let teamA = manualSelectedA;
    let teamB = manualSelectedB;
    let roundNum = document.getElementById('manual_round').value;
    
    let btn = document.getElementById('btn_save_manual');
    btn.disabled = true;
    
    if(!teamA || !teamB || !roundNum) return; 
    
    // Al quitar la validación restrictiva, se permite armar cualquier duelo libremente
    btn.disabled = false;
}

function setupManualMatchForm() {
    document.getElementById('formManualMatch').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = e.target.querySelector('button[type="submit"]');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';

        const formData = new FormData(e.target);
        const data = {};
        formData.forEach((value, key) => data[key] = value);
        data.action = 'add_manual_fixture'; 

        try {
            const res = await fetch(API_URL, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            });
            const json = await res.json();

            if(json.status === 'success') {
                bootstrap.Modal.getInstance(document.getElementById('modalManualMatch')).hide();
                Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Partido Creado', showConfirmButton: false, timer: 3000 });
                loadFixture();
            } else {
                Swal.fire('Error', json.message, 'error');
            }
        } catch(err) {
            Swal.fire('Error', 'Fallo de conexión.', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    });
}

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
                Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Actualizado', showConfirmButton: false, timer: 3000 });
                loadFixture();
            } else {
                Swal.fire('Error', json.message, 'error');
            }
        } catch(err) {
            Swal.fire('Error', 'Fallo de conexión.', 'error');
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