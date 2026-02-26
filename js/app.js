const API_URL = 'https://basket.techsolutions.management/api.php';
let charts = {};

// Autocompletar el año del Footer
document.getElementById('currentYear').textContent = new Date().getFullYear();

document.addEventListener("DOMContentLoaded", () => {
    initThemeToggle(); 
    loadTournaments();
    loadDynamicGallery(); 
    document.getElementById('tournamentSelect').addEventListener('change', (e) => loadDashboard(e.target.value));
});

// --- LÓGICA DEL MODO OSCURO ---
function initThemeToggle() {
    const btn = document.getElementById('themeToggleBtn');
    const icon = document.getElementById('themeIcon');
    const htmlElement = document.documentElement;

    if (localStorage.getItem('theme') === 'dark') {
        htmlElement.setAttribute('data-theme', 'dark');
        icon.classList.replace('bi-moon-fill', 'bi-sun-fill');
    }

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
            select.innerHTML = json.data.map(t => `<option value="${t.id}">${t.name}</option>`).join('');
            loadDashboard(json.data[0].id);
        } else {
            select.innerHTML = '<option disabled>No hay torneos activos</option>';
        }
    } catch (e) { console.error(e); }
}

async function loadDynamicGallery() {
    try {
        const res = await fetch(`${API_URL}?action=get_slider_images`);
        const json = await res.json();
        
        if (json.status === 'success' && json.data.length > 0) {
            document.getElementById('basketballGallery').classList.remove('d-none');
            const indicators = document.getElementById('galleryIndicators');
            const inner = document.getElementById('galleryInner');
            
            indicators.innerHTML = '';
            const overlayHtml = '<div class="carousel-overlay"></div>';
            let slidesHtml = '';

            json.data.forEach((img, index) => {
                const isActive = index === 0 ? 'active' : '';
                indicators.innerHTML += `<button type="button" data-bs-target="#basketballGallery" data-bs-slide-to="${index}" class="${isActive}"></button>`;
                slidesHtml += `
                    <div class="carousel-item ${isActive} h-100">
                        <div class="slide-content-wrapper">
                            <img src="assets/imagenes/slider/${img.filename}" class="gallery-bg-blur" alt="bg blur">
                            <img src="assets/imagenes/slider/${img.filename}" class="gallery-img-main" alt="Jugada ${index+1}">
                        </div>
                    </div>
                `;
            });
            
            inner.innerHTML = overlayHtml + slidesHtml;
        }
    } catch (e) { console.error("Error cargando galería:", e); }
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
            renderStandings(d.standings);

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

function renderFixture(rounds) {
    const tabsContainer = document.getElementById('fixtureTabs');
    const contentContainer = document.getElementById('fixtureContent');
    const liveIndicator = document.getElementById('liveIndicator');
    
    tabsContainer.innerHTML = '';
    contentContainer.innerHTML = '';
    let hasLiveGames = false;

    if (!rounds || Object.keys(rounds).length === 0) {
        contentContainer.innerHTML = '<div class="text-center py-5"><i class="bi bi-calendar-x fs-1 d-block mb-2"></i>No hay partidos programados.</div>';
        return;
    }

    let activeRoundIndex = 0;
    let index = 0;
    let foundActive = false;

    for (const [roundName, matches] of Object.entries(rounds)) {
        if (matches.some(m => m.status === 'PLAYING')) hasLiveGames = true;
        if (!foundActive && matches.some(m => m.status !== 'FINISHED')) {
            activeRoundIndex = index;
            foundActive = true;
        }
        index++;
    }
    
    if (hasLiveGames) liveIndicator.classList.remove('d-none');
    else liveIndicator.classList.add('d-none');

    let tabsHtml = '';
    let panesHtml = '';

    index = 0;
    for (const [roundName, matches] of Object.entries(rounds)) {
        const safeId = roundName.replace(/[^a-zA-Z0-9]/g, '_');
        const isActive = index === activeRoundIndex;
        const activeClass = isActive ? 'active' : '';
        const showClass = isActive ? 'show active' : '';

        tabsHtml += `
            <li class="nav-item" role="presentation">
                <button class="nav-link ${activeClass}" id="tab-btn-${safeId}" data-bs-toggle="pill" data-bs-target="#tab_${safeId}" type="button" role="tab" aria-controls="tab_${safeId}" aria-selected="${isActive}">
                    ${roundName}
                </button>
            </li>`;

        let matchesHtml = matches.map(m => {
            const dateStr = m.scheduled_datetime ? new Date(m.scheduled_datetime).toLocaleDateString('es-ES', {weekday:'short', day:'numeric', month:'short'}) : 'Sin definir';
            const timeStr = m.scheduled_datetime ? new Date(m.scheduled_datetime).toLocaleTimeString('es-ES', {hour:'2-digit', minute:'2-digit'}) : '';
            
            let statusBadge = '';
            let pdfButton = '';
            
            if(m.status === 'PLAYING') {
                statusBadge = '<div class="live-badge">EN VIVO</div>';
            } else if(m.status === 'FINISHED') {
                statusBadge = '<span class="status-badge-neutral">Final</span>';
                if (m.pdf_url) {
                    pdfButton = `<a href="${m.pdf_url.replace('../', '')}" target="_blank" class="btn btn-sm btn-outline-danger mt-3 w-100 fw-bold"><i class="bi bi-file-earmark-pdf-fill me-1"></i> Ver Acta Oficial</a>`;
                }
            } else {
                statusBadge = `<span class="status-badge-neutral"><i class="bi bi-clock me-1"></i>${timeStr || 'Pendiente'}</span>`;
            }

            let scoreA = '';
            let scoreB = '';
            let winnerAClass = '';
            let winnerBClass = '';

            if (m.status === 'FINISHED' || m.status === 'PLAYING') {
                scoreA = m.score_a !== null ? m.score_a : '0';
                scoreB = m.score_b !== null ? m.score_b : '0';
                
                if (m.status === 'FINISHED') {
                    if (parseInt(scoreA) > parseInt(scoreB)) winnerAClass = 'winner';
                    if (parseInt(scoreB) > parseInt(scoreA)) winnerBClass = 'winner';
                }
            }

            return `
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="match-card" data-status="${m.status}">
                    <div class="status-indicator"></div>
                    <div class="match-card-body">
                        <div class="team-row">
                            <div class="team-info">
                                <img src="${m.logo_a ? m.logo_a.replace('../', '') : `https://ui-avatars.com/api/?name=${m.team_a}&background=random&color=fff`}" class="team-logo-sm">
                                <span class="team-name-card ${winnerAClass}">${m.team_a}</span>
                            </div>
                            <span class="team-score ${winnerAClass}">${scoreA}</span> 
                        </div>
                        
                        <div class="team-row mb-0">
                            <div class="team-info">
                                <img src="${m.logo_b ? m.logo_b.replace('../', '') : `https://ui-avatars.com/api/?name=${m.team_b}&background=random&color=fff`}" class="team-logo-sm">
                                <span class="team-name-card ${winnerBClass}">${m.team_b}</span>
                            </div>
                            <span class="team-score ${winnerBClass}">${scoreB}</span> 
                        </div>

                        <div class="match-meta">
                            <div><i class="bi bi-calendar-event me-1"></i>${dateStr}</div>
                            <div>${statusBadge}</div>
                            <div><i class="bi bi-geo-alt me-1"></i>${m.venue_name || 'Sede Local'}</div>
                        </div>
                        
                        ${pdfButton}
                    </div>
                </div>
            </div>`;
        }).join('');

        panesHtml += `
            <div class="tab-pane fade ${showClass}" id="tab_${safeId}" role="tabpanel" aria-labelledby="tab-btn-${safeId}">
                <div class="row g-3">${matchesHtml}</div>
            </div>`;
        
        index++;
    }

    tabsContainer.innerHTML = tabsHtml;
    contentContainer.innerHTML = panesHtml;
}

function renderStandings(teams) {
    const tbody = document.getElementById('standingsBody');
    tbody.innerHTML = teams.map((t, i) => {
        const isFirst = i === 0 ? 'color: var(--primary); font-size: 1.1rem;' : '';
        const j = parseInt(t.w) + parseInt(t.l) + parseInt(t.d || 0);
        
        return `
        <tr>
            <td class="fw-bold" style="${isFirst}">${i + 1}</td>
            <td>
                <div class="d-flex align-items-center">
                    ${getTeamLogo(t.team, t.logo_url)}
                    <span class="fw-bold ms-2">${t.team}</span>
                </div>
            </td>
            <td class="text-center text-muted fw-bold">${j}</td>
            <td class="text-center fw-bold text-success">${t.w}</td>
            <td class="text-center fw-bold text-muted">${t.d || 0}</td>
            <td class="text-center fw-bold text-danger">${t.l}</td>
            <td class="text-center fw-medium text-muted" style="font-size: 0.85rem;">
                ${t.pts_favor} : ${t.pts_contra}
            </td>
            <td class="text-center fw-bold ${t.point_diff > 0 ? 'text-success' : (t.point_diff < 0 ? 'text-danger' : 'text-muted')}">
                ${t.point_diff > 0 ? '+' : ''}${t.point_diff}
            </td>
            <td class="text-center fw-bold fs-5" style="color: var(--primary); background: rgba(255, 87, 34, 0.05);">${t.pts}</td>
        </tr>
    `}).join('');
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