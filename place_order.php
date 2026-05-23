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


$product_id = 1;
$quantity = 2;
$user_id = 1;

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

    $pdo->commit();

    echo "Order placed successfully";

} catch (Exception $e) {

    $pdo->rollBack();

    echo "Transaction failed";
}
?>
