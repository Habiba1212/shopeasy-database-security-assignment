<?php
session_start();

// ---------------- AUDIT LOGGING ----------------
// We must log the action BEFORE we destroy the session!
require 'db_connect.php';
require_once 'audit_helper.php';

if (isset($_SESSION['user_id'])) {
    $user_id = (int) $_SESSION['user_id'];
    
    logAudit(
        $pdo,
        $user_id,
        'LOGOUT_SUCCESS',
        'User logged out and terminated session securely'
    );
}
// -----------------------------------------------

// Now it is safe to completely wipe the session memory
$_SESSION = [];

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

session_unset();
session_destroy();

// Remove remember-me cookie securely
setcookie(
    'shopeasy_remember_me',
    '',
    time() - 3600,
    "/"
);

header("Location: login.php");
exit();
?>

session_unset();
session_destroy();

// Remove remember-me cookie
setcookie(
    'shopeasy_remember_me',
    '',
    time() - 3600,
    "/"
);

header("Location: login.php");
exit();
?>
