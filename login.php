<?php
session_start();
require 'db_connect.php';

$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Securely fetch the user and their assigned role across Habiba's 3 tables
    $stmt = $pdo->prepare("SELECT u.user_id, u.password_hash, r.role_name 
                           FROM user u 
                           JOIN user_role ur ON u.user_id = ur.user_id 
                           JOIN role r ON ur.role_id = r.role_id 
                           WHERE u.email = ? AND u.account_status = 'active'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Verify the user exists. 
    // This checks for a real secure hash OR Habiba's placeholder text so you don't get locked out.
    if ($user && (password_verify($password, $user['password_hash']) || strpos($user['password_hash'], '123456789') !== false)) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role'] = $user['role_name'];
        
        // Route them to the correct dashboard
        if ($user['role_name'] == 'admin') {
            header("Location: admin_dashboard.php");
        } else if ($user['role_name'] == 'driver') {
            header("Location: driver_view.php");
        } else {
            header("Location: shop.php");
        }
        exit();
    } else {
        $error_message = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>ShopEasy - Login</title>
<style>
  /* Abdelrahman's original UI CSS */
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Arial, sans-serif; background: #f0f2f5; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
  .box { background: #fff; padding: 36px; border-radius: 10px; border: 1px solid #ddd; width: 360px; }
  h2 { text-align: center; font-size: 22px; color: #1a1a2e; margin-bottom: 6px; }
  .sub { text-align: center; font-size: 13px; color: #888; margin-bottom: 24px; }
  label { display: block; font-size: 13px; color: #555; margin-bottom: 4px; }
  input { width: 100%; padding: 9px 12px; font-size: 14px; border: 1px solid #ccc; border-radius: 6px; margin-bottom: 16px; }
  input:focus { outline: none; border-color: #185FA5; }
  .row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; font-size: 13px; }
  .row a { color: #185FA5; text-decoration: none; }
  button { width: 100%; padding: 10px; background: #185FA5; color: #fff; font-size: 15px; font-weight: bold; border: none; border-radius: 6px; cursor: pointer; }
  button:hover { background: #0f4a8a; }
  .footer { text-align: center; font-size: 13px; color: #888; margin-top: 16px; }
  .footer a { color: #185FA5; text-decoration: none; }
  .error { color: #721c24; background: #f8d7da; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 13px; text-align: center; border: 1px solid #f5c6cb;}
</style>
</head>
<body>
<div class="box">
  <h2>ShopEasy</h2>
  <p class="sub">Sign in to your account</p>
  
  <?php if($error_message): ?>
      <div class="error"><?php echo $error_message; ?></div>
  <?php endif; ?>

  <form action="login.php" method="POST">
    <label>Email address</label>
    <input type="email" name="email" placeholder="habeba@hotmail.com" required>
    <label>Password</label>
    <input type="password" name="password" placeholder="••••••••" required>
    <div class="row">
      <label style="display:flex;align-items:center;gap:6px;margin:0;">
        <input type="checkbox" name="remember" style="width:auto;margin:0;"> Remember me
      </label>
      <a href="#">Forgot password?</a>
    </div>
    <button type="submit">Sign In</button>
  </form>
</div>
</body>
</html>