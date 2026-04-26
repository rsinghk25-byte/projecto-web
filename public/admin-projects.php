<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Proyectos - Control Horario</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php
    require_once __DIR__ . '/../includes/auth.php';
    require_once __DIR__ . '/../src/Project.php';
    requireAdmin();
    
    $user = getCurrentUser();
    $projectModel = new Project();
    
    // Manejar acciones (crear/actualizar/desactivar)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'create') {
            $nombre = sanitizeInput($_POST['nombre'] ?? '');
            $descripcion = sanitizeInput($_POST['descripcion'] ?? '');
            $cliente = sanitizeInput($_POST['cliente'] ?? '');
            $fechaInicio = $_POST['fecha_inicio'] ?: null;
            $fechaFin = $_POST['fecha_fin'] ?: null;
            
            if (!empty($nombre)) {
                $projectModel->create($nombre, $descripcion, $cliente, $fechaInicio, $fechaFin);
                header("Location: admin-projects.php?success=Proyecto creado correctamente");
                exit;
            }
        } elseif ($action === 'update') {
            $id = intval($_POST['id'] ?? 0);
            $nombre = sanitizeInput($_POST['nombre'] ?? '');
            $descripcion = sanitizeInput($_POST['descripcion'] ?? '');
            $cliente = sanitizeInput($_POST['cliente'] ?? '');
            $fechaInicio = $_POST['fecha_inicio'] ?: null;
            $fechaFin = $_POST['fecha_fin'] ?: null;
            
            if ($id > 0 && !empty($nombre)) {
                $projectModel->update($id, $nombre, $descripcion, $cliente, $fechaInicio, $fechaFin);
                header("Location: admin-projects.php?success=Proyecto actualizado correctamente");
                exit;
            }
        } elseif ($action === 'toggle') {
            $id = intval($_POST['id'] ?? 0);
            $activo = intval($_POST['activo'] ?? 0);
            $projectModel->toggleActive($id, !$activo);
            header("Location: admin-projects.php?success=Estado del proyecto actualizado");
            exit;
        }
    }
    
    $projects = $projectModel->getAllActive();
    $editId = intval($_GET['edit'] ?? 0);
    $editProject = $editId ? $projectModel->getById($editId) : null;
    
    $error = $_GET['error'] ?? null;
    $success = $_GET['success'] ?? null;
    ?>
    
    <div class="container">
        <header class="header">
            <h1>📁 Gestión de Proyectos</h1>
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
                <div class="card full-width">
                    <h3><?php echo $editProject ? '✏️ Editar Proyecto' : '➕ Crear Nuevo Proyecto'; ?></h3>
                    <form method="POST" action="admin-projects.php">
                        <input type="hidden" name="action" value="<?php echo $editProject ? 'update' : 'create'; ?>">
                        <?php if ($editProject): ?>
                            <input type="hidden" name="id" value="<?php echo $editProject['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nombre">Nombre del Proyecto: *</label>
                                <input type="text" id="nombre" name="nombre" required maxlength="150"
                                       value="<?php echo $editProject ? escape($editProject['nombre']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="cliente">Cliente:</label>
                                <input type="text" id="cliente" name="cliente" maxlength="150"
                                       value="<?php echo $editProject ? escape($editProject['cliente'] ?? '') : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="descripcion">Descripción:</label>
                            <textarea id="descripcion" name="descripcion" rows="3"><?php echo $editProject ? escape($editProject['descripcion'] ?? '') : ''; ?></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="fecha_inicio">Fecha de Inicio:</label>
                                <input type="date" id="fecha_inicio" name="fecha_inicio"
                                       value="<?php echo $editProject ? escape($editProject['fecha_inicio'] ?? '') : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="fecha_fin">Fecha de Fin:</label>
                                <input type="date" id="fecha_fin" name="fecha_fin"
                                       value="<?php echo $editProject ? escape($editProject['fecha_fin'] ?? '') : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="action-buttons">
                            <button type="submit" class="btn btn-primary">
                                <?php echo $editProject ? '💾 Guardar Cambios' : '➕ Crear Proyecto'; ?>
                            </button>
                            <?php if ($editProject): ?>
                                <a href="admin-projects.php" class="btn btn-secondary">Cancelar</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                
                <div class="card full-width">
                    <h3>📋 Proyectos Activos</h3>
                    <?php if (empty($projects)): ?>
                        <p class="text-muted">No hay proyectos registrados.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Cliente</th>
                                        <th>Inicio</th>
                                        <th>Fin</th>
                                        <th>Descripción</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($projects as $p): ?>
                                        <tr>
                                            <td><strong><?php echo escape($p['nombre']); ?></strong></td>
                                            <td><?php echo escape($p['cliente'] ?? '—'); ?></td>
                                            <td><?php echo $p['fecha_inicio'] ? date('d/m/Y', strtotime($p['fecha_inicio'] ?? '')) : '—'; ?></td>
                                            <td><?php echo $p['fecha_fin'] ? date('d/m/Y', strtotime($p['fecha_fin'] ?? '')) : '—'; ?></td>
                                            <td><?php echo escape(substr($p['descripcion'] ?? '', 0, 50)); ?>
                                                <?php echo strlen($p['descripcion'] ?? '') > 50 ? '...' : ''; ?>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="admin-projects.php?edit=<?php echo $p['id']; ?>" 
                                                       class="btn btn-sm btn-primary">✏️ Editar</a>
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="action" value="toggle">
                                                        <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                                        <input type="hidden" name="activo" value="1">
                                                        <button type="submit" class="btn btn-sm btn-warning"
                                                                onclick="return confirm('¿Desactivar proyecto?')">
                                                            🚫 Desactivar
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="card">
                    <h3>📊 Estadísticas</h3>
                    <div class="summary">
                        <div class="summary-item">
                            <span class="summary-label">Total Proyectos:</span>
                            <span class="summary-value"><?php echo count($projects); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <h3>🔗 Acciones Rápidas</h3>
                    <div class="action-buttons">
                        <a href="admin.php" class="btn btn-primary">Panel Admin</a>
                        <a href="admin-reports.php" class="btn btn-secondary">Ver Reportes</a>
                        <a href="admin-alerts.php" class="btn btn-secondary">Ver Alertas</a>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="assets/js/main.js"></script>
</body>
</html>