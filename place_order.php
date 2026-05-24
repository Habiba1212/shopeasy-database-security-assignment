<?php
session_start();

// Disable browser cache
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// CUSTOMER ONLY
if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'customer'
) {
    header("Location: login.php");
    exit();
}

require 'db_connect.php';
require_once 'audit_helper.php'; // 1. ADDED AUDIT HELPER

// 2. FIXED: Use the actual logged-in user, not a hardcoded "1"
$user_id = (int) $_SESSION['user_id']; 

// NOTE: You currently have these hardcoded for testing. 
// In your final app, these should come from $_POST or a Cart session!
$product_id = 1;
$quantity = 2;

try {

    $pdo->beginTransaction();

    // Get product and stock
    $stmt = $pdo->prepare("
        SELECT p.price, i.quantity_available
        FROM product p
        JOIN inventory i ON p.product_id = i.product_id
        WHERE p.product_id = ?
    ");

    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    // STOCK PROTECTION
    if ($product['quantity_available'] < $quantity) {
        
        // AUDIT LOGGING: Record the failed attempt due to stock
        logAudit($pdo, $user_id, 'ORDER_FAILED_STOCK', 'Customer tried to order Product ID ' . $product_id . ' but stock was insufficient.');
        
        die("Insufficient stock available");
    }

    // Price from database
    $total_amount = $product['price'] * $quantity;

    // Create order
    $stmt = $pdo->prepare("
        INSERT INTO orders (
            customer_id,
            total_amount,
            order_status
        )
        VALUES (?, ?, 'pending')
    ");

    $stmt->execute([
        $user_id,
        $total_amount
    ]);

    $order_id = $pdo->lastInsertId();

    // Add order item
    $stmt = $pdo->prepare("
        INSERT INTO order_item (
            order_id,
            product_id,
            quantity,
            unit_price
        )
        VALUES (?, ?, ?, ?)
    ");

    $stmt->execute([
        $order_id,
        $product_id,
        $quantity,
        $product['price']
    ]);

    // Update stock securely
    $stmt = $pdo->prepare("
        UPDATE inventory
        SET quantity_available = quantity_available - ?
        WHERE product_id = ?
        AND quantity_available >= ?
    ");

    $stmt->execute([
        $quantity,
        $product_id,
        $quantity
    ]);

    // 3. SUCCESS AUDIT LOGGING
    logAudit($pdo, $user_id, 'ORDER_PLACED', 'Customer placed Order #' . $order_id . ' for RM ' . number_format($total_amount, 2));

    $pdo->commit();

    echo "Order placed successfully";

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // 4. FAILURE AUDIT LOGGING
    logAudit($pdo, $user_id, 'ORDER_FAILED_ERROR', 'Customer checkout failed due to system error.');

    echo "Transaction failed";
}
?>
