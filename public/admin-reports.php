<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - Control Horario</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php
    require_once __DIR__ . '/../includes/auth.php';
    require_once __DIR__ . '/../src/Report.php';
    require_once __DIR__ . '/../src/Project.php';
    requireAdmin();
    
    $user = getCurrentUser();
    $report = new Report();
    $project = new Project();
    
    $projects = $project->getAllActive();
    $projectComparison = $report->getProjectComparison();
    $chartData = $report->getChartData(null, 30);
    
    $selectedProject = $_GET['project'] ?? null;
    if ($selectedProject) {
        $chartData = $report->getChartData(intval($selectedProject), 30);
    }
    
    $error = $_GET['error'] ?? null;
    $success = $_GET['success'] ?? null;
    ?>
    
    <div class="container">
        <header class="header">
            <h1>Reportes de Horas</h1>
            <div class="user-info">
                <span><?php echo escape($user['nombre'] . ' ' . $user['apellidos']); ?></span>
                <a href="admin.php" class="btn btn-secondary">Volver al Admin</a>
                <a href="logout.php" class="btn btn-secondary">Cerrar sesión</a>
            </div>
        </header>
        
        <?php if ($error): ?>
            <div class="error"><?php echo escape($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo escape($success); ?></div>
        <?php endif; ?>
        
        <main class="main-content">
            <div class="dashboard-grid">
                <div class="card">
                    <h3>Filtrar por Proyecto</h3>
                    <form action="admin-reports.php" method="GET">
                        <div class="form-group">
                            <label for="project">Proyecto:</label>
                            <select name="project" id="project">
                                <option value="">Todos los proyectos</option>
                                <?php foreach ($projects as $p): ?>
                                    <option value="<?php echo $p['id']; ?>" <?php echo $selectedProject == $p['id'] ? 'selected' : ''; ?>>
                                        <?php echo escape($p['nombre']); ?>
                                        <?php if ($p['cliente']): ?> - <?php echo escape($p['cliente']); ?><?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                    </form>
                </div>
                
                <div class="card full-width">
                    <h3>Horas por Día (Últimos 30 días)</h3>
                    <div class="chart-container">
                        <canvas id="hoursChart"></canvas>
                    </div>
                </div>
                
                <div class="card full-width">
                    <h3>Comparativa de Proyectos</h3>
                    <?php if (empty($projectComparison)): ?>
                        <p>No hay datos de proyectos disponibles.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Proyecto</th>
                                        <th>Cliente</th>
                                        <th>Equipo</th>
                                        <th>Horas Reales</th>
                                        <th>Horas Estimadas</th>
                                        <th>Progreso</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($projectComparison as $p): ?>
                                        <tr>
                                            <td><strong><?php echo escape($p['nombre']); ?></strong></td>
                                            <td><?php echo escape($p['cliente'] ?? 'N/A'); ?></td>
                                            <td><?php echo $p['team_size']; ?> personas</td>
                                            <td><?php echo number_format($p['hours_worked'], 1); ?>h</td>
                                            <td><?php echo $p['hours_estimated'] > 0 ? $p['hours_estimated'] . 'h' : 'N/A'; ?></td>
                                            <td>
                                                <div class="progress-bar">
                                                    <div class="progress-fill" style="width: <?php echo min($p['progress_percent'], 100); ?>%">
                                                        <?php echo number_format($p['progress_percent'], 1); ?>%
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php
                                                $statusClass = 'badge-secondary';
                                                if ($p['progress_percent'] >= 100) $statusClass = 'badge-success';
                                                elseif ($p['progress_percent'] >= 75) $statusClass = 'badge-warning';
                                                elseif ($p['progress_percent'] >= 50) $statusClass = 'badge-warning';
                                                ?>
                                                <span class="badge <?php echo $statusClass; ?>">
                                                    <?php
                                                    if ($p['progress_percent'] >= 100) echo 'Completado';
                                                    elseif ($p['progress_percent'] >= 75) echo 'Avanzado';
                                                    elseif ($p['progress_percent'] >= 50) echo 'Medio';
                                                    else echo 'Inicial';
                                                    ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="card">
                    <h3>Exportar Datos</h3>
                    <p>Descarga los datos del reporte en formato JSON.</p>
                    <div class="action-buttons">
                        <a href="api/reports/projects.php" class="btn btn-secondary" target="_blank">Exportar JSON</a>
                    </div>
                </div>
                
                <div class="card">
                    <h3>Acciones</h3>
                    <div class="action-buttons">
                        <a href="admin.php" class="btn btn-primary">Panel Admin</a>
                        <a href="dashboard.php" class="btn btn-secondary">Mi Dashboard</a>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="assets/js/main.js"></script>
    <script>
        const ctx = document.getElementById('hoursChart').getContext('2d');
        const chartData = {
            labels: <?php echo json_encode($chartData['labels']); ?>,
            datasets: [{
                label: 'Horas Diarias',
                data: <?php echo json_encode($chartData['daily']); ?>,
                borderColor: '#3498db',
                backgroundColor: 'rgba(52, 152, 219, 0.1)',
                tension: 0.1,
                fill: true
            }, {
                label: 'Horas Acumuladas',
                data: <?php echo json_encode($chartData['cumulative']); ?>,
                borderColor: '#2ecc71',
                backgroundColor: 'rgba(46, 204, 113, 0.1)',
                tension: 0.1,
                fill: true
            }]
        };
        
        new Chart(ctx, {
            type: 'line',
            data: chartData,
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Evolución de Horas Trabajadas'
                    },
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Horas'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Fecha'
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>