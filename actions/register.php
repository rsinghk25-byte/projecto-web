<?php
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../config/database.php';

startSecureSession();

function redirectWithError($error, $oldData = []) {
    $params = http_build_query([
        'error' => $error,
        'old' => $oldData
    ]);
    header("Location: register.php?" . $params);
    exit;
}

function redirectWithSuccess($message) {
    header("Location: login.php?success=" . urlencode($message));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectWithError('Método no permitido');
}

if (!verifyPostWithCsrf()) {
    logSecurityEvent('CSRF_VIOLATION', 'Registro - Token inválido');
    redirectWithError('Error de validación. Intente de nuevo.');
}

if (!checkRateLimit('register', 3, 300)) {
    logSecurityEvent('RATE_LIMIT', 'Registro - Límite excedido');
    redirectWithError('Demasiados intentos. Espere 5 minutos.');
}

$nombre = sanitizeInput($_POST['nombre'] ?? '');
$apellidos = sanitizeInput($_POST['apellidos'] ?? '');
$email = sanitizeEmail($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

$oldData = [
    'nombre' => $nombre,
    'apellidos' => $apellidos,
    'email' => $email
];

if (empty($nombre) || strlen($nombre) > 100) {
    redirectWithError('El nombre es obligatorio y no puede superar 100 caracteres', $oldData);
}

if (empty($apellidos) || strlen($apellidos) > 150) {
    redirectWithError('Los apellidos son obligatorios y no pueden superar 150 caracteres', $oldData);
}

if (empty($email) || !isValidEmail($email) || strlen($email) > 100) {
    redirectWithError('El email es obligatorio y debe ser válido (máx 100 caracteres)', $oldData);
}

if (empty($password) || strlen($password) < 8) {
    redirectWithError('La contraseña es obligatoria y debe tener al menos 8 caracteres', $oldData);
}

if ($password !== $confirm_password) {
    redirectWithError('Las contraseñas no coinciden', $oldData);
}

try {
    $db = getDB();
    
    $stmt = $db->query(
        "SELECT id FROM usuarios WHERE email = ?",
        [$email]
    );
    
    if ($stmt && $stmt->fetch()) {
        redirectWithError('El email ya está registrado', $oldData);
    }
    
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    $result = $db->query(
        "INSERT INTO usuarios (nombre, apellidos, email, password_hash, rol) 
         VALUES (?, ?, ?, ?, 'user')",
        [$nombre, $apellidos, $email, $passwordHash]
    );
    
    if ($result) {
        clearRateLimit('register');
        redirectWithSuccess('Usuario registrado correctamente. Ahora puedes iniciar sesión.');
    } else {
        throw new Exception('Error al registrar usuario');
    }
    
} catch (Exception $e) {
    error_log("Error en registro: " . $e->getMessage());
    redirectWithError('Error en el registro. Intente de nuevo más tarde.', $oldData);
}