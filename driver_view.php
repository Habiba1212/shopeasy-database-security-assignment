<?php
session_start();
require 'db_connect.php';

// Strict Access Control: Only allow users with the 'driver' role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'driver') {
    header("Location: login.php");
    exit();
}

$driver_id = $_SESSION['user_id'];
$driver_name = $pdo->query("SELECT full_name FROM user WHERE user_id = {$driver_id}")->fetchColumn();

// --- HANDLE STATUS UPDATES ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['delivery_status'];
    $delivery_id = $_POST['delivery_id'];
    $order_id = $_POST['order_id'];

    // 1. Update the delivery table
    $stmt = $pdo->prepare("UPDATE delivery SET delivery_status = ? WHERE delivery_id = ?");
    $stmt->execute([$new_status, $delivery_id]);

    // 2. Sync it with the main orders table so the Customer and Admin see the update!
    $order_status = ($new_status == 'delivered') ? 'delivered' : 'shipped';
    $stmt2 = $pdo->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
    $stmt2->execute([$order_status, $order_id]);

    // Refresh to show updates
    header("Location: driver_view.php");
    exit();
}

// Fetch all active deliveries assigned specifically to this driver
$deliveries = $pdo->prepare("
    SELECT d.delivery_id, d.order_id, d.delivery_status, o.total_amount, 
           l.address_line, l.city, l.postcode, 
           u.full_name as customer_name, u.phone
    FROM delivery d
    JOIN orders o ON d.order_id = o.order_id
    JOIN location l ON d.location_id = l.location_id
    JOIN user u ON o.customer_id = u.user_id
    WHERE d.driver_id = ? AND d.delivery_status != 'delivered'
    ORDER BY d.assigned_at DESC
");
$deliveries->execute([$driver_id]);
$active_tasks = $deliveries->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>ShopEasy - Driver Portal</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Arial, sans-serif; background: #f0f2f5; }
  nav { background: #1a1a2e; padding: 16px 30px; display: flex; justify-content: space-between; align-items: center; }
  nav .brand { color: #fff; font-size: 18px; font-weight: bold; }
  nav .user-info { color: #ccc; font-size: 14px; }
  nav .user-info strong { color: #fff; }
  nav a { color: #f87171; text-decoration: none; font-size: 14px; margin-left: 20px; font-weight: bold; }
  
  .container { max-width: 800px; margin: 30px auto; padding: 0 20px; }
  .header-section { margin-bottom: 24px; }
  .header-section h1 { font-size: 22px; color: #1a1a2e; margin-bottom: 6px; }
  .header-section p { font-size: 14px; color: #666; }
  
  .task-card { background: #fff; border: 1px solid #ddd; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
  .task-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid #eee; padding-bottom: 12px; }
  .order-id { font-size: 18px; font-weight: bold; color: #185FA5; }
  
  .badge { display: inline-block; font-size: 12px; padding: 4px 10px; border-radius: 20px; font-weight: bold; }
  .status-assigned { background: #d1ecf1; color: #0c5460; }
  .status-out { background: #fff3cd; color: #856404; }
  
  .task-details { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px; font-size: 14px; color: #333; }
  .detail-group strong { display: block; font-size: 12px; color: #888; margin-bottom: 4px; text-transform: uppercase; }
  
  .action-bar { background: #f8f9fa; padding: 16px; border-radius: 8px; display: flex; align-items: center; justify-content: space-between; }
  .action-bar select { padding: 8px 12px; border-radius: 6px; border: 1px solid #ccc; font-size: 14px; }
  .btn { padding: 8px 20px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: bold; color: #fff; background: #185FA5; }
  .btn:hover { background: #0f4a8a; }
  
  .empty-state { text-align: center; padding: 50px 20px; background: #fff; border-radius: 12px; border: 1px solid #ddd; color: #888; }
</style>
</head>
<body>

<nav>
  <span class="brand">ShopEasy Driver</span>
  <div>
    <span class="user-info">Driver: <strong><?php echo htmlspecialchars($driver_name); ?></strong></span>
    <a href="login.php">Logout</a>
  </div>
</nav>

<div class="container">
  <div class="header-section">
    <h1>My Deliveries</h1>
    <p>Manage your assigned routes and update statuses in real-time.</p>
  </div>

  <?php if (count($active_tasks) > 0): ?>
      <?php foreach ($active_tasks as $task): ?>
          <?php 
            $badge_class = $task['delivery_status'] == 'assigned' ? 'status-assigned' : 'status-out';
            $display_status = str_replace('_', ' ', ucfirst($task['delivery_status']));
          ?>
          <div class="task-card">
            <div class="task-header">
              <span class="order-id">Order #<?php echo $task['order_id']; ?></span>
              <span class="badge <?php echo $badge_class; ?>"><?php echo $display_status; ?></span>
            </div>
            
            <div class="task-details">
              <div class="detail-group">
                <strong>Customer Info</strong>
                <?php echo htmlspecialchars($task['customer_name']); ?><br>
                📞 <?php echo htmlspecialchars($task['phone'] ?? 'No phone provided'); ?>
              </div>
              <div class="detail-group">
                <strong>Delivery Address</strong>
                <?php echo htmlspecialchars($task['address_line']); ?><br>
                <?php echo htmlspecialchars($task['city']) . ', ' . htmlspecialchars($task['postcode']); ?>
              </div>
            </div>

            <div class="action-bar">
              <form method="POST" action="driver_view.php" style="display: flex; gap: 10px; width: 100%;">
                <input type="hidden" name="delivery_id" value="<?php echo $task['delivery_id']; ?>">
                <input type="hidden" name="order_id" value="<?php echo $task['order_id']; ?>">
                
                <select name="delivery_status" style="flex: 1;">
                  <option value="assigned" <?php if($task['delivery_status'] == 'assigned') echo 'selected'; ?>>Assigned (At Warehouse)</option>
                  <option value="out_for_delivery" <?php if($task['delivery_status'] == 'out_for_delivery') echo 'selected'; ?>>Out for Delivery</option>
                  <option value="delivered">✅ Mark as Delivered</option>
                </select>
                
                <button type="submit" name="update_status" class="btn">Update Status</button>
              </form>
            </div>
          </div>
      <?php endforeach; ?>
  <?php else: ?>
      <div class="empty-state">
        <h2>🎉 You're all caught up!</h2>
        <p style="margin-top: 10px;">You have no active delivery assignments right now.</p>
      </div>
  <?php endif; ?>

</div>

</body>
</html>