<?php
session_start();
require 'db_connect.php';
require_once 'audit_helper.php';

$message = '';
$token = $_GET['token'] ?? '';

// 1. Kick out anyone without a token
if (empty($token)) {
    header("Location: login.php");
    exit();
}

// 2. Verify the token is real and hasn't expired
$stmt = $pdo->prepare("
    SELECT user_id 
    FROM user 
    WHERE reset_token = ? AND token_expires_at > NOW()
");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    // Audit log the invalid attempt
    logAudit($pdo, 0, 'INVALID_TOKEN_USE', 'Someone attempted to use an invalid or expired reset token.');
    die("<div style='text-align:center; padding:50px; font-family:Arial;'><h2>Invalid or Expired Link</h2><p>Your password reset link has expired or is invalid. Please request a new one.</p><a href='forgot_password.php'>Go back</a></div>");
}

// 3. Process the new password
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $message = "Passwords do not match.";
    } else {
        // Hash the new password
        $password_hash = password_hash($new_password, PASSWORD_BCRYPT);

        // Update the password AND destroy the token so it can never be used again
        $update_stmt = $pdo->prepare("
            UPDATE user 
            SET password_hash = ?, reset_token = NULL, token_expires_at = NULL 
            WHERE user_id = ?
        ");
        $update_stmt->execute([$password_hash, $user['user_id']]);

        // SUCCESS AUDIT LOGGING
        logAudit($pdo, $user['user_id'], 'PASSWORD_CHANGED', 'User successfully reset their password via token.');

        // Force a clean slate
        session_unset();
        session_destroy();

        // Redirect to login with success message via URL parameter
        header("Location: login.php?reset=success");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>ShopEasy - Reset Password</title>
<style>
body { font-family: Arial, sans-serif; background: #f5f5f5; display: flex; justify-content: center; align-items: center; height: 100vh; }
.card { background: white; padding: 30px; border-radius: 10px; width: 350px; border: 1px solid #ddd; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
h2 { text-align: center; color: #1a1a2e; margin-bottom: 20px; }
input { width: 100%; padding: 10px; margin-top: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
button { width: 100%; padding: 10px; background: #185FA5; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
button:hover { background: #134b82; }
.error { color: #721c24; background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 13px; text-align: center; }
</style>
</head>
<body>

<div class="card">
    <h2>Set New Password</h2>

    <?php if ($message != ''): ?>
        <div class='error'><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="password" name="new_password" placeholder="New Password" required minlength="8">
        <input type="password" name="confirm_password" placeholder="Confirm Password" required minlength="8">
        <button type="submit">Update Password</button>
    </form>
</div>

</body>
</html>
