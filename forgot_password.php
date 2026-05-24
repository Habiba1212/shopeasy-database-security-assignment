<?php
session_start();
require 'db_connect.php';
require_once 'audit_helper.php';

$message = '';
$simulation_link = ''; // Used ONLY for school project testing

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $email = trim($_POST['email']);

    // 1. Find the user
    $stmt = $pdo->prepare("SELECT user_id FROM user WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // 2. Generate a cryptographically secure token
        $token = bin2hex(random_bytes(32)); // 64 characters long
        
        // 3. Set expiration time to 15 minutes from now
        $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        // 4. Save token to database
        $update_stmt = $pdo->prepare("
            UPDATE user 
            SET reset_token = ?, token_expires_at = ? 
            WHERE user_id = ?
        ");
        $update_stmt->execute([$token, $expires_at, $user['user_id']]);

        // 5. SUCCESS AUDIT LOGGING
        logAudit($pdo, $user['user_id'], 'PASSWORD_RESET_REQUESTED', 'User requested a secure password reset link.');

        // 6. SIMULATION (For Assignment Only)
        // In reality, you would use PHPMailer here. We will just print the link for testing.
        $reset_url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=" . $token;
        $simulation_link = "<div class='success-box'><strong>Email Sent (Simulation)!</strong><br>Click here to reset: <a href='$reset_url'>$reset_url</a></div>";
        
        // Anti-Enumeration generic message
        $message = "If an account exists for that email, a password reset link has been sent.";

    } else {
        // FAILURE AUDIT LOGGING
        logAudit($pdo, 0, 'PASSWORD_RESET_FAILED', 'Unknown email attempted password reset: ' . $email);
        
        // Anti-Enumeration generic message (Notice it is the EXACT same message)
        $message = "If an account exists for that email, a password reset link has been sent.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>ShopEasy - Forgot Password</title>
<style>
body { font-family: Arial, sans-serif; background: #f5f5f5; display: flex; justify-content: center; align-items: center; height: 100vh; flex-direction: column; }
.card { background: white; padding: 30px; border-radius: 10px; width: 350px; border: 1px solid #ddd; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
h2 { text-align: center; color: #1a1a2e; margin-bottom: 20px; }
input { width: 100%; padding: 10px; margin-top: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
button { width: 100%; padding: 10px; background: #185FA5; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
button:hover { background: #134b82; }
.info { color: #155724; background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 13px; text-align: center; }
.success-box { margin-top: 20px; background: #e2e8f0; padding: 15px; border-radius: 8px; border-left: 4px solid #3b82f6; font-size: 13px; word-break: break-all; max-width: 400px; }
.back-link { display: block; text-align: center; margin-top: 15px; font-size: 13px; color: #185FA5; text-decoration: none; }
</style>
</head>
<body>

<div class="card">
    <h2>Forgot Password</h2>

    <?php if ($message != ''): ?>
        <div class='info'><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="email" name="email" placeholder="Enter your registered email" required>
        <button type="submit">Send Reset Link</button>
    </form>
    <a href="login.php" class="back-link">Return to Login</a>
</div>

<?php if ($simulation_link != '') echo $simulation_link; ?>

</body>
</html>
