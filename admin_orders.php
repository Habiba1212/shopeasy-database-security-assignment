<?php
session_start();
require 'db_connect.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// 1. Get Owner Name
$stmt = $pdo->prepare("SELECT full_name FROM user WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$owner_name = $stmt->fetchColumn();

// 2. Fetch ALL Orders
$all_orders = $pdo->query("
    SELECT o.order_id, u.full_name as customer, o.total_amount, o.order_status, o.ordered_at,
           (SELECT SUM(quantity) FROM order_item WHERE order_id = o.order_id) as items
    FROM orders o
    JOIN user u ON o.customer_id = u.user_id
    ORDER BY o.ordered_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>ShopEasy - All Orders</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 30px; }
  h1 { text-align: center; font-size: 20px; color: #1a1a2e; margin-bottom: 8px; }
  .subtitle { text-align: center; font-size: 13px; color: #666; margin-bottom: 30px; }
  .screen { background: #fff; border: 1px solid #ddd; border-radius: 12px; overflow: hidden; }
  .navbar { display: flex; justify-content: space-between; align-items: center; padding: 12px 20px; background: #1a1a2e; color: #fff; }
  .nav-brand { font-size: 16px; font-weight: bold; color: #fff; }
  .nav-links { display: flex; gap: 18px; font-size: 13px; color: #ccc; }
  .nav-links a { color: #f87171; text-decoration: none; font-weight: bold;}
  .content { padding: 20px; }
  .sidebar-layout { display: grid; grid-template-columns: 160px 1fr; gap: 14px; }
  .side-menu { background: #1a1a2e; border-radius: 10px; padding: 12px; height: fit-content; }
  .side-item { padding: 8px 12px; font-size: 13px; border-radius: 6px; margin-bottom: 3px; cursor: pointer; color: #aaa; }
  .side-item.active { background: #185FA5; color: #fff; font-weight: bold; }
  .card { background: #fff; border: 1px solid #e0e0e0; border-radius: 10px; padding: 16px; margin-bottom: 14px; }
  .card-title { font-size: 16px; font-weight: bold; color: #1a1a2e; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px solid #eee;}
  table { width: 100%; border-collapse: collapse; font-size: 13px; }
  th { background: #f5f5f5; text-align: left; padding: 10px; font-size: 12px; color: #555; border-bottom: 1px solid #ddd; }
  td { padding: 10px; border-bottom: 1px solid #eee; color: #333; }
  .badge { display: inline-block; font-size: 11px; padding: 4px 10px; border-radius: 20px; font-weight: bold; }
  .badge-success { background: #d4edda; color: #155724; }
  .badge-warn { background: #fff3cd; color: #856404; }
  .badge-info { background: #d1ecf1; color: #0c5460; }
  .badge-danger { background: #f8d7da; color: #721c24; }
</style>
</head>
<body>

<h1>ShopEasy — Admin Panel</h1>
<p class="subtitle">Order Management</p>

<div class="screen">
  <div class="navbar">
    <span class="nav-brand">ShopEasy Admin Panel</span>
    <div class="nav-links">
      <span>Logged in as: <strong><?php echo htmlspecialchars($owner_name); ?></strong></span>
      <a href="login.php">Logout</a>
    </div>
  </div>
  <div class="content">
    <div class="sidebar-layout">
      
      <div class="side-menu">
        <a href="admin_dashboard.php" style="text-decoration: none;">
            <div class="side-item">Dashboard</div>
        </a>
        <a href="admin_orders.php" style="text-decoration: none;">
            <div class="side-item active">Orders</div> 
        </a>
        <a href="admin_products.php" style="text-decoration: none;">
            <div class="side-item">Products</div>
        </a>
        <a href="admin_stock.php" style="text-decoration: none;">
            <div class="side-item">Stock</div>
        </a>
      </div>
      
      <div>
        <div class="card">
          <div class="card-title">All Customer Orders</div>
          <table>
            <thead>
              <tr><th>Order ID</th><th>Date & Time</th><th>Customer</th><th>Items</th><th>Total Amount</th><th>Status</th></tr>
            </thead>
            <tbody>
              <?php
              if (count($all_orders) > 0) {
                  foreach ($all_orders as $order) {
                      $badge = 'badge-info';
                      if ($order['order_status'] == 'processing') $badge = 'badge-warn';
                      if ($order['order_status'] == 'delivered') $badge = 'badge-success';
                      if ($order['order_status'] == 'cancelled') $badge = 'badge-danger';

                      $date = date("d M Y, h:i A", strtotime($order['ordered_at']));

                      echo "<tr>
                              <td><strong>#{$order['order_id']}</strong></td>
                              <td>{$date}</td>
                              <td>" . htmlspecialchars($order['customer']) . "</td>
                              <td>{$order['items']} items</td>
                              <td>RM " . number_format($order['total_amount'], 2) . "</td>
                              <td><span class='badge {$badge}'>" . ucfirst($order['order_status']) . "</span></td>
                            </tr>";
                  }
              } else {
                  echo "<tr><td colspan='6' style='text-align:center;'>No orders found.</td></tr>";
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>

</body>
</html>