<?php
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../config/database.php';

startSecureSession();

function redirectWithError($error) {
    header("Location: /public/login.php?error=" . urlencode($error));
    exit;
}

function redirectToIntended() {
    $intended = $_SESSION['intended_url'] ?? '/public/dashboard.php';
    unset($_SESSION['intended_url']);
    header("Location: " . $intended);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectWithError('Método no permitido');
}

if (!verifyPostWithCsrf()) {
    logSecurityEvent('CSRF_VIOLATION', 'Login - Token inválido');
    redirectWithError('Error de validación. Intente de nuevo.');
}

if (!checkRateLimit('login', 5, 300)) {
    logSecurityEvent('RATE_LIMIT', 'Login - Límite excedido desde ' . getClientIp());
    redirectWithError('Demasiados intentos. Espere 5 minutos.');
}

$email = sanitizeEmail($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || !isValidEmail($email)) {
    redirectWithError('El email es obligatorio y debe ser válido');
}

if (empty($password)) {
    redirectWithError('La contraseña es obligatoria');
}

try {
    $db = getDB();
    
    $stmt = $db->query(
        "SELECT id, email, password_hash, nombre, apellidos, rol, activo 
         FROM usuarios WHERE email = ?",
        [$email]
    );
    
    if (!$stmt) {
        throw new Exception('Error en la consulta');
    }
    
    $user = $stmt->fetch();
    
    if (!$user) {
        logSecurityEvent('FAILED_LOGIN', 'Usuario no encontrado: ' . $email);
        redirectWithError('Email o contraseña incorrectos');
    }
    
    if (!password_verify($password, $user['password_hash'])) {
        logSecurityEvent('FAILED_LOGIN', 'Contraseña inválida para: ' . $email);
        redirectWithError('Email o contraseña incorrectos');
    }
    
    if (!$user['activo']) {
        logSecurityEvent('INACTIVE_LOGIN', 'Intento de login con cuenta inactiva: ' . $email);
        redirectWithError('Cuenta desactivada. Contacte con el administrador.');
    }
    
    initSecureUserSession($user['id'], $user['email']);
    $_SESSION['user_nombre'] = $user['nombre'];
    $_SESSION['user_apellidos'] = $user['apellidos'];
    $_SESSION['user_rol'] = $user['rol'];
    $_SESSION['logged_in'] = true;
    
    clearRateLimit('login');
    
    redirectToIntended();
    
} catch (Exception $e) {
    error_log("Error en login: " . $e->getMessage());
    redirectWithError('Error en el inicio de sesión. Intente de nuevo.');
}