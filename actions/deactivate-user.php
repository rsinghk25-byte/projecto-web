<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../src/User.php';

requireAdmin();

$userId = intval($_GET['id'] ?? 0);
$currentUserId = $_SESSION['user_id'];

if ($userId === $currentUserId) {
    header("Location: /public/admin.php?error=No puedes desactivarte a ti mismo");
    exit;
}

try {
    $userModel = new User();
    $userModel->toggleActive($userId, false);
    header("Location: /public/admin.php?success=Usuario desactivado correctamente");
} catch (Exception $e) {
    error_log("Error al desactivar usuario: " . $e->getMessage());
    header("Location: /public/admin.php?error=Error al desactivar usuario");
}