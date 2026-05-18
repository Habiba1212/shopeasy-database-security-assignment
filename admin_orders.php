<?php
session_start();
require 'db_connect.php';

// Security Check: Admin Only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$owner_name = $_SESSION['full_name'] ?? 'Admin';

// --- HANDLE DRIVER ASSIGNMENT ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_driver'])) {
    $order_id = $_POST['order_id'];
    $driver_id = $_POST['driver_id'];

    try {
        $pdo->beginTransaction();

        // 1. Get the location_id from the original order
        $loc_stmt = $pdo->prepare("SELECT location_id FROM orders WHERE order_id = ?");
        $loc_stmt->execute([$order_id]);
        $location_id = $loc_stmt->fetchColumn();

        // 2. Create the delivery record bridging the order, driver, and location
        $stmt = $pdo->prepare("INSERT INTO delivery (order_id, driver_id, location_id, delivery_status) VALUES (?, ?, ?, 'assigned')");
        $stmt->execute([$order_id, $driver_id, $location_id]);

        // 3. Update the main order status so the customer knows it is moving
        $update_stmt = $pdo->prepare("UPDATE orders SET order_status = 'shipped' WHERE order_id = ?");
        $update_stmt->execute([$order_id]);

        $pdo->commit();
        $success_msg = "Order #$order_id successfully dispatched to Driver #$driver_id!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_msg = "Assignment failed. Order might already be assigned.";
    }
}

// Fetch all pending/processing orders to display
$orders_stmt = $pdo->query("
    SELECT o.order_id, o.total_amount, o.order_status, o.ordered_at, u.full_name as customer_name
    FROM orders o
    JOIN user u ON o.customer_id = u.user_id
    WHERE o.order_status IN ('pending', 'processing')
    ORDER BY o.ordered_at ASC
");
$pending_orders = $orders_stmt->fetchAll();

// Fetch all drivers for the dropdown menu
$drivers_stmt = $pdo->query("
    SELECT u.user_id, u.full_name 
    FROM user u 
    JOIN user_role ur ON u.user_id = ur.user_id 
    WHERE ur.role_id = 3 AND u.account_status = 'active'
");
$available_drivers = $drivers_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>ShopEasy - Manage Orders</title>
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
  .side-item { padding: 8px 12px; font-size: 13px; border-radius: 6px; margin-bottom: 3px; cursor: pointer; color: #aaa; text-decoration: none; display: block;}
  .side-item.active { background: #185FA5; color: #fff; font-weight: bold; }
  .card { background: #fff; border: 1px solid #e0e0e0; border-radius: 10px; padding: 16px; margin-bottom: 14px; }
  .card-title { font-size: 16px; font-weight: bold; color: #1a1a2e; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px solid #eee;}
  table { width: 100%; border-collapse: collapse; font-size: 13px; }
  th { background: #f5f5f5; text-align: left; padding: 10px; font-size: 12px; color: #555; border-bottom: 1px solid #ddd; }
  td { padding: 10px; border-bottom: 1px solid #eee; color: #333; vertical-align: middle; }
  
  .badge { display: inline-block; font-size: 11px; padding: 4px 10px; border-radius: 20px; font-weight: bold; }
  .badge-warn { background: #fff3cd; color: #856404; }
  
  .dispatch-form { display: flex; gap: 8px; align-items: center; }
  .dispatch-select { padding: 6px; border: 1px solid #ccc; border-radius: 4px; font-size: 12px; }
  .btn-assign { background: #185FA5; color: #fff; border: none; padding: 6px 12px; border-radius: 4px; font-size: 12px; cursor: pointer; font-weight: bold; }
  .msg-success { background: #d4edda; color: #155724; padding: 10px; border-radius: 6px; margin-bottom: 14px; font-size: 13px; border: 1px solid #c3e6cb;}
</style>
</head>
<body>
<h1>ShopEasy — Admin Panel</h1>
<p class="subtitle">Order Dispatch Management</p>
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
        <a href="admin_dashboard.php"><div class="side-item">Dashboard</div></a>
        <a href="admin_orders.php"><div class="side-item active">Orders</div></a>
        <a href="#"><div class="side-item">Products</div></a>
        <a href="#"><div class="side-item">Stock</div></a>
        <a href="admin_drivers.php"><div class="side-item">Drivers</div></a>
        <a href="admin_customers.php"><div class="side-item">Customers</div></a>
      </div>
      <div>
        <div class="card">
          <div class="card-title">Pending Orders Requiring Dispatch</div>
          
          <?php if(isset($success_msg)) echo "<div class='msg-success'>$success_msg</div>"; ?>

          <table>
            <thead>
              <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Status</th>
                <th>Assign Driver</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($pending_orders) > 0): ?>
                  <?php foreach ($pending_orders as $order): ?>
                      <tr>
                        <td><strong>#<?php echo $order['order_id']; ?></strong><br><span style="font-size:11px; color:#888;"><?php echo date('M d, H:i', strtotime($order['ordered_at'])); ?></span></td>
                        <td><?php echo htmlspecialchars($order['customer_name']); ?><br><span style="font-size:11px; color:#185FA5;">RM <?php echo number_format($order['total_amount'], 2); ?></span></td>
                        <td><span class='badge badge-warn'><?php echo ucfirst($order['order_status']); ?></span></td>
                        <td>
                          <form method="POST" action="admin_orders.php" class="dispatch-form">
                            <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                            <select name="driver_id" class="dispatch-select" required>
                                <option value="" disabled selected>Select a driver...</option>
                                <?php foreach ($available_drivers as $driver): ?>
                                    <option value="<?php echo $driver['user_id']; ?>">
                                        <?php echo htmlspecialchars($driver['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="assign_driver" class="btn-assign">Dispatch</button>
                          </form>
                        </td>
                      </tr>
                  <?php endforeach; ?>
              <?php else: ?>
                  <tr><td colspan='4' style='text-align:center; padding: 20px;'>No pending orders at the moment.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
