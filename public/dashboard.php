<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Control Horario</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php
    require_once __DIR__ . '/../includes/auth.php';
    require_once __DIR__ . '/../src/TimeRecord.php';
    require_once __DIR__ . '/../src/Project.php';
    require_once __DIR__ . '/../src/User.php';
    requireLogin();
    
    $user = getCurrentUser();
    $userId = $user['id'];
    
    $timeRecord = new TimeRecord();
    $project = new Project();
    $userModel = new User();
    
    $isCheckedIn = $timeRecord->isCurrentlyCheckedIn($userId);
    $lastCheckIn = $timeRecord->getLastCheckIn($userId);
    $todayHours = $timeRecord->calculateTodayHours($userId);
    $todayRecords = $timeRecord->getTodayRecords($userId);
    $recentRecords = $timeRecord->getUserRecords($userId, 10);
    $projects = $project->getAllActive();
    $userProjects = $project->getUserProjectHours($userId);
    
    $error = $_GET['error'] ?? null;
    $success = $_GET['success'] ?? null;
    ?>
    
    <div class="container">
        <header class="header">
            <h1>Panel de Control</h1>
            <div class="user-info">
                <span><?php echo escape($user['nombre'] . ' ' . $user['apellidos']); ?> (<?php echo escape(ucfirst($user['rol'])); ?>)</span>
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
                    <h3>Fichaje</h3>
                    <div class="status">
                        <?php if ($isCheckedIn): ?>
                            <p class="checked-in">✓ Actualmente trabajando</p>
                            <p class="time-info">Desde: <?php echo date('H:i', strtotime($lastCheckIn['fecha_hora'])); ?></p>
                        <?php else: ?>
                            <p class="checked-out">○ No estás trabajando</p>
                        <?php endif; ?>
                    </div>
                    
                    <form action="actions/checkin.php" method="POST" class="fichaje-form">
                        <h4>Registrar Entrada</h4>
                        <div class="form-group">
                            <label for="proyecto_checkin">Proyecto (opcional):</label>
                            <select name="proyecto_id" id="proyecto_checkin">
                                <option value="">-- Sin proyecto --</option>
                                <?php foreach ($projects as $p): ?>
                                    <option value="<?php echo $p['id']; ?>">
                                        <?php echo escape($p['nombre']); ?>
                                        <?php if ($p['cliente']): ?> - <?php echo escape($p['cliente']); ?><?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="notas_checkin">Notas (opcional):</label>
                            <textarea name="notas" id="notas_checkin" rows="2" placeholder="Detalles adicionales..."></textarea>
                        </div>
                        <button type="submit" name="checkin" class="btn btn-primary" <?php echo $isCheckedIn ? 'disabled' : ''; ?>>
                            Registrar Entrada
                        </button>
                    </form>
                    
                    <form action="actions/checkout.php" method="POST" class="fichaje-form" style="margin-top: 20px;">
                        <h4>Registrar Salida</h4>
                        <div class="form-group">
                            <label for="notas_checkout">Notas (opcional):</label>
                            <textarea name="notas" id="notas_checkout" rows="2" placeholder="Tarea completada..."></textarea>
                        </div>
                        <button type="submit" name="checkout" class="btn btn-warning" <?php echo !$isCheckedIn ? 'disabled' : ''; ?>>
                            Registrar Salida
                        </button>
                    </form>
                </div>
                
                <div class="card">
                    <h3>Resumen de Hoy</h3>
                    <div class="summary">
                        <div class="summary-item">
                            <span class="summary-label">Horas trabajadas:</span>
                            <span class="summary-value"><?php echo number_format($todayHours, 2); ?>h</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Registros hoy:</span>
                            <span class="summary-value"><?php echo count($todayRecords); ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Proyectos hoy:</span>
                            <span class="summary-value"><?php echo count(array_filter($userProjects, fn($p) => $p['total_registros'] > 0)); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <h3>Registros Recientes</h3>
                    <?php if (empty($recentRecords)): ?>
                        <p>No hay registros recientes.</p>
                    <?php else: ?>
                        <ul class="records-list">
                            <?php foreach ($recentRecords as $record): ?>
                                <li class="record-item">
                                    <span class="record-type <?php echo $record['tipo_registro']; ?>">
                                        <?php echo $record['tipo_registro'] === 'check-in' ? '→ Entrada' : '← Salida'; ?>
                                    </span>
                                    <span class="record-time"><?php echo date('d/m H:i', strtotime($record['fecha_hora'])); ?></span>
                                    <?php if ($record['proyecto_nombre']): ?>
                                        <span class="record-project"><?php echo escape($record['proyecto_nombre']); ?></span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                
                <div class="card">
                    <h3>Proyectos Activos</h3>
                    <?php if (empty($projects)): ?>
                        <p>No hay proyectos activos.</p>
                    <?php else: ?>
                        <ul class="simple-list">
                            <?php foreach ($projects as $p): ?>
                                <li class="simple-item">
                                    <span class="item-name"><?php echo escape($p['nombre']); ?></span>
                                    <?php if ($p['cliente']): ?>
                                        <span class="item-meta"><?php echo escape($p['cliente']); ?></span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                
                <?php if ($user['rol'] === 'admin'): ?>
                <div class="card full-width">
                    <h3>Accesos Rápidos (Admin)</h3>
                    <div class="action-buttons">
                        <a href="admin.php" class="btn btn-primary">Panel de Administrador</a>
                        <a href="admin-projects.php" class="btn btn-secondary">Gestionar Proyectos</a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script src="assets/js/main.js"></script>
</body>
</html>