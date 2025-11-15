<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/csrf.php';

// Verify this is a POST request and validate CSRF token
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($token)) {
        // Invalid CSRF token - redirect with error
        $_SESSION['logout_error'] = 'Invalid security token';
        header('Location: doctor_login.php');
        exit;
    }
    
    // Completely destroy the session
    $_SESSION = array();
    
    // If it's desired to kill the session, also delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    
    // Finally, destroy the session
    session_destroy();
    
    // Redirect to login page
    header('Location: doctor_login.php');
    exit;
} else {
    // If not POST request, redirect with error
    $_SESSION['logout_error'] = 'Invalid logout request';
    header('Location: doctor_login.php');
    exit;
}