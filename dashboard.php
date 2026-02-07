<?php
// --- SIMULACIÓN DE DATOS (BACKEND) ---

// KPIs Generales
$stats = [
    'total_matches' => 45,
    'total_teams' => 12,
    'active_referees' => 4,
    'avg_points' => 68.5
];

// Datos para la Tabla de Posiciones
$standings = [
    ['pos' => 1, 'team' => 'Toros de Ixmiquilpan', 'w' => 10, 'l' => 1, 'pts' => 21, 'diff' => '+85'],
    ['pos' => 2, 'team' => 'Águilas del Valle', 'w' => 9, 'l' => 2, 'pts' => 20, 'diff' => '+60'],
    ['pos' => 3, 'team' => 'Guerreros Mixquiahuala', 'w' => 7, 'l' => 4, 'pts' => 18, 'diff' => '+32'],
    ['pos' => 4, 'team' => 'Cardonal Heat', 'w' => 6, 'l' => 5, 'pts' => 17, 'diff' => '-10'],
    ['pos' => 5, 'team' => 'Tasquillo Force', 'w' => 4, 'l' => 7, 'pts' => 15, 'diff' => '-25'],
];

// Datos para Próximos Partidos
$upcoming = [
    ['date' => '05 Feb', 'time' => '18:00', 'home' => 'Toros', 'away' => 'Cardonal', 'venue' => 'Auditorio Municipal'],
    ['date' => '05 Feb', 'time' => '20:00', 'home' => 'Águilas', 'away' => 'Guerreros', 'venue' => 'Cancha Techada La Reforma'],
    ['date' => '06 Feb', 'time' => '10:00', 'home' => 'Tasquillo', 'away' => 'San Juan', 'venue' => 'Unidad Deportiva'],
];

// DATOS PARA GRÁFICAS (JSON para JS)
// 1. Puntos por Equipo
$chartTeams = json_encode(['Toros', 'Águilas', 'Guerreros', 'Cardonal', 'Tasquillo']);
$chartPoints = json_encode([850, 810, 740, 690, 620]);

// 2. Top Anotadores (MVP Race)
$chartPlayers = json_encode(['J. Pérez (Toros)', 'M. López (Águilas)', 'R. Gonzalez (Cardonal)', 'A. Smith (Guerreros)', 'C. Diaz (Toros)']);
$chartPlayerPoints = json_encode([245, 210, 198, 185, 170]);

// 3. Top Triples (3 Puntos)
$chart3PtPlayers = json_encode(['C. Diaz (Toros)', 'J. Pérez (Toros)', 'R. Gonzalez (Cardonal)', 'M. López (Águilas)', 'L. Vega (Tasquillo)']);
$chart3PtCounts = json_encode([45, 38, 32, 28, 25]);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Basket Arbitraje | Panel Administrativo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --primary-orange: #FF5722; /* Color Seed de tu App Flutter */
            --secondary-dark: #2c2e3e;
            --dark-sidebar: #1a1c23;
        }
        body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        
        .sidebar {
            height: 100vh;
            background-color: var(--dark-sidebar);
            color: white;
            position: fixed;
            width: 250px;
            padding-top: 20px;
            z-index: 1000;
        }
        .sidebar a {
            color: #adb5bd;
            text-decoration: none;
            padding: 12px 25px;
            display: block;
            transition: 0.3s;
            border-left: 4px solid transparent;
        }
        .sidebar a:hover, .sidebar a.active {
            color: white;
            background-color: rgba(255, 87, 34, 0.1);
            border-left: 4px solid var(--primary-orange);
        }
        .main-content { margin-left: 250px; padding: 20px; }
        
        /* Tarjetas */
        .stat-card {
            border: none;
            border-radius: 12px;
            background: white;
            box-shadow: 0 2px 15px rgba(0,0,0,0.03);
            transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-5px); }
        .icon-box {
            width: 48px; height: 48px;
            background: rgba(255, 87, 34, 0.1);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; color: var(--primary-orange);
        }
        
        /* Tablas y Listas */
        .table-custom thead { background-color: var(--secondary-dark); color: white; }
        .match-card {
            border-left: 4px solid var(--primary-orange);
            background: white;
            margin-bottom: 10px;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
        }
        .match-time { font-weight: bold; color: var(--primary-orange); font-size: 0.9rem; }
        .match-teams { font-weight: 600; font-size: 1.05rem; }
        .match-venue { font-size: 0.85rem; color: #6c757d; }

        .brand-logo { font-size: 1.4rem; font-weight: bold; color: white; text-align: center; margin-bottom: 35px; }
    </style>
</head>
<body>

<div class="sidebar d-none d-md-block">
    <div class="brand-logo"><i class="bi bi-basket2-fill text-warning"></i> Basket Pro</div>
    <a href="#" class="active"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
    <a href="#"><i class="bi bi-trophy me-2"></i> Torneos</a>
    <a href="#"><i class="bi bi-people me-2"></i> Equipos</a>
    <a href="#"><i class="bi bi-person-badge me-2"></i> Jugadores</a>
    <a href="#"><i class="bi bi-file-earmark-pdf me-2"></i> Reportes PDF</a>
    <a href="#" class="mt-5"><i class="bi bi-gear me-2"></i> Configuración</a>
</div>

<div class="main-content">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0 fw-bold">Panel General</h4>
            <span class="text-muted small">Liga Municipal Ixmiquilpan • Temporada 2026</span>
        </div>
        <div class="d-flex align-items-center gap-3">
             <button class="btn btn-primary btn-sm rounded-pill px-3" style="background: var(--primary-orange); border:none;">
                <i class="bi bi-cloud-arrow-down-fill me-1"></i> Sincronizar App
            </button>
            <img src="https://ui-avatars.com/api/?name=Admin&background=2c2e3e&color=fff" class="rounded-circle" width="40">
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card p-3 d-flex align-items-center justify-content-between">
                <div><h6 class="text-muted mb-1">Partidos</h6><h3 class="mb-0 fw-bold"><?php echo $stats['total_matches']; ?></h3></div>
                <div class="icon-box"><i class="bi bi-play-circle-fill"></i></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card p-3 d-flex align-items-center justify-content-between">
                <div><h6 class="text-muted mb-1">Equipos</h6><h3 class="mb-0 fw-bold"><?php echo $stats['total_teams']; ?></h3></div>
                <div class="icon-box"><i class="bi bi-shield-shaded"></i></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card p-3 d-flex align-items-center justify-content-between">
                <div><h6 class="text-muted mb-1">Árbitros</h6><h3 class="mb-0 fw-bold"><?php echo $stats['active_referees']; ?></h3></div>
                <div class="icon-box"><i class="bi bi-whistle"></i></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card p-3 d-flex align-items-center justify-content-between">
                <div><h6 class="text-muted mb-1">Pts Promedio</h6><h3 class="mb-0 fw-bold"><?php echo $stats['avg_points']; ?></h3></div>
                <div class="icon-box"><i class="bi bi-activity"></i></div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-8">
            <div class="card stat-card h-100 p-3">
                <div class="d-flex justify-content-between mb-3">
                    <h5 class="fw-bold">Top Anotadores (MVP)</h5>
                    <select class="form-select form-select-sm w-auto"><option>General</option><option>Por Equipo</option></select>
                </div>
                <div style="height: 250px;">
                    <canvas id="scorersChart"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
             <div class="card stat-card h-100 p-3">
                <h5 class="fw-bold mb-3">Francotiradores (3 Pts)</h5>
                <div style="height: 220px; display:flex; justify-content:center;">
                    <canvas id="triplesChart"></canvas>
                </div>
                <div class="mt-3 text-center small text-muted">
                    <i class="bi bi-info-circle"></i> Jugadores con más tiros de 3 encestados.
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        
        <div class="col-lg-7">
            <div class="card stat-card h-100">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between">
                    <h5 class="mb-0 fw-bold">Tabla General</h5>
                    <a href="#" class="text-decoration-none small text-muted">Ver Completa ></a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-custom mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">#</th>
                                <th>Equipo</th>
                                <th>G-P</th>
                                <th>Dif</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($standings as $row): ?>
                            <tr>
                                <td class="ps-4 fw-bold"><?php echo $row['pos']; ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-light me-2" style="width:25px;height:25px;"></div>
                                        <?php echo $row['team']; ?>
                                    </div>
                                </td>
                                <td><span class="badge bg-success bg-opacity-75"><?php echo $row['w']; ?></span> - <span class="badge bg-secondary"><?php echo $row['l']; ?></span></td>
                                <td class="fw-bold <?php echo intval($row['diff']) > 0 ? 'text-success' : 'text-danger'; ?>"><?php echo $row['diff']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card stat-card h-100 p-3 bg-light border-0">
                <div class="d-flex justify-content-between mb-3">
                    <h5 class="fw-bold">Próximos Partidos</h5>
                    <button class="btn btn-sm btn-outline-dark"><i class="bi bi-plus-lg"></i> Programar</button>
                </div>
                
                <?php foreach($upcoming as $match): ?>
                <div class="match-card">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="badge bg-secondary text-white small"><?php echo $match['date']; ?></span>
                        <span class="match-time"><i class="bi bi-clock"></i> <?php echo $match['time']; ?></span>
                    </div>
                    <div class="match-teams mb-1">
                        <?php echo $match['home']; ?> <span class="text-muted mx-1">vs</span> <?php echo $match['away']; ?>
                    </div>
                    <div class="match-venue">
                        <i class="bi bi-geo-alt-fill text-muted"></i> <?php echo $match['venue']; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                
            </div>
        </div>

    </div>
</div>

<script>
    // 1. Gráfica de Top Anotadores (Barras Verticales)
    const ctxScorers = document.getElementById('scorersChart').getContext('2d');
    new Chart(ctxScorers, {
        type: 'bar',
        data: {
            labels: <?php echo $chartPlayers; ?>,
            datasets: [{
                label: 'Puntos Totales',
                data: <?php echo $chartPlayerPoints; ?>,
                backgroundColor: '#FF5722',
                borderRadius: 4,
                barPercentage: 0.6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, grid: { borderDash: [2, 4] } }, x: { grid: { display: false } } }
        }
    });

    // 2. Gráfica de Triples (Doughnut)
    const ctxTriples = document.getElementById('triplesChart').getContext('2d');
    new Chart(ctxTriples, {
        type: 'doughnut',
        data: {
            labels: <?php echo $chart3PtPlayers; ?>,
            datasets: [{
                data: <?php echo $chart3PtCounts; ?>,
                backgroundColor: ['#FF5722', '#FF8A65', '#FFCCBC', '#455A64', '#90A4AE'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: { 
                legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } 
            }
        }
    });
</script>

</body>
</html>