<?php
session_start();
require_once __DIR__ . '/../config/database.php';

function redirectWithError($error, $oldData = []) {
    $params = http_build_query([
        'error' => $error,
        'old' => $oldData
    ]);
    header("Location: /public/register.php?" . $params);
    exit;
}

function redirectWithSuccess($message) {
    header("Location: /public/login.php?success=" . urlencode($message));
    exit;
}

if (!isset($_POST['registro'])) {
    redirectWithError('Método no permitido');
}

$nombre = trim($_POST['nombre'] ?? '');
$apellidos = trim($_POST['apellidos'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

$oldData = [
    'nombre' => $nombre,
    'apellidos' => $apellidos,
    'email' => $email
];

if (empty($nombre)) {
    redirectWithError('El nombre es obligatorio', $oldData);
}
if (strlen($nombre) > 100) {
    redirectWithError('El nombre no puede superar 100 caracteres', $oldData);
}
if (empty($apellidos)) {
    redirectWithError('Los apellidos son obligatorios', $oldData);
}
if (strlen($apellidos) > 150) {
    redirectWithError('Los apellidos no pueden superar 150 caracteres', $oldData);
}
if (empty($email)) {
    redirectWithError('El email es obligatorio', $oldData);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirectWithError('El email no es válido', $oldData);
}
if (strlen($email) > 100) {
    redirectWithError('El email no puede superar 100 caracteres', $oldData);
}
if (empty($password)) {
    redirectWithError('La contraseña es obligatoria', $oldData);
}
if (strlen($password) < 8) {
    redirectWithError('La contraseña debe tener al menos 8 caracteres', $oldData);
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
        "INSERT INTO usuarios (nombre, apellidos, email, password_hash, rol) VALUES (?, ?, ?, ?, 'user')",
        [$nombre, $apellidos, $email, $passwordHash]
    );
    
    if ($result) {
        redirectWithSuccess('Usuario registrado correctamente. Ahora puedes iniciar sesión.');
    } else {
        throw new Exception('Error al registrar usuario');
    }
    
} catch (Exception $e) {
    error_log("Error en registro: " . $e->getMessage());
    redirectWithError('Error en el registro. Intente de nuevo más tarde.', $oldData);
}