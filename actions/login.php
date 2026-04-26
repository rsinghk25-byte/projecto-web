<?php
session_start();
require_once __DIR__ . '/../config/database.php';

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

if (!isset($_POST['login'])) {
    redirectWithError('Método no permitido');
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email)) {
    redirectWithError('El email es obligatorio');
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
        redirectWithError('Email o contraseña incorrectos');
    }
    
    if (!password_verify($password, $user['password_hash'])) {
        redirectWithError('Email o contraseña incorrectos');
    }
    
    if (!$user['activo']) {
        redirectWithError('Cuenta desactivada. Contacte con el administrador.');
    }
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_nombre'] = $user['nombre'];
    $_SESSION['user_apellidos'] = $user['apellidos'];
    $_SESSION['user_rol'] = $user['rol'];
    $_SESSION['logged_in'] = true;
    
    redirectToIntended();
    
} catch (Exception $e) {
    error_log("Error en login: " . $e->getMessage());
    redirectWithError('Error en el inicio de sesión. Intente de nuevo.');
}