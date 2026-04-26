<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
        header("Location: /public/login.php");
        exit;
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'email' => $_SESSION['user_email'] ?? null,
        'nombre' => $_SESSION['user_nombre'] ?? null,
        'apellidos' => $_SESSION['user_apellidos'] ?? null,
        'rol' => $_SESSION['user_rol'] ?? null
    ];
}

function isAdmin() {
    return isLoggedIn() && ($_SESSION['user_rol'] ?? '') === 'admin';
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("Location: /public/dashboard.php");
        exit;
    }
}

function logout() {
    session_start();
    $_SESSION = [];
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    session_destroy();
    header("Location: /public/login.php?logout=1");
    exit;
}

function escape($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}