<?php
session_start();

// Disable browser cache
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// DRIVER ONLY CHECK
if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'driver'
) {
    header("Location: login.php");
    exit();
}

require 'db_connect.php';
$driver_id = (int) $_SESSION['user_id'];

// ===============================
// GET DRIVER NAME
// ===============================
$stmt = $pdo->prepare("SELECT full_name FROM user WHERE user_id = ?");
$stmt->execute([$driver_id]);
$driver_name = $stmt->fetchColumn() ?: 'Ali'; 
// Extract just the first name for the greeting (e.g., "Ali")
$first_name = explode(' ', trim($driver_name))[0];

// ===============================
// FETCH ALL DELIVERIES (Including Completed)
// ===============================
$query = "
    SELECT 
        d.delivery_id, 
        d.delivery_status, 
        o.order_id, 
        c.full_name AS customer_name,
        l.address_line, 
        l.city, 
        l.postcode,
        (
            SELECT GROUP_CONCAT(CONCAT(p.product_name, ' × ', oi.quantity) SEPARATOR ', ')
            FROM order_item oi
            JOIN product p ON oi.product_id = p.product_id
            WHERE oi.order_id = o.order_id
        ) AS items
    FROM delivery d
    JOIN orders o ON d.order_id = o.order_id
    JOIN user c ON o.customer_id = c.user_id
    LEFT JOIN location l ON d.location_id = l.location_id
    WHERE d.driver_id = ? 
    ORDER BY o.order_id DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute([$driver_id]);
$deliveries = $stmt->fetchAll();

// Calculate how many deliveries are left to do today
$active_deliveries_count = 0;
foreach ($deliveries as $d) {
    if ($d['delivery_status'] !== 'delivered') {
        $active_deliveries_count++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ShopEasy - Driver View</title>
<style>
    /* Global Reset & Typography */
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { 
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; 
        background-color: #f4f5f7; 
        color: #333; 
        display: flex; 
        flex-direction: column; 
        align-items: center; 
        padding: 40px 20px; 
    }

    /* Page Header */
    .page-header { text-align: center; margin-bottom: 30px; }
    .page-header h1 { font-size: 22px; color: #111827; margin-bottom: 8px; }

    /* Main App Container */
    .app-container { 
        width: 100%; 
        max-width: 500px; 
        background: #ffffff; 
        border-radius: 12px; 
        border: 1px solid #e5e7eb;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); 
        overflow: hidden; 
    }

    /* App Top Bar */
    .app-topbar { 
        background: #1a1a2e; 
        padding: 16px 20px; 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
    }
    .app-topbar h2 { color: #ffffff; font-size: 18px; font-weight: bold; }
    .app-topbar a { color: #f87171; text-decoration: none; font-size: 14px; font-weight: bold; }

    /* App Body */
    .app-body { padding: 20px; }
    .greeting { font-size: 15px; color: #4b5563; margin-bottom: 20px; }
    .greeting strong { color: #111827; }

    /* Order Card */
    .order-card { 
        border: 1px solid #e5e7eb; 
        border-radius: 10px; 
        padding: 18px; 
        margin-bottom: 16px; 
    }
    .order-card:last-child { margin-bottom: 0; }

    /* Card Header & Dynamic Badges */
    .card-header { 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        margin-bottom: 12px; 
    }
    .card-header h3 { font-size: 16px; color: #111827; }
    .badge { 
        padding: 4px 10px; 
        border-radius: 20px; 
        font-size: 12px; 
        font-weight: bold; 
        display: inline-block;
    }
    .badge-pending { background: #fef3c7; color: #92400e; } /* Yellow */
    .badge-route { background: #dbeafe; color: #1e40af; }   /* Blue */
    .badge-completed { background: #d1fae5; color: #065f46; } /* Green */

    /* Address & Details */
    .address { 
        font-size: 14px; 
        color: #6b7280; 
        margin-bottom: 14px; 
        display: flex; 
        align-items: flex-start; 
        gap: 8px; 
    }
    .address-icon { color: #f43f5e; font-size: 16px; }
    
    .map-placeholder { 
        background: #ecfdf5; 
        border: 1px dashed #6ee7b7; 
        border-radius: 8px; 
        padding: 24px 20px; 
        text-align: center; 
        color: #059669; 
        font-size: 13px; 
        margin-bottom: 14px; 
    }

    .item-list { font-size: 13px; color: #4b5563; line-height: 1.6; margin-bottom: 16px; }
    .item-list strong { color: #111827; }

    /* Action Button */
    .btn-route { 
        display: block; 
        width: 100%; 
        text-align: center; 
        background: #2563eb; 
        color: #ffffff; 
        text-decoration: none; 
        padding: 12px; 
        border-radius: 6px; 
        font-weight: bold; 
        font-size: 14px; 
        border: none; 
        cursor: pointer; 
        transition: background 0.2s;
    }
    .btn-route:hover { background: #1d4ed8; }
</style>
</head>
<body>

<div class="page-header">
    <h1>ShopEasy — Single Owner Online Store</h1>
</div>

<div class="app-container">
    <div class="app-topbar">
        <h2>My Deliveries</h2>
        <a href="logout.php">Logout</a>
    </div>
    
    <div class="app-body">
        <div class="greeting">
            Hi <?= htmlspecialchars($first_name) ?> — <strong><?= $active_deliveries_count ?> deliveries</strong> left today
        </div>

        <?php if (count($deliveries) === 0): ?>
            <div class="order-card" style="text-align: center; color: #6b7280;">
                You have no assigned deliveries yet.
            </div>
        <?php else: ?>
            
            <?php 
            $active_route_shown = false; // Flag to ensure we only show the map/button on the NEXT available order
            
            foreach ($deliveries as $index => $delivery): ?>
                <?php 
                    // Format Address safely
                    $full_address = $delivery['address_line'] ? htmlspecialchars($delivery['address_line'] . ', ' . $delivery['city'] . ', ' . $delivery['postcode']) : 'Not Specified Yet, Kuala Lumpur, 50000';
                    
                    // Dynamic Badge Logic
                    if ($delivery['delivery_status'] === 'assigned') {
                        $display_status = 'Pending Pickup';
                        $badge_class = 'badge-pending';
                    } elseif ($delivery['delivery_status'] === 'out_for_delivery') {
                        $display_status = 'On Route';
                        $badge_class = 'badge-route';
                    } elseif ($delivery['delivery_status'] === 'delivered') {
                        $display_status = 'Completed';
                        $badge_class = 'badge-completed';
                    } else {
                        $display_status = ucfirst(str_replace('_', ' ', $delivery['delivery_status']));
                        $badge_class = 'badge-pending';
                    }

                    // Check if this is the active order the driver needs to focus on right now
                    $is_current_task = false;
                    if (!$active_route_shown && $delivery['delivery_status'] !== 'delivered') {
                        $is_current_task = true;
                        $active_route_shown = true; 
                    }
                ?>
                
                <div class="order-card">
                    <div class="card-header">
                        <h3>Order #<?= htmlspecialchars($delivery['order_id']) ?></h3>
                        <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($display_status) ?></span>
                    </div>
                    
                    <div class="address">
                        <span class="address-icon">📍</span>
                        <span><?= $full_address ?></span>
                    </div>

                    <?php if ($is_current_task): ?>
                        <div class="map-placeholder">
                            Map — GPS route to destination
                        </div>
                    <?php endif; ?>

                    <div class="item-list">
                        <div><strong>Items:</strong> <?= htmlspecialchars($delivery['items'] ?: 'No items listed') ?></div>
                        <?php if ($is_current_task): ?>
                            <div><strong>Customer:</strong> <?= htmlspecialchars($delivery['customer_name']) ?></div>
                        <?php endif; ?>
                    </div>

                    <?php if ($is_current_task): ?>
                        <form method="POST" action="update_route.php">
                            <input type="hidden" name="delivery_id" value="<?= $delivery['delivery_id'] ?>">
                            <button type="submit" class="btn-route">Start Route</button>
                        </form>
                    <?php endif; ?>
                </div>
                
            <?php endforeach; ?>
            
        <?php endif; ?>
    </div>
</div>

</body>
</html>
