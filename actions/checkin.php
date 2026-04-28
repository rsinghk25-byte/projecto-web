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

if (!isset($_POST['checkin'])) {
    redirectBack('Método no permitido');
}

$userId = $_SESSION['user_id'];
$projectId = !empty($_POST['proyecto_id']) ? intval($_POST['proyecto_id']) : null;
$notes = trim($_POST['notas'] ?? '');

try {
    $timeRecord = new TimeRecord();
    
    if ($timeRecord->isCurrentlyCheckedIn($userId)) {
        redirectBack('Ya has registrado una entrada. Debes registrar la salida primero.');
    }
    
    if ($projectId) {
        $db = getDB();
        $project = $db->query("SELECT id, activo FROM proyectos WHERE id = ?", [$projectId])->fetch();
        if (!$project) {
            redirectBack('El proyecto seleccionado no existe');
        }
        if (!$project['activo']) {
            redirectBack('El proyecto seleccionado ya no está activo');
        }
    }
    
    $result = $timeRecord->checkIn($userId, $projectId, $notes);
    
    if ($result) {
        redirectBack(null, 'Entrada registrada correctamente a las ' . date('H:i'));
    } else {
        throw new Exception('Error al registrar entrada');
    }
    
} catch (Exception $e) {
    error_log("Error en check-in: " . $e->getMessage());
    redirectBack('Error al registrar entrada. Intente de nuevo.');
}