<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");
require 'db_connect.php';

// Security Check: Admin Only
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// --- FETCH LIVE DATA FROM DATABASE ---
$admin_id = (int) $_SESSION['user_id'];

// 1. Get Owner Name
$stmt = $pdo->prepare("SELECT full_name FROM user WHERE user_id = ?");
$stmt->execute([$admin_id]);
$owner_name = $stmt->fetchColumn() ?: 'Admin';

// 2. Dashboard Metrics
$orders_today = $pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(ordered_at) = CURDATE()")->fetchColumn();
$monthly_revenue = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE MONTH(ordered_at) = MONTH(CURDATE()) AND YEAR(ordered_at) = YEAR(CURDATE())")->fetchColumn() ?: 0;
$active_drivers = $pdo->query("SELECT COUNT(*) FROM user_role WHERE role_id = 3")->fetchColumn();
$total_customers = $pdo->query("SELECT COUNT(*) FROM user_role WHERE role_id = 2")->fetchColumn();

// 3. Recent Orders
$recent_orders = $pdo->query("
    SELECT o.order_id, u.full_name as customer, o.total_amount, o.order_status, 
           (SELECT SUM(quantity) FROM order_item WHERE order_id = o.order_id) as items
    FROM orders o
    JOIN user u ON o.customer_id = u.user_id
    ORDER BY o.ordered_at DESC LIMIT 5
")->fetchAll();

// 4. Stock Alerts (Products with 5 or less in inventory)
$stock_alerts = $pdo->query("
    SELECT p.product_name, i.quantity_available 
    FROM product p 
    JOIN inventory i ON p.product_id = i.product_id 
    WHERE i.quantity_available <= 5 AND p.is_active = 1
")->fetchAll();

// 5. Driver Status
$drivers = $pdo->query("
    SELECT u.full_name, 
           IFNULL((SELECT delivery_status FROM delivery WHERE driver_id = u.user_id AND delivery_status != 'delivered' LIMIT 1), 'Available') as status
    FROM user u
    JOIN user_role ur ON u.user_id = ur.user_id
    WHERE ur.role_id = 3
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>ShopEasy - Admin Dashboard</title>
<style>
  /*UI CSS */
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
  .metrics-row { display: flex; gap: 12px; margin-bottom: 14px; }
  .metric { flex: 1; background: #f0f4ff; border-radius: 8px; padding: 14px; border: 1px solid #e0e8f5; }
  .metric-label { font-size: 11px; color: #555; margin-bottom: 4px; font-weight: bold; text-transform: uppercase;}
  .metric-value { font-size: 22px; font-weight: bold; color: #1a1a2e; }
  .card { background: #fff; border: 1px solid #e0e0e0; border-radius: 10px; padding: 16px; margin-bottom: 14px; }
  .card-title { font-size: 14px; font-weight: bold; color: #1a1a2e; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px solid #eee;}
  table { width: 100%; border-collapse: collapse; font-size: 13px; }
  th { background: #f5f5f5; text-align: left; padding: 8px 10px; font-size: 12px; color: #555; border-bottom: 1px solid #ddd; }
  td { padding: 8px 10px; border-bottom: 1px solid #eee; color: #333; }
  .badge { display: inline-block; font-size: 11px; padding: 3px 9px; border-radius: 20px; font-weight: bold; }
  .badge-success { background: #d4edda; color: #155724; }
  .badge-warn { background: #fff3cd; color: #856404; }
  .badge-info { background: #d1ecf1; color: #0c5460; }
  .badge-danger { background: #f8d7da; color: #721c24; }
  .list-row { display:flex; justify-content:space-between; font-size:13px; padding:8px 0; border-bottom:1px solid #eee; }
  .list-row:last-child { border-bottom: none; }
</style>
</head>
<body>

<h1>ShopEasy — Admin Dashboard</h1>
<p class="subtitle">Live Database View</p>

<div class="screen">
  <div class="navbar">
    <span class="nav-brand">ShopEasy Admin Panel</span>
    <div class="nav-links">
      <span>Logged in as: <strong><?php echo htmlspecialchars($owner_name, ENT_QUOTES, 'UTF-8'); ?></strong></span>
      <a href="logout.php">Logout</a>
    </div>
  </div>
  <div class="content">
    <div class="sidebar-layout">
      <div class="side-menu">
  <a href="admin_dashboard.php" style="text-decoration: none;"><div class="side-item <?php echo (basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php') ? 'active' : ''; ?>">Dashboard</div></a>
  <a href="admin_orders.php" style="text-decoration: none;"><div class="side-item <?php echo (basename($_SERVER['PHP_SELF']) == 'admin_orders.php') ? 'active' : ''; ?>">Orders</div></a>
  <a href="admin_products.php" style="text-decoration: none;"><div class="side-item <?php echo (basename($_SERVER['PHP_SELF']) == 'admin_products.php') ? 'active' : ''; ?>">Products</div></a>
  <a href="admin_stock.php" style="text-decoration: none;"><div class="side-item <?php echo (basename($_SERVER['PHP_SELF']) == 'admin_stock.php') ? 'active' : ''; ?>">Stock</div></a>
  <a href="admin_drivers.php" style="text-decoration: none;"><div class="side-item <?php echo (basename($_SERVER['PHP_SELF']) == 'admin_drivers.php') ? 'active' : ''; ?>">Drivers</div></a>
  <a href="admin_customers.php" style="text-decoration: none;"><div class="side-item <?php echo (basename($_SERVER['PHP_SELF']) == 'admin_customers.php') ? 'active' : ''; ?>">Customers</div></a>
</div>
      <div>
        <div class="metrics-row">
          <div class="metric"><div class="metric-label">Orders Today</div><div class="metric-value"><?php echo $orders_today; ?></div></div>
          <div class="metric"><div class="metric-label">Monthly Revenue</div><div class="metric-value">RM <?php echo number_format($monthly_revenue, 2); ?></div></div>
          <div class="metric"><div class="metric-label">Active Drivers</div><div class="metric-value"><?php echo $active_drivers; ?></div></div>
          <div class="metric"><div class="metric-label">Total Customers</div><div class="metric-value"><?php echo $total_customers; ?></div></div>
        </div>

        <div class="card">
          <div class="card-title">Recent Orders</div>
          <table>
            <thead>
              <tr><th>Order ID</th><th>Customer</th><th>Items</th><th>Amount</th><th>Status</th></tr>
            </thead>
            <tbody>
              <?php
              if (count($recent_orders) > 0) {
                  foreach ($recent_orders as $order) {
                      // Color code the badges based on status
                      $badge = 'badge-info';
                      if ($order['order_status'] == 'processing') $badge = 'badge-warn';
                      if ($order['order_status'] == 'delivered') $badge = 'badge-success';
                      if ($order['order_status'] == 'cancelled') $badge = 'badge-danger';

                      echo "<tr>
                              <td>#" . (int)$order['order_id'] . "</td>
                              <td>" . htmlspecialchars($order['customer']) . "</td>
                              <td>" . (int)$order['items'] . " items</td>
                              <td>RM " . number_format($order['total_amount'], 2) . "</td>
                              <td><span class='badge {$badge}'>" . htmlspecialchars(ucfirst($order['order_status']), ENT_QUOTES, 'UTF-8') . "</span></td>
                            </tr>";
                  }
              } else {
                  echo "<tr><td colspan='5' style='text-align:center;'>No orders found.</td></tr>";
              }
              ?>
            </tbody>
          </table>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
          <div class="card">
            <div class="card-title">Stock Alerts</div>
            <?php
            if (count($stock_alerts) > 0) {
                foreach ($stock_alerts as $alert) {
                    $badge = $alert['quantity_available'] <= 0 ? 'badge-danger' : 'badge-warn';
                    echo "<div class='list-row'>
                            <span>" . htmlspecialchars($alert['product_name']) . "</span>
                            <span class='badge {$badge}'>" . (int)$alert['quantity_available'] . " left</span>
                          </div>";
                }
            } else {
                echo "<div class='list-row'>All stock levels are healthy!</div>";
            }
            ?>
          </div>

          <div class="card">
            <div class="card-title">Driver Status</div>
            <?php
            foreach ($drivers as $driver) {
                $badge = $driver['status'] == 'Available' ? 'badge-success' : 'badge-warn';
                echo "<div class='list-row'>
                        <span>" . htmlspecialchars($driver['full_name']) . "</span>
                        <span class='badge {$badge}'>" . htmlspecialchars($driver['status'], ENT_QUOTES, 'UTF-8') . "</span>
                      </div>";
            }
            ?>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

</body>
</html>
