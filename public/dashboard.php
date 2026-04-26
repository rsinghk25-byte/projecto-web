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
    requireLogin();
    $user = getCurrentUser();
    ?>
    
    <div class="container">
        <header class="header">
            <h1>Panel de Control</h1>
            <div class="user-info">
                <span>Bienvenido, <?php echo escape($user['nombre']); ?></span>
                <a href="logout.php" class="btn btn-secondary">Cerrar sesión</a>
            </div>
        </header>
        
        <main class="main-content">
            <div class="dashboard-grid">
                <div class="card">
                    <h3>Información de Usuario</h3>
                    <p><strong>Nombre:</strong> <?php echo escape($user['nombre'] . ' ' . $user['apellidos']); ?></p>
                    <p><strong>Email:</strong> <?php echo escape($user['email']); ?></p>
                    <p><strong>Rol:</strong> <?php echo escape(ucfirst($user['rol'])); ?></p>
                </div>
                
                <div class="card">
                    <h3>Acciones Rápidas</h3>
                    <div class="action-buttons">
                        <button class="btn btn-primary" onclick="registrarEntrada()">
                            Registrar Entrada
                        </button>
                        <button class="btn btn-warning" onclick="registrarSalida()">
                            Registrar Salida
                        </button>
                    </div>
                </div>
                
                <div class="card">
                    <h3>Últimos Registros</h3>
                    <p>Próximamente...</p>
                </div>
            </div>
        </main>
    </div>
    
    <script src="assets/js/main.js"></script>
    <script>
        function registrarEntrada() {
            alert('Funcionalidad de registro de entrada próximamente');
        }
        
        function registrarSalida() {
            alert('Funcionalidad de registro de salida próximamente');
        }
    </script>
</body>
</html>