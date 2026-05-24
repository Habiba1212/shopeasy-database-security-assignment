<?php
session_start();

// Disable browser cache
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// DRIVER ONLY CHECK
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require 'db_connect.php';
require_once 'audit_helper.php';
$driver_id = (int) $_SESSION['user_id'];

// Check if a delivery ID was passed via POST
if (!isset($_POST['delivery_id'])) {
    header("Location: driver_view.php");
    exit();
}

$delivery_id = (int) $_POST['delivery_id'];

// ==========================================
// HANDLE "CONFIRM DROP-OFF" (COMPLETE)
// ==========================================
if (isset($_POST['action']) && $_POST['action'] === 'complete') {
    try {
        $pdo->beginTransaction();

        // 1. Update delivery status to 'delivered'
        $stmt1 = $pdo->prepare("UPDATE delivery SET delivery_status = 'delivered' WHERE delivery_id = ? AND driver_id = ?");
        $stmt1->execute([$delivery_id, $driver_id]);

        // 2. Get the order_id associated with this delivery
        $stmt2 = $pdo->prepare("SELECT order_id FROM delivery WHERE delivery_id = ?");
        $stmt2->execute([$delivery_id]);
        $order_id = $stmt2->fetchColumn();

        // 3. Update the main orders table to 'delivered'
        if ($order_id) {
            $stmt3 = $pdo->prepare("UPDATE orders SET order_status = 'delivered' WHERE order_id = ?");
            $stmt3->execute([$order_id]);
        }

        $pdo->commit();
        
        logAudit($pdo, $driver_id, 'ORDER_DELIVERED', 'Driver confirmed drop-off for Order #' . $order_id);
        // Redirect back to driver dashboard
        header("Location: driver_view.php");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error completing delivery: " . $e->getMessage());
    }
}

// ==========================================
// HANDLE "START ROUTE" (INITIATE)
// ==========================================
// Update status to 'en_route' 
$update_stmt = $pdo->prepare("UPDATE delivery SET delivery_status = 'en_route' WHERE delivery_id = ? AND driver_id = ? AND delivery_status = 'assigned'");
$update_stmt->execute([$delivery_id, $driver_id]);

if ($update_stmt->rowCount() > 0) {
    // rowCount() > 0 means the status just successfully changed from 'assigned' to 'en_route'
    logAudit($pdo, $driver_id, 'ROUTE_STARTED', 'Driver started route for Delivery #' . $delivery_id);
}
// ==========================================
// FETCH DELIVERY DETAILS FOR THE MAP
// ==========================================
$query = "
    SELECT 
        d.delivery_id, o.order_id, 
        c.full_name AS customer_name,
        l.address_line, l.city, l.postcode,
        (
            SELECT GROUP_CONCAT(CONCAT(p.product_name, ' × ', oi.quantity) SEPARATOR ', ')
            FROM order_item oi JOIN product p ON oi.product_id = p.product_id
            WHERE oi.order_id = o.order_id
        ) AS items
    FROM delivery d
    JOIN orders o ON d.order_id = o.order_id
    JOIN user c ON o.customer_id = c.user_id
    LEFT JOIN location l ON d.location_id = l.location_id
    WHERE d.delivery_id = ? AND d.driver_id = ?
";

$stmt = $pdo->prepare($query);
$stmt->execute([$delivery_id, $driver_id]);
$delivery = $stmt->fetch();

if (!$delivery) {
    echo "Delivery not found or access denied.";
    exit();
}

// Prepare address for Google Maps (URL Encoded)
// If you deleted phone_number from your DB earlier, it won't crash here.
$raw_address = $delivery['address_line'] . ', ' . $delivery['city'] . ', ' . $delivery['postcode'];
$map_address = urlencode($raw_address);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Active Route - ShopEasy</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; background-color: #f4f5f7; display: flex; flex-direction: column; align-items: center; padding: 20px; }
    
    .app-container { width: 100%; max-width: 500px; background: #ffffff; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); overflow: hidden; }
    
    .app-topbar { background: #1a1a2e; padding: 16px 20px; display: flex; align-items: center; gap: 15px; }
    .back-btn { color: #fff; text-decoration: none; font-size: 20px; font-weight: bold; }
    .app-topbar h2 { color: #ffffff; font-size: 18px; margin: 0; }

    .map-container { width: 100%; height: 400px; background: #e5e7eb; position: relative; }
    .map-container iframe { width: 100%; height: 100%; border: none; }

    .details-box { padding: 20px; }
    .customer-info { margin-bottom: 20px; }
    .customer-info h3 { font-size: 18px; color: #111827; margin-bottom: 5px; }
    .customer-info p { font-size: 14px; color: #4b5563; margin-bottom: 5px; line-height: 1.5; }
    
    .badge { display: inline-block; background: #fee2e2; color: #991b1b; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; margin-bottom: 10px; }

    .btn-complete { display: block; width: 100%; text-align: center; background: #059669; color: #ffffff; text-decoration: none; padding: 16px; border-radius: 8px; font-weight: bold; font-size: 16px; border: none; cursor: pointer; transition: background 0.2s; box-shadow: 0 4px 6px -1px rgba(5, 150, 105, 0.4); }
    .btn-complete:hover { background: #047857; }
</style>
</head>
<body>

<div class="app-container">
    <div class="app-topbar">
        <a href="driver_view.php" class="back-btn">←</a>
        <h2>Active Navigation</h2>
    </div>

    <div class="map-container">
        <iframe 
            src="https://maps.google.com/maps?q=<?= $map_address ?>&output=embed" 
            allowfullscreen="" 
            loading="lazy">
        </iframe>
    </div>

    <div class="details-box">
        <div class="badge">ON ROUTE</div>
        
        <div class="customer-info">
            <h3>Drop-off Details: Order #<?= htmlspecialchars($delivery['order_id']) ?></h3>
            <p><strong>Customer:</strong> <?= htmlspecialchars($delivery['customer_name']) ?></p>
            <p><strong>Address:</strong> <?= htmlspecialchars($raw_address) ?></p>
            <p><strong>Items:</strong> <?= htmlspecialchars($delivery['items']) ?></p>
        </div>

        <form method="POST" action="update_route.php">
            <input type="hidden" name="delivery_id" value="<?= $delivery['delivery_id'] ?>">
            <input type="hidden" name="action" value="complete">
            <button type="submit" class="btn-complete" onclick="return confirm('Confirm that this order has been handed to the customer?');">
                Confirm Drop-off
            </button>
        </form>
    </div>
</div>

</body>
</html>