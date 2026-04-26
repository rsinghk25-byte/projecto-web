<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../src/User.php';

requireAdmin();

$userId = intval($_GET['id'] ?? 0);

try {
    $userModel = new User();
    $userModel->toggleActive($userId, true);
    header("Location: /public/admin.php?success=Usuario activado correctamente");
} catch (Exception $e) {
    error_log("Error al activar usuario: " . $e->getMessage());
    header("Location: /public/admin.php?error=Error al activar usuario");
}