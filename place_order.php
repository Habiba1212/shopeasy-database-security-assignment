<?php
session_start();
require 'db_connect.php';

// Customer only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Example values (replace later with cart values)
$product_id = 1;
$quantity = 2;

try {

    // Start transaction
    $pdo->beginTransaction();

    // Get product price and stock
    $stmt = $pdo->prepare("
        SELECT p.price, i.quantity_available
        FROM product p
        JOIN inventory i ON p.product_id = i.product_id
        WHERE p.product_id = ?
    ");

    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if (!$product) {
        die("Product not found");
    }

    // STOCK PROTECTION
    if ($product['quantity_available'] < $quantity) {
        die("Insufficient stock available");
    }

    // Real price from database
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

    // Insert order item
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

    if ($stmt->rowCount() == 0) {
        throw new Exception("Stock update failed");
    }

    // Commit transaction
    $pdo->commit();

    echo "Order placed successfully";

} catch (Exception $e) {

    $pdo->rollBack();

    echo "Transaction failed";
}
?>
