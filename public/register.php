<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Control Horario</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <h1>Registro de Usuario</h1>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="error">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="success">
                <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
        <?php endif; ?>
        
        <form action="actions/register.php" method="POST">
            <div class="form-group">
                <label for="nombre">Nombre:</label>
                <input type="text" id="nombre" name="nombre" required 
                       maxlength="100" 
                       value="<?php echo isset($_GET['old']['nombre']) ? htmlspecialchars($_GET['old']['nombre']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="apellidos">Apellidos:</label>
                <input type="text" id="apellidos" name="apellidos" required 
                       maxlength="150"
                       value="<?php echo isset($_GET['old']['apellidos']) ? htmlspecialchars($_GET['old']['apellidos']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required 
                       maxlength="100"
                       value="<?php echo isset($_GET['old']['email']) ? htmlspecialchars($_GET['old']['email']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Contraseña (mínimo 8 caracteres):</label>
                <input type="password" id="password" name="password" required 
                       minlength="8" maxlength="255">
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirmar contraseña:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <div class="form-group">
                <button type="submit" name="registro" class="btn btn-primary">Registrarse</button>
            </div>
        </form>
        
        <p class="login-link">
            ¿Ya tienes cuenta? <a href="login.php">Iniciar sesión</a>
        </p>
    </div>
</body>
</html>