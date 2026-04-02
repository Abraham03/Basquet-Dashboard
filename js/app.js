const API_URL = 'https://vanball.com.mx/api.php';
let charts = {};

// Autocompletar el año del Footer
document.getElementById('currentYear').textContent = new Date().getFullYear();

document.addEventListener("DOMContentLoaded", () => {
    initThemeToggle(); 
    loadTournaments();
    loadDynamicGallery(); 
    document.getElementById('tournamentSelect').addEventListener('change', (e) => {
    const id = e.target.value;
    loadDashboard(id);
    loadDynamicGallery(id); // <--- Cambia las fotos al cambiar el torneo
});
});
function updateTechSolutionsLogo() {
    const logoImg = document.getElementById('techSolutionsLogo');
    if (!logoImg) return; // Por si no encuentra la imagen

    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    
    // Si es modo oscuro, usamos el logo blanco (logo1.png). Si no, el oscuro (logo2.png)
    if (isDark) {
        logoImg.src = 'assets/imagenes/logo1.png';
    } else {
        logoImg.src = 'assets/imagenes/logo2.png';
    }
}
// --- LÓGICA DEL MODO OSCURO ---
function initThemeToggle() {
    const btn = document.getElementById('themeToggleBtn');
    const icon = document.getElementById('themeIcon');
    const htmlElement = document.documentElement;

    if (localStorage.getItem('theme') === 'dark') {
        htmlElement.setAttribute('data-theme', 'dark');
        icon.classList.replace('bi-moon-fill', 'bi-sun-fill');
    }
    
    // Asegurarnos de que el logo correcto se muestre al cargar la página
    updateTechSolutionsLogo();

    btn.addEventListener('click', () => {
        if (htmlElement.getAttribute('data-theme') === 'dark') {
            htmlElement.removeAttribute('data-theme');
            localStorage.setItem('theme', 'light');
            icon.classList.replace('bi-sun-fill', 'bi-moon-fill');
        } else {
            htmlElement.setAttribute('data-theme', 'dark');
            localStorage.setItem('theme', 'dark');
            icon.classList.replace('bi-moon-fill', 'bi-sun-fill');
        }
        updateChartTheme(); 
        updateTechSolutionsLogo();
    });
}

function updateChartTheme() {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const textColor = isDark ? '#94A3B8' : '#64748B'; 
    const gridColor = isDark ? '#334155' : '#E2E8F0';

    Chart.defaults.color = textColor;
    Chart.defaults.font.family = "'Inter', sans-serif";

    Object.values(charts).forEach(chart => {
        if(chart && chart.options && chart.options.scales) {
            if(chart.options.scales.x) {
                chart.options.scales.x.ticks.color = textColor;
                if(chart.options.scales.x.grid) chart.options.scales.x.grid.color = gridColor;
            }
            if(chart.options.scales.y) {
                chart.options.scales.y.ticks.color = textColor;
                if(chart.options.scales.y.grid) chart.options.scales.y.grid.color = gridColor;
            }
        }
        if(chart) chart.update();
    });
}

// --- CARGA DE DATOS ---
async function loadTournaments() {
    try {
        const res = await fetch(`${API_URL}?action=get_tournaments_list`);
        const json = await res.json();
        const select = document.getElementById('tournamentSelect');
        
        if (json.status === 'success' && json.data.length > 0) {
            // Llenamos el select con Nombre y Categoría
            select.innerHTML = json.data.map(t => {
                const categoryLabel = t.category ? ` (${t.category})` : '';
                return `<option value="${t.id}">${t.name}${categoryLabel}</option>`;
            }).join('');
            
            // VALIDACIÓN INICIAL: Cargamos el dashboard y la galería del primer torneo de la lista
            const firstTournamentId = json.data[0].id;
            loadDashboard(firstTournamentId);
            loadDynamicGallery(firstTournamentId); // <--- Carga inicial de fotos
        } else {
            select.innerHTML = '<option disabled>No hay torneos activos</option>';
        }
    } catch (e) { 
        console.error("Error al cargar lista de torneos:", e); 
    }
}

async function loadDynamicGallery(tournamentId) {
    try {
        // Consultamos al nuevo path que incluye el tournament_id
        const res = await fetch(`${API_URL}?action=get_slider_images&tournament_id=${tournamentId}`);
        const json = await res.json();
        
        const galleryContainer = document.getElementById('basketballGallery');
        const indicators = document.getElementById('galleryIndicators');
        const inner = document.getElementById('galleryInner');

        if (json.status === 'success' && json.data.length > 0) {
            galleryContainer.classList.remove('d-none');
            
            indicators.innerHTML = '';
            const overlayHtml = '<div class="carousel-overlay"></div>';
            let slidesHtml = '';

            json.data.forEach((img, index) => {
                const isActive = index === 0 ? 'active' : '';
                indicators.innerHTML += `<button type="button" data-bs-target="#basketballGallery" data-bs-slide-to="${index}" class="${isActive}"></button>`;
                
                // Usamos img.url que ya viene con la ruta assets/imagenes/torneos/ID/slider/
                slidesHtml += `
                    <div class="carousel-item ${isActive} h-100">
                        <div class="slide-content-wrapper">
                            <img src="${img.url}" class="gallery-bg-blur" alt="bg blur">
                            <img src="${img.url}" class="gallery-img-main" alt="Jugada ${index+1}">
                        </div>
                    </div>
                `;
            });
            
            inner.innerHTML = overlayHtml + slidesHtml;
            
            // Reiniciar el carrusel de Bootstrap para que reconozca los nuevos elementos
            const carousel = new bootstrap.Carousel(galleryContainer);
            carousel.to(0);

        } else {
            // Si el torneo no tiene fotos, ocultamos el componente del slider
            galleryContainer.classList.add('d-none');
        }
    } catch (e) { 
        console.error("Error cargando galería dinámica:", e);
        document.getElementById('basketballGallery').classList.add('d-none');
    }
}

async function loadDashboard(tournamentId) {
    const loader = document.getElementById('loader');
    const content = document.getElementById('dashboardContent');
    const emptyState = document.getElementById('emptyState');

    loader.style.display = 'block';
    content.style.display = 'none';
    content.style.opacity = '0';
    emptyState.style.display = 'none';

    try {
        const statsRes = await fetch(`${API_URL}?action=get_dashboard_stats&tournament_id=${tournamentId}`);
        const statsJson = await statsRes.json();
        
        const fixtureRes = await fetch(`${API_URL}?action=get_fixture&tournament_id=${tournamentId}`);
        const fixtureJson = await fixtureRes.json();

        if (statsJson.status === 'success') {
            const d = statsJson.data;
            
            if ((!d.stats.total_matches || d.stats.total_matches == 0) && (!fixtureJson.data || !fixtureJson.data.rounds)) {
                loader.style.display = 'none';
                emptyState.style.display = 'block';
                return;
            }

            document.getElementById('kpiMatches').innerText = d.stats.total_matches;
            document.getElementById('kpiTeams').innerText = d.stats.total_teams;
            document.getElementById('kpiAvg').innerText = parseFloat(d.stats.avg_points).toFixed(1);

            renderPlayerList('scorersList', d.top_scorers, 'total_points', 'PTS');
            renderPlayerList('triplesList', d.top_triples, 'triples_made', '3PT');
            renderPeriodsChart(d.periods);
            updateChartTheme(); 
            
            renderDefenseTable(d.best_defense);
            renderStandings(d.standings, tournamentId);

            if(fixtureJson.status === 'success' && fixtureJson.data.rounds) {
                renderFixture(fixtureJson.data.rounds);
            } else {
                document.getElementById('fixtureContent').innerHTML = '<div class="alert alert-secondary text-center">Calendario no disponible.</div>';
            }

            loader.style.display = 'none';
            content.style.display = 'block';
            setTimeout(() => content.style.opacity = '1', 50);
        }
    } catch (e) { console.error(e); }
}

function getTeamLogo(teamName, logoUrl) {
    const fallback = `https://ui-avatars.com/api/?name=${encodeURIComponent(teamName)}&background=random&color=fff`;
    const src = logoUrl ? logoUrl.replace('../', '') : fallback;
    return `<img src="${src}" class="team-logo-sm" style="width: 32px; height:32px; padding:0;" onerror="this.src='${fallback}'">`;
}

function getPlayerPhoto(name, photoUrl) {
    const fallback = `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=f1f5f9&color=64748b&size=256&bold=true`;
    return photoUrl ? photoUrl.replace('../', '') : fallback;
}

// Variable global para guardar las jornadas disponibles
let publicRoundsData = {};

function renderFixture(rounds) {
    const contentContainer = document.getElementById('fixtureContent');
    const liveIndicator = document.getElementById('liveIndicator');
    const roundSelect = document.getElementById('roundSelectPublic');
    
    contentContainer.innerHTML = '';
    roundSelect.innerHTML = '';
    publicRoundsData = rounds;
    let hasLiveGames = false;

    if (!rounds || Object.keys(rounds).length === 0) {
        roundSelect.innerHTML = '<option value="">Sin Partidos</option>';
        roundSelect.disabled = true;
        contentContainer.innerHTML = `
            <div class="empty-state">
                <i class="bi bi-calendar-x mb-3 d-block"></i>
                <h4 class="fw-bold text-dark">Sin partidos programados</h4>
                <p class="fs-6 text-muted">El calendario de este torneo aún no ha sido publicado.</p>
            </div>`;
        return;
    }

    roundSelect.disabled = false;
    let activeRoundName = Object.keys(rounds)[0]; // Por defecto la primera
    let foundActive = false;

    // Poblar el Dropdown y buscar partidos en vivo/pendientes
    for (const [roundName, matches] of Object.entries(rounds)) {
        if (matches.some(m => m.status === 'PLAYING')) hasLiveGames = true;
        if (!foundActive && matches.some(m => m.status !== 'FINISHED')) {
            activeRoundName = roundName;
            foundActive = true;
        }
        // Añadir al select
        roundSelect.innerHTML += `<option value="${roundName}">${roundName}</option>`;
    }
    
    if (hasLiveGames) liveIndicator.classList.remove('d-none');
    else liveIndicator.classList.add('d-none');

    // Seleccionar la jornada activa en el Dropdown
    roundSelect.value = activeRoundName;
    
    // Renderizar esa jornada
    renderPublicMatches(activeRoundName);
    
    // Inicializar tooltips
    setTimeout(() => {
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    }, 100);
}

// Nueva función que se ejecuta al cambiar el Select
function changePublicRound(roundName) {
    renderPublicMatches(roundName);
}

// Función que dibuja solo las tarjetas de la jornada seleccionada
function renderPublicMatches(roundName) {
    const contentContainer = document.getElementById('fixtureContent');
    const matches = publicRoundsData[roundName];
    
    if(!matches) return;

    let matchesHtml = matches.map(m => {
        // Formatear Fecha y Hora
        let dateDisplay = '<span style="font-size:0.75rem">Por definir</span>';
        let timeDisplay = '';
        
        if(m.scheduled_datetime) {
            const date = new Date(m.scheduled_datetime);
            const day = date.toLocaleDateString('es-ES', { weekday: 'short', day: '2-digit', month: 'short' });
            const time = date.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
            
            dateDisplay = `<span><i class="bi bi-calendar3 me-1"></i>${day}</span>`;
            timeDisplay = `<div class="match-time mt-1">${time} hrs</div>`;
        }

        // Logos con Fallback
        const logoA = m.logo_a ? `${m.logo_a.replace('../', '')}` : `https://ui-avatars.com/api/?name=${encodeURIComponent(m.team_a)}&background=f1f5f9&color=64748b`;
        const logoB = m.logo_b ? `${m.logo_b.replace('../', '')}` : `https://ui-avatars.com/api/?name=${encodeURIComponent(m.team_b)}&background=f1f5f9&color=64748b`;

        // Estado del Partido (Badge)
        let statusBadge = '<span class="match-status status-scheduled">Programado</span>';
        let isFinished = false;

        if(m.status === 'FINISHED') { statusBadge = '<span class="match-status status-finished">Finalizado</span>'; isFinished = true; }
        if(m.status === 'PLAYING') { statusBadge = '<span class="match-status status-playing">En Juego</span>'; isFinished = true; }
        if(m.status === 'CANCELLED') { statusBadge = '<span class="match-status bg-danger text-white border-danger">Cancelado</span>'; }

        // Lógica de Marcador y Ganador
        let centerContent = `<div class="vs-text">VS</div>${timeDisplay}`;
        let winnerAClass = '';
        let winnerBClass = '';

        if(isFinished || m.status === 'PLAYING') {
            const scA = m.score_a !== null ? parseInt(m.score_a) : 0;
            const scB = m.score_b !== null ? parseInt(m.score_b) : 0;
            
            if (isFinished) {
                if (scA > scB) winnerAClass = 'winner';
                if (scB > scA) winnerBClass = 'winner';
            }

            centerContent = `
                <div class="d-flex align-items-center justify-content-center gap-2 mb-1">
                    <span class="score-display ${winnerAClass}">${scA}</span>
                    <span class="text-muted fs-6">-</span>
                    <span class="score-display ${winnerBClass}">${scB}</span>
                </div>
                ${m.status === 'PLAYING' ? '<div class="live-badge mt-1"><span class="spinner-grow spinner-grow-sm me-1" style="width: 0.3rem; height: 0.3rem;"></span>En Vivo</div>' : timeDisplay}
            `;
        }

        // Botón PDF (Acta)
        let pdfButton = '';
        if(m.pdf_url && isFinished) {
            pdfButton = `<a href="${m.pdf_url.replace('../', '')}" target="_blank" class="btn-pdf" data-bs-toggle="tooltip" title="Ver Acta Oficial"><i class="bi bi-file-earmark-pdf-fill"></i></a>`;
        }

        // Render de la Tarjeta Completa
        return `
        <div class="col-md-6 col-xl-4 mb-4">
            <div class="match-card" data-status="${m.status}">
                ${statusBadge}
                
                <div class="d-flex align-items-center justify-content-between p-3 pb-2 mt-2">
                    <div class="team-container">
                        <img src="${logoA}" class="team-logo shadow-sm" onerror="this.src='https://placehold.co/60x60?text=A'">
                        <div class="team-name-card ${winnerAClass}" title="${m.team_a}">${m.team_a}</div>
                    </div>

                    <div class="match-info">
                        ${centerContent}
                    </div>

                    <div class="team-container">
                        <img src="${logoB}" class="team-logo shadow-sm" onerror="this.src='https://placehold.co/60x60?text=B'">
                        <div class="team-name-card ${winnerBClass}" title="${m.team_b}">${m.team_b}</div>
                    </div>
                </div>

                <div class="match-footer">
                    <div class="d-flex align-items-center gap-3">
                        <div title="Fecha">${dateDisplay}</div>
                        <div class="text-truncate" style="max-width: 140px;" title="Cancha/Sede">
                            <i class="bi bi-geo-alt-fill text-danger me-1"></i>${m.venue_name || 'Sede por definir'}
                        </div>
                    </div>
                    <div>
                        ${pdfButton}
                    </div>
                </div>
            </div>
        </div>`;
    }).join('');

    contentContainer.innerHTML = `
        <div class="fade show active">
            <div class="row g-2">${matchesHtml}</div>
        </div>`;
}

// Opcional: Arreglo Rápido para el Chart.js
// Evita el error al cargar un torneo sin partidos
function renderPeriodsChart(data) {
    const ctx = document.getElementById('periodsChart').getContext('2d');
    if (charts.periods) charts.periods.destroy();
    
    // Si no hay datos, crear un array en ceros para evitar que la gráfica falle
    const chartData = (data && data.length > 0) ? data : [{period: 1, total: 0}, {period: 2, total: 0}, {period: 3, total: 0}, {period: 4, total: 0}];

    charts.periods = new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.map(p => 'Q' + p.period),
            datasets: [{
                label: 'Intensidad de Juego',
                data: chartData.map(p => p.total),
                borderColor: '#10B981',
                borderWidth: 3,
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#10B981',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: { 
            responsive: true, maintainAspectRatio: false, 
            plugins: { legend: { display: false }, tooltip: { intersect: false, mode: 'index' } },
            scales: { y: { beginAtZero: true, grid: { borderDash: [4,4] } } }
        }
    });
}

function renderStandings(teams, tournamentId) {
    const tbody = document.getElementById('standingsBody');
    const tId = tournamentId || document.getElementById('tournamentSelect').value;

    tbody.innerHTML = teams.map((t, i) => {
        const isFirst = i === 0 ? 'color: var(--primary); font-size: 1.1rem;' : '';
        const safeTeamName = t.team.replace(/'/g, "\\'"); 
        const diff = (parseInt(t.pts_favor || 0) - parseInt(t.pts_contra || 0));

        return `
        <tr class="clickable-row" onclick="showTeamDetails(${tId}, ${t.id}, '${safeTeamName}')">
            <td class="fw-bold" style="${isFirst}">${i + 1}</td>
            <td>
                <div class="d-flex align-items-center">
                    ${getTeamLogo(t.team, t.logo_url)}
                    <span class="fw-bold ms-2">${t.team}</span>
                </div>
            </td>
            <td class="text-center text-muted fw-bold">${t.j}</td>
            <td class="text-center fw-bold text-success">${t.jg}</td>
            <td class="text-center fw-bold text-warning">${t.jd || 0}</td>
            <td class="text-center fw-bold text-danger">${t.jp}</td>
            <td class="text-center fw-medium text-muted small">
                ${t.pts_favor || 0} : ${t.pts_contra || 0}
            </td>
            <td class="text-center fw-bold ${diff > 0 ? 'text-success' : 'text-danger'}">
                ${diff > 0 ? '+' : ''}${diff}
            </td>
            <td class="text-center fw-bold fs-5" style="color: var(--primary); background: rgba(255, 87, 34, 0.05);">${t.pts}</td>
        </tr>
    `}).join('');
}


async function showTeamDetails(tournamentId, teamId, teamName) {
    document.getElementById('teamDetailsTitle').innerText = teamName;
    const tbody = document.getElementById('teamPlayersBody');
    
    // Mostrando 5 columnas en el loading
    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-5"><div class="spinner-border" style="color: var(--primary);"></div></td></tr>';
    
    // Instanciar y mostrar el modal de Bootstrap
    const modalEl = document.getElementById('teamDetailsModal');
    let modal = bootstrap.Modal.getInstance(modalEl);
    if (!modal) modal = new bootstrap.Modal(modalEl);
    modal.show();

    try {
        const res = await fetch(`${API_URL}?action=get_team_player_stats&tournament_id=${tournamentId}&team_id=${teamId}`);
        const json = await res.json();
        
        if(json.status === 'success' && json.data.length > 0) {
            tbody.innerHTML = json.data.map(p => {
                const att = parseFloat(p.attendance_percentage);
                const min = parseInt(p.min_attendance_percent);
                const isBelow = att < min;
                
                const attColor = isBelow ? 'color: var(--primary-dark);' : 'color: var(--success);';
                const attIcon = isBelow ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill';
                const fallbackSrc = `https://ui-avatars.com/api/?name=${encodeURIComponent(p.name)}&background=f1f5f9&color=64748b`;
                const photoSrc = p.photo_url ? p.photo_url.replace('../', '') : fallbackSrc;

                // Estructura de 5 columnas: # | Jugador | PTS | 3PT | Asistencia
                return `
                <tr>
                    <td class="fw-bold text-muted text-center">${p.default_number}</td>
                    <td class="fw-bold">
                        <div class="d-flex align-items-center">
                            <img src="${photoSrc}" onerror="this.src='${fallbackSrc}'" style="width: 32px; height: 32px; border-radius: 6px; object-fit: cover; margin-right: 10px; border: 1px solid var(--border-color);">
                            ${p.name}
                        </div>
                    </td>
                    <td class="text-center fw-bold fs-6" style="color: var(--text-main);">${p.total_points}</td>
                    <td class="text-center fw-medium text-muted">${p.triples}</td>
                    <td class="text-end fw-bold" style="${attColor}">
                        <div class="d-flex flex-column align-items-end">
                            <span><i class="bi ${attIcon} me-1"></i> ${att.toFixed(0)}%</span>
                            <span class="small fw-normal text-muted" style="font-size: 0.7rem;">${p.games_played} de ${p.total_games} juegos</span>
                        </div>
                    </td>
                </tr>
                `;
            }).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4"><i class="bi bi-person-x fs-3 d-block mb-2"></i>Sin jugadores registrados o sin actividad</td></tr>';
        }
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger py-4">Error de conexión</td></tr>';
    }
}

function renderPlayerList(containerId, players, valueKey, label) {
    const container = document.getElementById(containerId);
    
    if (!players || players.length === 0) {
        container.innerHTML = '<div class="text-muted small text-center mt-4">No hay datos suficientes aún.</div>';
        return;
    }

    const maxValue = Math.max(...players.map(p => parseInt(p[valueKey])));

    container.innerHTML = players.map((p, index) => {
        const percentage = (parseInt(p[valueKey]) / maxValue) * 100;
        const photoSrc = getPlayerPhoto(p.name, p.photo_url);
        
        const badgeBg = index === 0 ? 'bg-primary' : (index === 1 ? 'bg-secondary' : 'bg-dark');
        const nameColor = index === 0 ? 'color: var(--primary);' : 'color: var(--text-main);';
        const iconTop = index === 0 ? `<i class="bi bi-star-fill text-warning me-1"></i>` : '';
        
        const fallbackSrc = `https://ui-avatars.com/api/?name=${encodeURIComponent(p.name)}&background=f1f5f9&color=64748b&size=256&bold=true`;

        return `
        <div class="leaderboard-item d-flex align-items-center mb-2">
            
            <div class="fw-bold me-3" style="color: var(--text-muted); width: 20px; text-align: center; font-size: 0.95rem;">
                ${index + 1}
            </div>
            
            <div class="position-relative me-3">
                <img src="${photoSrc}" class="player-avatar" alt="${p.name}" onerror="this.src='${fallbackSrc}'">
                ${index === 0 ? `<span class="player-rank-badge shadow-sm ${badgeBg}"><i class="bi bi-star-fill" style="font-size: 0.5rem;"></i></span>` : ''}
            </div>
            
            <div class="flex-grow-1">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <div>
                        <div class="fw-bold leaderboard-name" style="${nameColor}">
                            ${iconTop}${p.name.split(' ').slice(0,2).join(' ')} 
                        </div>
                        <div class="text-muted fw-medium mt-1" style="font-size: 0.7rem;">
                            <i class="bi bi-shield-fill me-1 opacity-75"></i>${p.team}
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold leaderboard-stat" style="color: var(--text-main); line-height: 1;">${p[valueKey]}</div>
                        <small class="text-muted fw-bold" style="font-size: 0.6rem;">${label}</small>
                    </div>
                </div>
                <div class="progress bg-light mt-1" style="height: 4px; border-radius: 10px;">
                    <div class="progress-bar ${index === 0 ? 'bg-primary' : 'bg-secondary'}" style="width: ${percentage}%; border-radius: 10px;"></div>
                </div>
            </div>
        </div>
        `;
    }).join('');
}

function renderDefenseTable(teams) {
    const tbody = document.getElementById('defenseBody');
    tbody.innerHTML = teams.map(t => `
        <tr>
            <td>
                <div class="d-flex align-items-center">
                    ${getTeamLogo(t.team, t.logo_url)}
                    <span class="fw-bold ms-2 small">${t.team}</span>
                </div>
            </td>
            <td class="text-end fw-bold" style="color: var(--primary);">${parseFloat(t.avg_allowed).toFixed(1)} <span class="fw-normal small">pts</span></td>
        </tr>
    `).join('');
}

function renderPeriodsChart(data) {
    const ctx = document.getElementById('periodsChart').getContext('2d');
    if (charts.periods) charts.periods.destroy();
    charts.periods = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.map(p => 'Q' + p.period),
            datasets: [{
                label: 'Intensidad de Juego',
                data: data.map(p => p.total),
                borderColor: '#10B981',
                borderWidth: 3,
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#10B981',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: { 
            responsive: true, maintainAspectRatio: false, 
            plugins: { legend: { display: false }, tooltip: { intersect: false, mode: 'index' } },
            scales: { y: { beginAtZero: true, grid: { borderDash: [4,4] } } }
        }
    });
}