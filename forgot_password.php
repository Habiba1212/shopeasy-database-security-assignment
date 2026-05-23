<?php
session_start();
require 'db_connect.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $email = trim($_POST['email']);

    $stmt = $pdo->prepare("
        SELECT user_id
        FROM user
        WHERE email = ?
        LIMIT 1
    ");

    $stmt->execute([$email]);

    $user = $stmt->fetch();

    if ($user) {

        $_SESSION['reset_email'] = $email;

        header("Location: reset_password.php");
        exit();

    } else {

        $message = "No account found with that email.";

    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Forgot Password</title>

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

<h2>Forgot Password</h2>

<?php
if ($message != '') {
    echo "<div class='error'>$message</div>";
}
?>

<form method="POST">

<input
    type="email"
    name="email"
    placeholder="Enter your email"
    required
>

<button type="submit">
    Continue
</button>

</form>

</div>

</body>
</html>
