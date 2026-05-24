<?php
session_start();

// Disable browser cache
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// ADMIN ONLY
if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    header("Location: login.php");
    exit();
}

require 'db_connect.php';
require 'audit_helper.php';
$admin_id = (int) $_SESSION['user_id'];

// ===============================
// GET ADMIN NAME SECURELY
// ===============================
$stmt = $pdo->prepare("SELECT full_name FROM user WHERE user_id = ?");
$stmt->execute([$admin_id]);
$owner_name = $stmt->fetchColumn() ?: 'Admin';

$success_msg = '';
$error_msg = '';

// ===============================
// HANDLE DRIVER ASSIGNMENT
// ===============================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_driver'])) {
    $order_id = (int) $_POST['order_id'];
    $driver_id = (int) $_POST['driver_id'];

    try {
        $pdo->beginTransaction();

        // Verify order exists and lock it
        $order_stmt = $pdo->prepare("
            SELECT order_id, location_id, order_status 
            FROM orders 
            WHERE order_id = ? AND order_status IN ('pending', 'processing') 
            FOR UPDATE
        ");
        $order_stmt->execute([$order_id]);
        $order = $order_stmt->fetch();

        if (!$order) {
            throw new Exception("Order not available for assignment.");
        }

        // Check if already assigned
        $existing_stmt = $pdo->prepare("SELECT delivery_id FROM delivery WHERE order_id = ? LIMIT 1");
        $existing_stmt->execute([$order_id]);
        if ($existing_stmt->fetch()) {
            throw new Exception("Order is already assigned to a driver.");
        }

        // Validate Driver
        $driver_stmt = $pdo->prepare("
            SELECT u.user_id, u.full_name 
            FROM user u 
            JOIN user_role ur ON u.user_id = ur.user_id 
            WHERE u.user_id = ? AND ur.role_id = 3 AND u.account_status = 'active' 
            LIMIT 1
        ");
        $driver_stmt->execute([$driver_id]);
        $driver = $driver_stmt->fetch();

        if (!$driver) {
            throw new Exception("Selected driver is invalid or inactive.");
        }

        $location_id = (int) $order['location_id'];

        // Create Delivery Record
        $insert_stmt = $pdo->prepare("
            INSERT INTO delivery (order_id, driver_id, location_id, delivery_status) 
            VALUES (?, ?, ?, 'assigned')
        ");
        $insert_stmt->execute([$order_id, $driver_id, $location_id]);

        // Update Order Status
        $update_stmt = $pdo->prepare("UPDATE orders SET order_status = 'shipped' WHERE order_id = ?");
        $update_stmt->execute([$order_id]);

        // Audit Logging
        logAudit($pdo, $admin_id, 'DRIVER_ASSIGNMENT', 'Admin assigned driver ID ' . $driver_id . ' to order ID ' . $order_id);

        $pdo->commit();

        // Using dashboard CSS classes for alerts
        $success_msg = "<div class='alert alert-success'>Order #" . $order_id . " successfully dispatched to " . htmlspecialchars($driver['full_name']) . ".</div>";

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        logAudit($pdo, $admin_id, 'DRIVER_ASSIGNMENT_FAILED', 'Admin failed to assign driver ID ' . $driver_id . ' to order ID ' . $order_id);
        
        $error_msg = "<div class='alert alert-danger'>" . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

// ==========================================
// FETCH PENDING ORDERS
// ==========================================
$orders_stmt = $pdo->query("
    SELECT o.order_id, o.total_amount, o.order_status, o.ordered_at, u.full_name AS customer_name
    FROM orders o
    JOIN user u ON o.customer_id = u.user_id
    WHERE o.order_status IN ('pending', 'processing')
    AND NOT EXISTS (
        SELECT 1 FROM delivery d WHERE d.order_id = o.order_id
    )
    ORDER BY o.ordered_at ASC
");
$pending_orders = $orders_stmt->fetchAll();

// ==========================================
// FETCH ACTIVE DRIVERS
// ==========================================
$drivers_stmt = $pdo->query("
    SELECT u.user_id, u.full_name
    FROM user u
    JOIN user_role ur ON u.user_id = ur.user_id
    WHERE ur.role_id = 3 AND u.account_status = 'active'
    ORDER BY u.full_name ASC
");
$available_drivers = $drivers_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>ShopEasy - Admin Orders</title>
<style>
  /* UI CSS from Dashboard */
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
  .card-title { font-size: 14px; font-weight: bold; color: #1a1a2e; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px solid #eee;}
  
  /* Table CSS */
  table { width: 100%; border-collapse: collapse; font-size: 13px; }
  th { background: #f5f5f5; text-align: left; padding: 8px 10px; font-size: 12px; color: #555; border-bottom: 1px solid #ddd; }
  td { padding: 8px 10px; border-bottom: 1px solid #eee; color: #333; vertical-align: middle; }
  
  /* Form & Alert Additions for the Orders Page */
  .btn { background: #185FA5; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: bold; }
  .btn:hover { background: #134b82; }
  .form-select { padding: 6px; border-radius: 4px; border: 1px solid #ccc; font-size: 12px; outline: none; margin-right: 5px; }
  .alert { padding: 10px; border-radius: 6px; margin-bottom: 14px; font-size: 13px; font-weight: bold; }
  .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
  .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
  .empty-state { text-align: center; padding: 20px; color: #777; font-size: 13px; }
</style>
</head>
<body>

<h1>ShopEasy — Admin Dashboard</h1>
<p class="subtitle">Order Management & Dispatch</p>

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
        <?php if (!empty($success_msg)) echo $success_msg; ?>
        <?php if (!empty($error_msg)) echo $error_msg; ?>

        <div class="card">
          <div class="card-title">Pending Orders (Awaiting Dispatch)</div>
          
          <?php if (empty($pending_orders)): ?>
            <div class="empty-state">No pending orders need driver assignment right now.</div>
          <?php else: ?>
            <table>
              <thead>
                <tr>
                  <th>Order ID</th>
                  <th>Customer</th>
                  <th>Date</th>
                  <th>Total Amount</th>
                  <th>Assign Courier</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($pending_orders as $order): ?>
                  <tr>
                    <td><strong>#<?= htmlspecialchars($order['order_id']) ?></strong></td>
                    <td><?= htmlspecialchars($order['customer_name']) ?></td>
                    <td><?= date('M d, Y h:i A', strtotime($order['ordered_at'])) ?></td>
                    <td>RM <?= htmlspecialchars(number_format($order['total_amount'], 2)) ?></td>
                    <td>
                      <form method="POST" action="" style="display: flex; align-items: center;">
                        <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                        <select name="driver_id" class="form-select" required>
                          <option value="" disabled selected>Select Courier...</option>
                          <?php foreach ($available_drivers as $driver): ?>
                            <option value="<?= $driver['user_id'] ?>">
                              <?= htmlspecialchars($driver['full_name']) ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                        <button type="submit" name="assign_driver" class="btn">Dispatch</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// Prevent back-button cache issues
window.addEventListener('pageshow', function(event) {
    if (event.persisted || (window.performance && window.performance.navigation.type === 2)) {
        window.location.href = 'login.php';
    }
});
</script>

</body>
</html>
