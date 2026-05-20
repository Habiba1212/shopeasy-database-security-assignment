<?php

session_start();

session_unset();

session_destroy();

setcookie(
    'shopeasy_remember_me',
    '',
    time() - 3600,
    "/"
);

header("Location: login.php");

exit();

?>
