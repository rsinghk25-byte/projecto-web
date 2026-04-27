<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Alertas - Control Horario</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php
    require_once __DIR__ . '/../includes/auth.php';
    require_once __DIR__ . '/../src/Alert.php';
    requireAdmin();
    
    $user = getCurrentUser();
    $alertModel = new Alert();
    
    // Manejar acciones
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'mark_read') {
            $alertId = intval($_POST['alert_id'] ?? 0);
            if ($alertId > 0) {
                $alertModel->markAsRead($alertId);
                header("Location: admin-alerts.php?success=Alerta marcada como leída");
                exit;
            }
        } elseif ($action === 'mark_all_read') {
            $alertModel->markAllAsReadForAdmin();
            header("Location: admin-alerts.php?success=Todas las alertas marcadas como leídas");
            exit;
        }
    }
    
    $unreadAlerts = $alertModel->getUnreadForAdmin();
    $allAlerts = $alertModel->getAllForAdmin();
    
    $error = $_GET['error'] ?? null;
    $success = $_GET['success'] ?? null;
    ?>
    
    <div class="container">
        <header class="header">
            <h1>🔔 Gestión de Alertas</h1>
            <div class="user-info">
                <span>👤 <?php echo escape($user['nombre'] . ' ' . $user['apellidos']); ?></span>
                <a href="admin.php" class="btn btn-secondary">← Volver al Admin</a>
                <a href="logout.php" class="btn btn-danger">Cerrar sesión</a>
            </div>
        </header>
        
        <?php if ($error): ?>
            <div class="error">⚠️ <?php echo escape($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success">✓ <?php echo escape($success); ?></div>
        <?php endif; ?>
        
        <main class="main-content">
            <div class="dashboard-grid">
                <div class="card stat-card warning">
                    <div class="stat-content">
                        <h3>Alertas Sin Leer</h3>
                        <div class="stat-value"><?php echo count($unreadAlerts); ?></div>
                    </div>
                    <div class="stat-icon">🔔</div>
                </div>
                
                <div class="card stat-card info">
                    <div class="stat-content">
                        <h3>Total Alertas</h3>
                        <div class="stat-value"><?php echo count($allAlerts); ?></div>
                    </div>
                    <div class="stat-icon">📋</div>
                </div>
                
                <div class="card full-width">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h3>📢 Alertas Pendientes</h3>
                        <?php if (count($unreadAlerts) > 0): ?>
                            <form method="POST">
                                <input type="hidden" name="action" value="mark_all_read">
                                <button type="submit" class="btn btn-sm btn-success">✓ Marcar todas leídas</button>
                            </form>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (empty($unreadAlerts)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">✅</div>
                            <p class="text-muted">No hay alertas sin leer. ¡Todo bajo control!</p>
                        </div>
                    <?php else: ?>
                        <div class="alerts-list">
                            <?php foreach ($unreadAlerts as $alert): ?>
                                <div class="alert-item alert-<?php echo $alert['nivel']; ?>">
                                    <div class="alert-icon">
                                        <?php 
                                        echo match($alert['nivel']) {
                                            'danger' => '🚨',
                                            'warning' => '⚠️',
                                            'info' => 'ℹ️',
                                            default => '🔔'
                                        };
                                        ?>
                                    </div>
                                    <div class="alert-content">
                                        <div class="alert-title"><?php echo escape($alert['titulo']); ?></div>
                                        <div class="alert-message"><?php echo escape($alert['mensaje']); ?></div>
                                        <div class="alert-time"><?php echo date('d/m/Y H:i', strtotime($alert['created_at'] ?? '')); ?></div>
                                    </div>
                                    <form method="POST" class="alert-action">
                                        <input type="hidden" name="action" value="mark_read">
                                        <input type="hidden" name="alert_id" value="<?php echo $alert['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-success">✓</button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="card full-width">
                    <h3>📜 Historial de Alertas</h3>
                    <?php if (empty($allAlerts)): ?>
                        <p class="text-muted">No hay alertas registradas.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Nivel</th>
                                        <th>Título</th>
                                        <th>Mensaje</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($allAlerts as $alert): ?>
                                        <tr class="<?php echo $alert['leido'] ? '' : 'table-row-unread'; ?>">
                                            <td><?php echo date('d/m/Y H:i', strtotime($alert['created_at'] ?? '')); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $alert['nivel']; ?>">
                                                    <?php echo strtoupper($alert['nivel']); ?>
                                                </span>
                                            </td>
                                            <td><strong><?php echo escape($alert['titulo']); ?></strong></td>
                                            <td><?php echo escape($alert['mensaje']); ?></td>
                                            <td>
                                                <?php echo $alert['leido'] 
                                                    ? '<span class="badge badge-success">LEÍDO</span>' 
                                                    : '<span class="badge badge-warning">PENDIENTE</span>'; 
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="card">
                    <h3>🔗 Acciones Rápidas</h3>
                    <div class="action-buttons">
                        <a href="admin.php" class="btn btn-primary">Panel Admin</a>
                        <a href="admin-projects.php" class="btn btn-secondary">Gestionar Proyectos</a>
                        <a href="admin-reports.php" class="btn btn-secondary">Ver Reportes</a>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="assets/js/main.js"></script>
</body>
</html>