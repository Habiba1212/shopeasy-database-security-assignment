<?php

session_start();

if (
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {

    header("Location: login.php");

    exit();
}

?>
    <?php

session_start();
require 'db_connect.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$owner_name = $pdo->query("SELECT full_name FROM user WHERE user_id = {$_SESSION['user_id']}")->fetchColumn();

// --- HANDLE STOCK UPDATES ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_stock'])) {
    $stmt = $pdo->prepare("UPDATE inventory SET quantity_available = ? WHERE product_id = ?");
    $stmt->execute([$_POST['new_qty'], $_POST['product_id']]);
    header("Location: admin_stock.php");
    exit();
}

// Fetch Inventory joined with Product details
$inventory = $pdo->query("
    SELECT p.product_id, p.product_name, i.quantity_available, i.last_updated 
    FROM product p 
    JOIN inventory i ON p.product_id = i.product_id 
    ORDER BY i.quantity_available ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>ShopEasy - Inventory</title>
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
  .badge-danger { background: #f8d7da; color: #721c24; }
  .qty-input { width: 60px; padding: 4px; text-align: center; border: 1px solid #ccc; border-radius: 4px; }
  .btn-update { padding: 5px 10px; border: none; background: #185FA5; color: #fff; border-radius: 4px; cursor: pointer; font-size: 11px; font-weight: bold;}
</style>
</head>
<body>
<h1>ShopEasy — Admin Panel</h1>
<p class="subtitle">Inventory Management</p>
<div class="screen">
  <div class="navbar">
    <span class="nav-brand">ShopEasy Admin Panel</span>
    <div class="nav-links">
      <span>Logged in as: <strong><?php echo htmlspecialchars($owner_name); ?></strong></span>
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
        <div class="card">
          <div class="card-title">Live Inventory Levels</div>
          <table>
            <thead>
              <tr><th>Product Name</th><th>Status</th><th>Last Updated</th><th>Update Stock</th></tr>
            </thead>
            <tbody>
              <?php
              foreach ($inventory as $i) {
                  $badge = $i['quantity_available'] > 0 ? 'badge-success' : 'badge-danger';
                  $status_text = $i['quantity_available'] > 0 ? "In Stock ({$i['quantity_available']})" : "Out of Stock";
                  $date = date("d M Y, h:i A", strtotime($i['last_updated']));

                  echo "<tr>
                          <td><strong>" . htmlspecialchars($i['product_name']) . "</strong></td>
                          <td><span class='badge {$badge}'>{$status_text}</span></td>
                          <td>{$date}</td>
                          <td>
                            <form method='POST' action='admin_stock.php' style='display:flex; gap:8px;'>
                              <input type='hidden' name='product_id' value='{$i['product_id']}'>
                              <input type='number' name='new_qty' class='qty-input' value='{$i['quantity_available']}' min='0'>
                              <button type='submit' name='update_stock' class='btn-update'>Update</button>
                            </form>
                          </td>
                        </tr>";
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
