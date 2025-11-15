<?php
// Absolute paths for includes
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Start session if not already started
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Unset all session variables
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

// Redirect to login page with a logout message
header('Location: login.php?reason=logout_success');
exit;
?>