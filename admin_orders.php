<?php
session_start();
require 'db_connect.php';

// Security Check: Admin Only
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$admin_id = (int) $_SESSION['user_id'];

// Get Owner Name securely
$stmt = $pdo->prepare("SELECT full_name FROM user WHERE user_id = ?");
$stmt->execute([$admin_id]);
$owner_name = $stmt->fetchColumn() ?: 'Admin';

$success_msg = '';
$error_msg = '';

// --- HANDLE DRIVER ASSIGNMENT ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_driver'])) {

    $order_id = (int) $_POST['order_id'];
    $driver_id = (int) $_POST['driver_id'];

    try {
        $pdo->beginTransaction();

        // 1. Lock and verify that the order exists and is still dispatchable
        $order_stmt = $pdo->prepare("
            SELECT order_id, location_id, order_status
            FROM orders
            WHERE order_id = ?
            AND order_status IN ('pending', 'processing')
            FOR UPDATE
        ");
        $order_stmt->execute([$order_id]);
        $order = $order_stmt->fetch();

        if (!$order) {
            throw new Exception("Order is not available for dispatch.");
        }

        // 2. Make sure the order is not already assigned to a delivery
        $existing_stmt = $pdo->prepare("
            SELECT delivery_id
            FROM delivery
            WHERE order_id = ?
            LIMIT 1
        ");
        $existing_stmt->execute([$order_id]);

        if ($existing_stmt->fetch()) {
            throw new Exception("Order already has a delivery assignment.");
        }

        // 3. Verify selected driver exists, is active, and has driver role
        $driver_stmt = $pdo->prepare("
            SELECT u.user_id, u.full_name
            FROM user u
            JOIN user_role ur ON u.user_id = ur.user_id
            WHERE u.user_id = ?
            AND ur.role_id = 3
            AND u.account_status = 'active'
            LIMIT 1
        ");
        $driver_stmt->execute([$driver_id]);
        $driver = $driver_stmt->fetch();

        if (!$driver) {
            throw new Exception("Selected driver is invalid or inactive.");
        }

        $location_id = (int) $order['location_id'];

        // 4. Create delivery record
        $stmt = $pdo->prepare("
            INSERT INTO delivery
            (order_id, driver_id, location_id, delivery_status)
            VALUES (?, ?, ?, 'assigned')
        ");
        $stmt->execute([
            $order_id,
            $driver_id,
            $location_id
        ]);

        // 5. Update order status
        $update_stmt = $pdo->prepare("
            UPDATE orders
            SET order_status = 'shipped'
            WHERE order_id = ?
            AND order_status IN ('pending', 'processing')
        ");
        $update_stmt->execute([$order_id]);

        // 6. Audit logging
        logAudit(
            $pdo,
            $admin_id,
            'DRIVER_ASSIGNMENT',
            'Admin assigned driver ID ' . $driver_id . ' to order ID ' . $order_id
        );

        $pdo->commit();

        $success_msg = "<div class='msg-success'>Order #" . $order_id . " successfully dispatched to Driver #" . $driver_id . ".</div>";

    } catch (Exception $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        logAudit(
            $pdo,
            $admin_id,
            'DRIVER_ASSIGNMENT_FAILED',
            'Admin failed to assign driver ID ' . $driver_id . ' to order ID ' . $order_id
        );

        $error_msg = "<div class='error-msg'>Assignment failed. The order may already be assigned, unavailable, or the driver may be invalid.</div>";
    }
}

// Fetch all pending/processing orders that do not already have a delivery
$orders_stmt = $pdo->query("
    SELECT 
        o.order_id,
        o.total_amount,
        o.order_status,
        o.ordered_at,
        u.full_name AS customer_name
    FROM orders o
    JOIN user u ON o.customer_id = u.user_id
    WHERE o.order_status IN ('pending', 'processing')
    AND NOT EXISTS (
        SELECT 1
        FROM delivery d
        WHERE d.order_id = o.order_id
    )
    ORDER BY o.ordered_at ASC
");
$pending_orders = $orders_stmt->fetchAll();

// Fetch active drivers for dropdown
$drivers_stmt = $pdo->query("
    SELECT u.user_id, u.full_name
    FROM user u
    JOIN user_role ur ON u.user_id = ur.user_id
    WHERE ur.role_id = 3
    AND u.account_status = 'active'
    ORDER BY u.full_name ASC
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
  
  .dispatch-form { display: flex; gap: 8px; align-items: center; margin: 0; }
  .dispatch-select { padding: 6px; border: 1px solid #ccc; border-radius: 4px; font-size: 12px; }
  .btn-assign { background: #185FA5; color: #fff; border: none; padding: 6px 12px; border-radius: 4px; font-size: 12px; cursor: pointer; font-weight: bold; }
  .msg-success { background: #d4edda; color: #155724; padding: 10px; border-radius: 6px; margin-bottom: 14px; font-size: 13px; border: 1px solid #c3e6cb;}
  .error-msg { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 6px; margin-bottom: 14px; font-size: 13px; border: 1px solid #f5c6cb;}
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
          <div class="card-title">Pending Orders Requiring Dispatch</div>
          
          <?php echo $success_msg; ?>
          <?php echo $error_msg; ?>

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
                    <?php
                        $order_id = (int) $order['order_id'];
                        $customer_name = htmlspecialchars($order['customer_name'], ENT_QUOTES, 'UTF-8');
                        $order_status = htmlspecialchars(ucfirst($order['order_status']), ENT_QUOTES, 'UTF-8');
                        $ordered_at = date('M d, H:i', strtotime($order['ordered_at']));
                        $total_amount = number_format((float) $order['total_amount'], 2);
                    ?>
                    <tr>
                        <td>
                            <strong>#<?php echo $order_id; ?></strong><br>
                            <span style="font-size:11px; color:#888;">
                                <?php echo $ordered_at; ?>
                            </span>
                        </td>

                        <td>
                            <?php echo $customer_name; ?><br>
                            <span style="font-size:11px; color:#185FA5; font-weight: bold;">
                                RM <?php echo $total_amount; ?>
                            </span>
                        </td>

                        <td>
                            <span class="badge badge-warn">
                                <?php echo $order_status; ?>
                            </span>
                        </td>

                        <td>
                            <form method="POST" action="admin_orders.php" class="dispatch-form">
                                <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">

                                <select name="driver_id" class="dispatch-select" required>
                                    <option value="" disabled selected>Select a driver...</option>

                                    <?php foreach ($available_drivers as $driver): ?>
                                        <?php
                                            $driver_id = (int) $driver['user_id'];
                                            $driver_name = htmlspecialchars($driver['full_name'], ENT_QUOTES, 'UTF-8');
                                        ?>
                                        <option value="<?php echo $driver_id; ?>">
                                            <?php echo $driver_name; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <button type="submit" name="assign_driver" class="btn-assign">
                                    Dispatch
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
              <?php else: ?>
                  <tr><td colspan='4' style='text-align:center; padding: 30px; color: #888;'>No pending orders at the moment.</td></tr>
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
