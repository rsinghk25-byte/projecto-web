<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../src/Alert.php';

requireAdmin();

try {
    $alert = new Alert();
    $generated = $alert->generateDailyAlerts();
    header("Location: admin.php?success=Se generaron $generated alertas");
} catch (Exception $e) {
    error_log("Error al generar alertas: " . $e->getMessage());
    header("Location: admin.php?error=Error al generar alertas");
}