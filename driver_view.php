
<?php

session_start();

if (
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'driver'
) {

    header("Location: login.php");

    exit();
}

?>
    <?php
session_start();
require 'db_connect.php';

// Strict Access Control: Only allow users with the 'driver' role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'driver') {
    header("Location: login.php");
    exit();
}

$driver_id = $_SESSION['user_id'];

// Fetch Driver Name from database
$stmt_name = $pdo->prepare("SELECT full_name FROM user WHERE user_id = ?");
$stmt_name->execute([$driver_id]);
$driver_name = $stmt_name->fetchColumn();

// --- HANDLE DELIVERY STATE CHANGES ---

// 1. Driver starts the route
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['start_delivery'])) {
    $delivery_id = $_POST['delivery_id'];
    
    $stmt = $pdo->prepare("UPDATE delivery SET delivery_status = 'out_for_delivery' WHERE delivery_id = ?");
    $stmt->execute([$delivery_id]);
    
    header("Location: driver_view.php");
    exit();
}

// 2. Driver finishes the delivery
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_delivery'])) {
    $delivery_id = $_POST['delivery_id'];
    $order_id = $_POST['order_id'];

    try {
        $pdo->beginTransaction();

        $stmt1 = $pdo->prepare("UPDATE delivery SET delivery_status = 'delivered', delivered_at = CURRENT_TIMESTAMP WHERE delivery_id = ?");
        $stmt1->execute([$delivery_id]);

        $stmt2 = $pdo->prepare("UPDATE orders SET order_status = 'delivered' WHERE order_id = ?");
        $stmt2->execute([$order_id]);

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
    }

    header("Location: driver_view.php");
    exit();
}

// Fetch active assignments for this specific driver
$deliveries_stmt = $pdo->prepare("
    SELECT d.delivery_id, d.order_id, d.delivery_status,
           l.address_line, l.city, l.postcode, 
           u.full_name as customer_name, u.phone,
           GROUP_CONCAT(CONCAT(p.product_name, ' × ', oi.quantity) SEPARATOR ', ') as item_details
    FROM delivery d
    JOIN orders o ON d.order_id = o.order_id
    JOIN location l ON d.location_id = l.location_id
    JOIN user u ON o.customer_id = u.user_id
    JOIN order_item oi ON o.order_id = oi.order_id
    JOIN product p ON oi.product_id = p.product_id
    WHERE d.driver_id = ? AND d.delivery_status != 'delivered'
    GROUP BY d.delivery_id
    ORDER BY d.assigned_at DESC
");
$deliveries_stmt->execute([$driver_id]);
$assigned_deliveries = $deliveries_stmt->fetchAll();
$delivery_count = count($assigned_deliveries);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>ShopEasy - Driver Delivery View</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 30px; }
  h1 { text-align: center; font-size: 20px; color: #1a1a2e; margin-bottom: 8px; }
  .subtitle { text-align: center; font-size: 13px; color: #666; margin-bottom: 30px; }
  .screen { background: #fff; border: 1px solid #ddd; border-radius: 12px; overflow: hidden; }
  .navbar { display: flex; justify-content: space-between; align-items: center; padding: 12px 20px; background: #1a1a2e; color: #fff; }
  .nav-brand { font-size: 16px; font-weight: bold; color: #fff; }
  .logout-btn { color: #f87171; text-decoration: none; font-weight: bold; font-size: 13px; }
  .content { padding: 20px; }
  
  .delivery-card { background: #fff; border: 1px solid #ddd; border-radius: 10px; padding: 14px; margin-bottom: 12px; text-align: left; }
  .delivery-card.active-driving { border: 2px solid #185FA5; box-shadow: 0 4px 8px rgba(24,95,165,0.15); }
  .delivery-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
  .delivery-addr { font-size: 13px; color: #555; margin-bottom: 10px; }
  .map-box { height: 75px; background: #e8f5e9; border: 1px dashed #81c784; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 12px; color: #388e3c; margin-bottom: 10px; }
  
  .action-row { display: flex; gap: 8px; width: 100%; }
  .btn-start { flex: 1; padding: 10px; font-size: 13px; font-weight: bold; border-radius: 6px; background: #185FA5; color: #fff; border: none; cursor: pointer; }
  .btn-confirm { flex: 1; padding: 10px; font-size: 13px; font-weight: bold; border-radius: 6px; background: #22c55e; color: #fff; border: none; cursor: pointer; }
  
  .badge { display: inline-block; font-size: 11px; padding: 3px 9px; border-radius: 20px; font-weight: bold; }
  .badge-warn { background: #fff3cd; color: #856404; }
  .badge-info { background: #d1ecf1; color: #0c5460; }
  .note { font-size: 11px; color: #888; text-align: center; margin-top: 30px; }
</style>
</head>
<body>

<h1>ShopEasy — Single Owner Online Store</h1>
<p class="subtitle">Live Database View for CCS6344 Assignment 1 | Database &amp; Cloud Security</p>

<div style="max-width: 420px; margin: 0 auto;">
  <div class="screen">
    <div class="navbar">
      <span class="nav-brand">My Deliveries</span>
      <a href="logout.php" class="logout-btn">Logout</a>
    </div>
    
    <div class="content">
      <div style="font-size:13px; color:#555; margin-bottom:14px; text-align: left;">
        Hi <?php echo htmlspecialchars($driver_name); ?> — <strong><?php echo $delivery_count; ?> deliveries</strong> left today
      </div>

      <?php if ($delivery_count > 0): ?>
          <?php foreach ($assigned_deliveries as $index => $task): ?>
              <?php 
                $is_out_for_delivery = ($task['delivery_status'] === 'out_for_delivery');
                $badge_class = $is_out_for_delivery ? "badge-warn" : "badge-info";
                $status_label = $is_out_for_delivery ? "Driving to Location" : "Pending Pickup";
                
                // Highlight the card deeply if they are currently driving
                $card_style_class = $is_out_for_delivery ? "delivery-card active-driving" : "delivery-card";
                $fade_style = ($index !== 0 && !$is_out_for_delivery) ? "style='opacity:0.65;'" : "";
              ?>
              
              <div class="<?php echo $card_style_class; ?>" <?php echo $fade_style; ?>>
                <div class="delivery-header">
                  <span style="font-size:14px; font-weight:bold; color:#1a1a2e;">Order #<?php echo $task['order_id']; ?></span>
                  <span class="badge <?php echo $badge_class; ?>"><?php echo $status_label; ?></span>
                </div>
                
                <div class="delivery-addr">📍 <?php echo htmlspecialchars($task['address_line'] . ', ' . $task['city'] . ', ' . $task['postcode']); ?></div>
                
                <?php if ($index === 0 || $is_out_for_delivery): ?>
                    <div class="map-box">🗺 Map — GPS route to destination</div>
                    <div style="font-size:12px; color:#555; margin-bottom:10px;">
                      <strong>Items:</strong> <?php echo htmlspecialchars($task['item_details']); ?> <br>
                      <strong>Customer:</strong> <?php echo htmlspecialchars($task['customer_name']); ?> (<?php echo htmlspecialchars($task['phone'] ?? 'N/A'); ?>)
                    </div>
                    
                    <form method="POST" action="driver_view.php" style="width: 100%;">
                      <input type="hidden" name="delivery_id" value="<?php echo $task['delivery_id']; ?>">
                      <input type="hidden" name="order_id" value="<?php echo $task['order_id']; ?>">
                      
                      <div class="action-row">
                        <?php if ($task['delivery_status'] === 'assigned'): ?>
                            <button type="submit" name="start_delivery" class="btn-start">🚀 Start Route</button>
                        <?php else: ?>
                            <button type="submit" name="confirm_delivery" class="btn-confirm">✔ Mark as Delivered</button>
                        <?php endif; ?>
                      </div>
                    </form>
                <?php else: ?>
                    <div style="font-size:12px; color:#555;">
                      <strong>Items:</strong> <?php echo htmlspecialchars($task['item_details']); ?>
                    </div>
                <?php endif; ?>
              </div>
          <?php endforeach; ?>
      <?php else: ?>
          <div class="delivery-card" style="text-align: center; padding: 30px 10px; color: #888;">
            You have completed all your deliveries!
          </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<p class="note">CCS6344 T2610 — Database &amp; Cloud Security | Assignment 1</p>
</body>
</html>
