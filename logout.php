<?php
session_start();

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
