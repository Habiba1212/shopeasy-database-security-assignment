<?php
session_start();
require 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // 1. Query the database to look up the user by email, joining their role name
    $stmt = $pdo->prepare("
        SELECT u.user_id, u.full_name, u.email, u.password_hash, r.role_name 
        FROM user u
        JOIN user_role ur ON u.user_id = ur.user_id
        JOIN role r ON ur.role_id = r.role_id
        WHERE u.email = ? LIMIT 1
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // 2. Check password: Supports both team placeholder seeds AND newly hashed accounts
        $is_placeholder_seed = (strpos($user['password_hash'], '$2y$10$example') === 0 || strpos($user['password_hash'], '$2y$10$customer') === 0 || strpos($user['password_hash'], '$2y$10$driver') === 0);
        
        if ($is_placeholder_seed || password_verify($password, $user['password_hash'])) {
            
            // Password is valid! Save details into session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role_name'];

            // 3. Dynamic Routing based on assigned database role
            if ($user['role_name'] === 'admin') {
                header("Location: admin_dashboard.php");
            } elseif ($user['role_name'] === 'driver') {
                header("Location: driver_view.php");
            } else {
                header("Location: shop.php");
            }
            exit();
        } else {
            $error_msg = "Invalid password entry.";
        }
    } else {
        $error_msg = "No account found with that email address.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>ShopEasy - Login</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Arial, sans-serif; background: #f0f2f5; display: flex; justify-content: center; align-items: center; height: 100vh; }
  .login-box { background: #fff; padding: 30px; border-radius: 12px; border: 1px solid #ddd; width: 100%; max-width: 400px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
  .brand { font-size: 22px; font-weight: bold; color: #1a1a2e; text-align: center; margin-bottom: 20px; }
  .field { margin-bottom: 14px; }
  .field label { display: block; font-size: 12px; color: #555; margin-bottom: 4px; font-weight: bold; }
  .field input { width: 100%; padding: 10px; font-size: 13px; border: 1px solid #ccc; border-radius: 6px; background: #fafafa; }
  .btn { width: 100%; padding: 10px; background: #185FA5; color: #fff; border: none; border-radius: 6px; font-size: 14px; font-weight: bold; cursor: pointer; margin-top: 10px; }
  .btn:hover { background: #144d85; }
  .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 6px; font-size: 13px; margin-bottom: 14px; text-align: center; border: 1px solid #f5c6cb; }
  .switch-link { display: block; text-align: center; font-size: 13px; color: #185FA5; text-decoration: none; margin-top: 15px; }
</style>
</head>
<body>
<div class="login-box">
  <div class="brand">ShopEasy Portal</div>
  
  <?php if(isset($error_msg)) echo "<div class='error'>$error_msg</div>"; ?>

  <form method="POST" action="login.php">
    <div class="field">
      <label>Email Address</label>
      <input type="email" name="email" placeholder="customer@shopeasy.com" required>
    </div>
    <div class="field">
      <label>Password</label>
      <input type="password" name="password" placeholder="••••••••" required>
    </div>
    <button type="submit" name="login" class="btn">Login</button>
    <a href="register.php" class="switch-link">New customer? Create an account</a>
  </form>
</div>
</body>
</html>
