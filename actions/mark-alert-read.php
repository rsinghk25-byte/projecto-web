<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../src/Alert.php';

requireLogin();

$alertId = intval($_GET['id'] ?? 0);
$userId = $_SESSION['user_id'];

if ($alertId === 0) {
    header("Location: dashboard.php?error=ID de alerta inválido");
    exit;
}

try {
    $alert = new Alert();
    
    $alertData = $alert->markAsRead($alertId);
    
    if ($alertData) {
        $referer = $_SERVER['HTTP_REFERER'] ?? 'dashboard.php';
        header("Location: $referer");
    } else {
        header("Location: dashboard.php?error=No se pudo marcar la alerta como leída");
    }
} catch (Exception $e) {
    error_log("Error al marcar alerta: " . $e->getMessage());
    header("Location: dashboard.php?error=Error al procesar la alerta");
}