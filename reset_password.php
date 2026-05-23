<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['reset_email'])) {

    header("Location: forgot_password.php");
    exit();
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {

        $message = "Passwords do not match.";

    } else {

        $password_hash = password_hash(
            $new_password,
            PASSWORD_DEFAULT
        );

        $stmt = $pdo->prepare("
            UPDATE user
            SET password_hash = ?
            WHERE email = ?
        ");

        $stmt->execute([
            $password_hash,
            $_SESSION['reset_email']
        ]);

        unset($_SESSION['reset_email']);

        header("Location: login.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Reset Password</title>

<style>

body{
    font-family:Arial;
    background:#f5f5f5;
    display:flex;
    justify-content:center;
    align-items:center;
    height:100vh;
}

.card{
    background:white;
    padding:30px;
    border-radius:10px;
    width:350px;
    border:1px solid #ddd;
}

input{
    width:100%;
    padding:10px;
    margin-top:10px;
    margin-bottom:15px;
}

button{
    width:100%;
    padding:10px;
    background:#185FA5;
    color:white;
    border:none;
    border-radius:5px;
    cursor:pointer;
}

.error{
    color:red;
    margin-bottom:15px;
}

</style>

</head>

<body>

<div class="card">

<h2>Reset Password</h2>

<?php
if ($message != '') {
    echo "<div class='error'>$message</div>";
}
?>

<form method="POST">

<input
    type="password"
    name="new_password"
    placeholder="New Password"
    required
>

<input
    type="password"
    name="confirm_password"
    placeholder="Confirm Password"
    required
>

<button type="submit">
    Reset Password
</button>

</form>

</div>

</body>
</html>
