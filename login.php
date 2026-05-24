<?php
session_start();

require 'db_connect.php';
require 'audit_helper.php';

// ---------------- LOGIN PROCESS ----------------

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // GET USER

    $stmt = $pdo->prepare("
        SELECT 
            u.user_id,
            u.password_hash,
            u.full_name,
            u.account_status,
            u.failed_login_attempts,
            r.role_name
        FROM user u
        JOIN user_role ur ON u.user_id = ur.user_id
        JOIN role r ON ur.role_id = r.role_id
        WHERE u.email = ?
    ");

    $stmt->execute([$email]);

    $user = $stmt->fetch();

    // USER EXISTS

    if ($user) {

        // CHECK IF ACCOUNT LOCKED

        if ($user['account_status'] === 'locked') {

            $error = "Your account is locked.";

            logAudit(
                $pdo,
                $user['user_id'],
                'LOCKED_LOGIN_ATTEMPT',
                'Locked account attempted to log in'
            );

        } elseif ($user['account_status'] !== 'active') {

            $error = "Your account is not active.";

            logAudit(
                $pdo,
                $user['user_id'],
                'LOGIN_BLOCKED',
                'Inactive or suspended account attempted to log in'
            );

        } else {

            // VERIFY PASSWORD

            if (password_verify($password, $user['password_hash'])) {

                // RESET FAILED ATTEMPTS

                $reset_stmt = $pdo->prepare("
                    UPDATE user
                    SET 
                        failed_login_attempts = 0,
                        last_login = CURRENT_TIMESTAMP
                    WHERE user_id = ?
                ");

                $reset_stmt->execute([$user['user_id']]);

                // CREATE SESSION

                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['role'] = $user['role_name'];
                $_SESSION['full_name'] = $user['full_name'];

                // ---------------- AUDIT LOGGING ----------------
                logAudit(
                    $pdo,
                    $user['user_id'],
                    'LOGIN_SUCCESS',
                    'User logged in successfully'
                );

                // REDIRECT BASED ON ROLE

                if ($user['role_name'] === 'admin') {

                    header("Location: admin_dashboard.php");

                } elseif ($user['role_name'] === 'driver') {

                    header("Location: driver_view.php");

                } else {

                    header("Location: shop.php");
                }

                exit();

            } else {
                // WRONG PASSWORD
                $attempts = $user['failed_login_attempts'] + 1;

                // LOCK ACCOUNT AFTER 3 ATTEMPTS
                if ($attempts >= 3) {
                    $lock_stmt = $pdo->prepare("
                        UPDATE user
                        SET failed_login_attempts = ?, account_status = 'locked'
                        WHERE user_id = ?
                    ");
                    $lock_stmt->execute([$attempts, $user['user_id']]);

                    $error = "Account locked after 3 failed attempts.";

                    logAudit($pdo, $user['user_id'], 'ACCOUNT_LOCKED', 'Account locked after 3 failed login attempts');
                } else {
                    // FIX: Update the attempts counter even if it's below 3!
                    $attempt_stmt = $pdo->prepare("
                        UPDATE user
                        SET failed_login_attempts = ?
                        WHERE user_id = ?
                    ");
                    $attempt_stmt->execute([$attempts, $user['user_id']]);

                    $error = "Invalid email or password.";

                    logAudit($pdo, $user['user_id'], 'LOGIN_FAILED', 'Failed login attempt. Attempt count: ' . $attempts);
                }
            }

                // FAILED LOGIN AUDIT LOG
                if ($attempts >= 3) {
                    logAudit(
                        $pdo,
                        $user['user_id'],
                        'ACCOUNT_LOCKED',
                        'Account locked after 3 failed login attempts'
                    );
                } else {
                    logAudit(
                        $pdo,
                        $user['user_id'],
                        'LOGIN_FAILED',
                        'Failed login attempt. Attempt count: ' . $attempts
                    );
                }
            }
        }

    } else {

        $error = "Invalid email or password.";
    }

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<title>ShopEasy - Login</title>

<style>

*{
    box-sizing:border-box;
    margin:0;
    padding:0;
}

body{
    font-family:Arial,sans-serif;
    background:#f0f2f5;
    display:flex;
    justify-content:center;
    align-items:center;
    height:100vh;
}

.login-card{
    background:#fff;
    padding:30px;
    border-radius:10px;
    box-shadow:0 4px 12px rgba(0,0,0,0.1);
    width:100%;
    max-width:360px;
}

.login-card h2{
    text-align:center;
    color:#1a1a2e;
    margin-bottom:20px;
    font-size:22px;
}

.input-group{
    margin-bottom:15px;
}

.input-group label{
    display:block;
    font-size:13px;
    color:#555;
    margin-bottom:5px;
    font-weight:bold;
}

.input-group input[type="email"],
.input-group input[type="password"]{
    width:100%;
    padding:10px;
    border:1px solid #ccc;
    border-radius:6px;
    font-size:14px;
}

.options-row{
    display:flex;
    justify-content:flex-end;
    align-items:center;
    font-size:12px;
    margin-bottom:20px;
}

.forgot-link{
    color:#185FA5;
    text-decoration:none;
    font-weight:bold;
}

.forgot-link:hover{
    text-decoration:underline;
}

.btn{
    width:100%;
    padding:10px;
    background:#185FA5;
    color:#fff;
    border:none;
    border-radius:6px;
    font-size:14px;
    font-weight:bold;
    cursor:pointer;
}

.btn:hover{
    background:#12487e;
}

.error{
    background:#f8d7da;
    color:#721c24;
    padding:10px;
    border-radius:6px;
    font-size:13px;
    margin-bottom:15px;
    text-align:center;
    border:1px solid #f5c6cb;
}

.footer-text{
    text-align:center;
    font-size:12px;
    margin-top:15px;
    color:#666;
}

</style>

</head>

<body>

<div class="login-card">

    <h2>Welcome to ShopEasy</h2>

    <?php if($error): ?>

        <div class="error">
            <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
        </div>

    <?php endif; ?>

    <form method="POST" action="login.php">

        <div class="input-group">

            <label>Email Address</label>

            <input 
                type="email" 
                name="email" 
                required 
                placeholder="name@example.com"
            >

        </div>

        <div class="input-group">

            <label>Password</label>

            <input 
                type="password" 
                name="password" 
                required 
                placeholder="••••••••"
            >

        </div>

        <div class="options-row">

            <a href="forgot_password.php" class="forgot-link">
                Forgot Password?
            </a>

        </div>

        <button type="submit" class="btn">
            Login
        </button>

    </form>

    <div class="footer-text">

        Don't have an account?

        <a href="register.php" class="forgot-link">
            Register here
        </a>

    </div>

</div>

</body>
</html>
