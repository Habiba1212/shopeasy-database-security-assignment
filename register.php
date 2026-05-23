<?php
session_start();
require 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = $_POST['password'];

    // 1. Securely hash the password using bcrypt (Crucial Security Measure!)
    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    try {
        $pdo->beginTransaction();

        // 2. Insert the new user into the user table matching Habiba's schema
        $stmt = $pdo->prepare("INSERT INTO user (full_name, email, password_hash, phone, account_status) VALUES (?, ?, ?, ?, 'active')");
        $stmt->execute([$full_name, $email, $password_hash, $phone]);
        $new_user_id = $pdo->lastInsertId();

        // 3. Link this user to the 'customer' role (role_id = 2) in user_role table
        $role_stmt = $pdo->prepare("INSERT INTO user_role (user_id, role_id) VALUES (?, 2)");
        $role_stmt->execute([$new_user_id]);

        // 4. Create a default location record so shop.php doesn't crash on checkout
        $loc_stmt = $pdo->prepare("INSERT INTO location (user_id, address_line, city, postcode, is_default) VALUES (?, 'Not Specified Yet', 'Kuala Lumpur', '50000', 1)");
        $loc_stmt->execute([$new_user_id]);

        $pdo->commit();
        $success_msg = "Account created successfully! You can now log in.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_msg = "Registration failed. Email might already exist.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>ShopEasy - Register</title>
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
  .msg { padding: 10px; border-radius: 6px; font-size: 13px; margin-bottom: 14px; text-align: center; }
  .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
  .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
  .switch-link { display: block; text-align: center; font-size: 13px; color: #185FA5; text-decoration: none; margin-top: 15px; }
</style>
</head>
<body>
<div class="login-box">
  <div class="brand">Create Account</div>
  
  <?php if(isset($success_msg)) echo "<div class='msg success'>$success_msg</div>"; ?>
  <?php if(isset($error_msg)) echo "<div class='msg error'>$error_msg</div>"; ?>

  <form method="POST" action="register.php">
    <div class="field">
      <label>Full Name</label>
      <input type="text" name="full_name" placeholder="John Doe" required>
    </div>
    <div class="field">
      <label>Email Address</label>
      <input type="email" name="email" placeholder="john@example.com" required>
    </div>
    <div class="field">
      <label>Phone Number</label>
      <input type="text" name="phone" placeholder="0123456789" required>
    </div>
    <div class="field">
      <label>Password</label>
      <input type="password" name="password" placeholder="••••••••" required>
    </div>
    <button type="submit" name="register" class="btn">Sign Up</button>
    <a href="login.php" class="switch-link">Already have an account? Login</a>
  </form>
</div>
</body>
</html>
