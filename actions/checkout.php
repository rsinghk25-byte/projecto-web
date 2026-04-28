<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/TimeRecord.php';

function redirectBack($error = null, $success = null) {
    $referer = $_SERVER['HTTP_REFERER'] ?? 'dashboard.php';
    $params = [];
    if ($error) $params['error'] = $error;
    if ($success) $params['success'] = $success;
    if (!empty($params)) {
        $referer .= (strpos($referer, '?') !== false ? '&' : '?') . http_build_query($params);
    }
    header("Location: $referer");
    exit;
}

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    redirectBack('Debes iniciar sesión');
}

if (!isset($_POST['checkout'])) {
    redirectBack('Método no permitido');
}

$userId = $_SESSION['user_id'];
$notes = trim($_POST['notas'] ?? '');

try {
    $timeRecord = new TimeRecord();
    
    if (!$timeRecord->isCurrentlyCheckedIn($userId)) {
        redirectBack('No has registrado una entrada. Debes registrar entrada primero.');
    }
    
    $result = $timeRecord->checkOut($userId, $notes);
    
    if ($result) {
        $hours = $timeRecord->calculateTodayHours($userId);
        $hoursText = number_format($hours, 2);
        redirectBack(null, 'Salida registrada correctamente a las ' . date('H:i') . '. Horas hoy: ' . $hoursText . 'h');
    } else {
        throw new Exception('Error al registrar salida');
    }
    
} catch (Exception $e) {
    error_log("Error en check-out: " . $e->getMessage());
    redirectBack('Error al registrar salida. Intente de nuevo.');
}