<?php
session_start();
require 'db_connect.php';

// If they aren't logged in, send them to the login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>ShopEasy - My Orders</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Arial, sans-serif; background: #f0f2f5; }
  nav { background: #1a1a2e; padding: 12px 30px; display: flex; justify-content: space-between; align-items: center; }
  nav .brand { color: #fff; font-size: 18px; font-weight: bold; }
  nav .links { display: flex; gap: 20px; align-items: center; }
  nav .links a { color: #ccc; text-decoration: none; font-size: 14px; }
  nav .links a:hover { color: #fff; }
  .container { max-width: 800px; margin: 40px auto; padding: 24px; background: #fff; border-radius: 10px; border: 1px solid #ddd; }
  .section-title { font-size: 20px; font-weight: bold; color: #1a1a2e; margin-bottom: 20px; border-bottom: 2px solid #eee; padding-bottom: 10px;}
  table { width: 100%; border-collapse: collapse; font-size: 14px; }
  th { background: #f5f5f5; text-align: left; padding: 12px; color: #555; border-bottom: 1px solid #ddd; }
  td { padding: 12px; border-bottom: 1px solid #eee; color: #333; }
  .badge { display: inline-block; font-size: 12px; padding: 4px 10px; border-radius: 20px; font-weight: bold; }
  .status-pending { background: #fff3cd; color: #856404; }
  .status-processing { background: #cce5ff; color: #004085; }
  .status-delivered { background: #d4edda; color: #155724; }
  .empty-state { text-align: center; color: #888; padding: 40px 0; font-size: 15px; }
</style>
</head>
<body>
<nav>
  <span class="brand">ShopEasy</span>
  <div class="links">
    <a href="shop.php">Products</a>
    <a href="orders.php" style="color:#fff; font-weight:bold;">My Orders</a>
    <a href="login.php">Logout</a>
  </div>
</nav>

<div class="container">
  <div class="section-title">Order History</div>
  
  <?php
  // Fetch all orders for this specific customer
  $stmt = $pdo->prepare("SELECT order_id, total_amount, order_status, ordered_at FROM orders WHERE customer_id = ? ORDER BY ordered_at DESC");
  $stmt->execute([$user_id]);
  $orders = $stmt->fetchAll();

  if (count($orders) > 0) {
      echo '<table>';
      echo '<tr><th>Order ID</th><th>Date</th><th>Total Amount</th><th>Status</th></tr>';
      
      foreach ($orders as $order) {
          // Format the status badge color
          $statusClass = 'status-pending';
          if ($order['order_status'] == 'processing') $statusClass = 'status-processing';
          if ($order['order_status'] == 'delivered') $statusClass = 'status-delivered';

          $date = date("d M Y, h:i A", strtotime($order['ordered_at']));
          
          echo '<tr>';
          echo '<td><strong>#' . $order['order_id'] . '</strong></td>';
          echo '<td>' . $date . '</td>';
          echo '<td>RM ' . number_format($order['total_amount'], 2) . '</td>';
          echo '<td><span class="badge ' . $statusClass . '">' . ucfirst($order['order_status']) . '</span></td>';
          echo '</tr>';
      }
      echo '</table>';
  } else {
      echo '<div class="empty-state">You haven\'t placed any orders yet!</div>';
  }
  ?>
</div>
</body>
</html>