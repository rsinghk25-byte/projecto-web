<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../src/User.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = intval($_POST['user_id'] ?? 0);
    $newRole = sanitizeInput($_POST['role'] ?? '');
    
    $allowedRoles = ['admin', 'jefe', 'empleado', 'recepcionista'];
    
    if ($userId > 0 && in_array($newRole, $allowedRoles)) {
        $userModel = new User();
        $success = $userModel->updateRole($userId, $newRole);
        
        if ($success) {
            header("Location: ../public/admin.php?success=Rol actualizado correctamente");
            exit;
        }
    }
    
    header("Location: ../public/admin.php?error=Error al actualizar rol");
    exit;
}

header("Location: ../public/admin.php");
exit;