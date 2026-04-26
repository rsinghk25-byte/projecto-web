<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Control Horario</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php
    require_once __DIR__ . '/../includes/auth.php';
    require_once __DIR__ . '/../src/User.php';
    require_once __DIR__ . '/../src/TimeRecord.php';
    requireAdmin();
    
    $user = getCurrentUser();
    $userModel = new User();
    $timeRecord = new TimeRecord();
    
    $employees = $userModel->getAllEmployees();
    $totalUsers = $userModel->getTotalUsers();
    $workingUsers = $userModel->getWorkingUsersCount();
    
    $error = $_GET['error'] ?? null;
    $success = $_GET['success'] ?? null;
    ?>
    
    <div class="container">
        <header class="header">
            <h1>Panel de Administrador</h1>
            <div class="user-info">
                <span><?php echo escape($user['nombre'] . ' ' . $user['apellidos']); ?></span>
                <a href="dashboard.php" class="btn btn-secondary">Mi Dashboard</a>
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
                    <h3>Resumen General</h3>
                    <div class="summary">
                        <div class="summary-item">
                            <span class="summary-label">Total Empleados:</span>
                            <span class="summary-value"><?php echo $totalUsers; ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Trabajando Ahora:</span>
                            <span class="summary-value" style="color: #27ae60;"><?php echo $workingUsers; ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">No Trabajando:</span>
                            <span class="summary-value" style="color: #e74c3c;"><?php echo $totalUsers - $workingUsers; ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="card full-width">
                    <h3>Lista de Empleados</h3>
                    <?php if (empty($employees)): ?>
                        <p>No hay empleados registrados.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Email</th>
                                        <th>Estado</th>
                                        <th>Última Actividad</th>
                                        <th>Horas Hoy</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($employees as $emp): 
                                        $isWorking = $userModel->isWorking($emp['id']);
                                        $todayHours = $timeRecord->calculateTodayHours($emp['id']);
                                        $lastActivity = $isWorking ? $emp['last_checkin'] : $emp['last_checkout'];
                                    ?>
                                        <tr>
                                            <td>
                                                <?php echo escape($emp['nombre'] . ' ' . $emp['apellidos']); ?>
                                            </td>
                                            <td><?php echo escape($emp['email']); ?></td>
                                            <td>
                                                <?php if ($isWorking): ?>
                                                    <span class="badge badge-success">Trabajando</span>
                                                <?php else: ?>
                                                    <span class="badge badge-warning">No trabajando</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($lastActivity): ?>
                                                    <?php echo date('d/m H:i', strtotime($lastActivity)); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Nunca</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?php echo number_format($todayHours, 2); ?>h</strong>
                                            </td>
                                            <td>
                                                <a href="admin-user.php?id=<?php echo $emp['id']; ?>" class="btn btn-sm btn-secondary">Ver</a>
                                                <?php if ($emp['activo']): ?>
                                                    <a href="actions/deactivate-user.php?id=<?php echo $emp['id']; ?>" 
                                                       class="btn btn-sm btn-danger" 
                                                       onclick="return confirm('¿Desactivar usuario?')">Desactivar</a>
                                                <?php else: ?>
                                                    <a href="actions/activate-user.php?id=<?php echo $emp['id']; ?>" 
                                                       class="btn btn-sm btn-success">Activar</a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="card">
                    <h3>Acciones Rápidas</h3>
                    <div class="action-buttons">
                        <a href="admin-projects.php" class="btn btn-primary">Gestionar Proyectos</a>
                        <a href="admin-reports.php" class="btn btn-secondary">Ver Reportes</a>
                        <a href="dashboard.php" class="btn btn-secondary">Volver al Dashboard</a>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="assets/js/main.js"></script>
</body>
</html>