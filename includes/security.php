<?php
/**
 * Funciones de seguridad para la aplicación
 * 
 * Mejoras de seguridad aplicadas:
 * - Sanitización de datos de entrada
 * - Protección contra XSS
 * - Protección contra CSRF
 * - Validación de datos
 * - Headers de seguridad
 */

// Iniciar sesión con configuración segura
function startSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // Configuración segura de sesiones
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
        ini_set('session.cookie_samesite', 'Strict');
        
        session_start();
        
        // Regenerar ID de sesión periódicamente
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } elseif (time() - $_SESSION['created'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
    }
}

// Escapar salida HTML para prevenir XSS
if (!function_exists('escape')) {
function escape($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
}
// Sanitizar entrada de texto
function sanitizeInput($input) {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return $input;
}

// Sanitizar email
function sanitizeEmail($email) {
    return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
}

// Validar email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Validar entero positivo
function isValidPositiveInt($value) {
    return filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false;
}

// Generar token CSRF
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verificar token CSRF
function verifyCsrfToken($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Token CSRF para formularios
function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . generateCsrfToken() . '">';
}

// Verificar método POST con CSRF
function verifyPostWithCsrf() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return false;
    }
    
    $token = $_POST['csrf_token'] ?? '';
    return verifyCsrfToken($token);
}

// Prevenir clickjacking
function setSecurityHeaders() {
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

// Limitar longitud de string
function limitLength($string, $max) {
    if (strlen($string) > $max) {
        return substr($string, 0, $max);
    }
    return $string;
}

// Validar contraseña (mínimo 8 caracteres, al menos una mayúscula y un número)
function isValidPassword($password) {
    return preg_match('/^(?=.*[A-Z])(?=.*\d).{8,}$/', $password) === 1;
}

// Rate limiting simple
function checkRateLimit($action, $maxAttempts = 5, $timeWindow = 300) {
    $key = 'rate_limit_' . $action;
    $attempts = $_SESSION[$key]['attempts'] ?? 0;
    $lastAttempt = $_SESSION[$key]['time'] ?? 0;
    
    if (time() - $lastAttempt > $timeWindow) {
        $_SESSION[$key] = ['attempts' => 1, 'time' => time()];
        return true;
    }
    
    if ($attempts >= $maxAttempts) {
        return false;
    }
    
    $_SESSION[$key]['attempts']++;
    return true;
}

// Limpiar datos de rate limiting
function clearRateLimit($action) {
    unset($_SESSION['rate_limit_' . $action]);
}

// Obtener IP del cliente (considerando proxies)
function getClientIp() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $forwardedIp = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        if (filter_var(trim($forwardedIp), FILTER_VALIDATE_IP)) {
            $ip = trim($forwardedIp);
        }
    }
    
    return $ip;
}

// Registrar actividad sospechosa
function logSecurityEvent($event, $details = '') {
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'ip' => getClientIp(),
        'event' => $event,
        'details' => $details,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ];
    
    error_log("SECURITY: " . json_encode($logEntry));
}

// Verificar si la sesión es válida
function validateSession() {
    if (!isset($_SESSION['user_ip'])) {
        return false;
    }
    
    // Verificar que la IP no haya cambiado
    if ($_SESSION['user_ip'] !== getClientIp()) {
        logSecurityEvent('IP_CHANGE', 'Session IP mismatch');
        return false;
    }
    
    return true;
}

// Inicializar sesión segura para usuario
function initSecureUserSession($userId, $userEmail) {
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_email'] = $userEmail;
    $_SESSION['user_ip'] = getClientIp();
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $_SESSION['last_activity'] = time();
}

// Verificar actividad reciente
function checkSessionActivity($timeout = 3600) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
        session_destroy();
        return false;
    }
    $_SESSION['last_activity'] = time();
    return true;
}
