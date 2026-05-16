<?php
require 'db_connect.php';

echo "<h1>ShopEasy Application Testing Pipeline</h1>";

try {
    // 1. First Operation: INSERT
    $stmt1 = $pdo->prepare("INSERT INTO products (name, price, stock, icon) VALUES (?, ?, ?, ?)");
    $stmt1->execute(['Mechanical Keyboard', 150.00, 12, '⌨️']);
    echo "<p style='color:green; font-weight:bold;'>[SUCCESS] Test 1: Inserted 'Mechanical Keyboard' into the database.</p>";

    // 2. Second Operation: DELETE
    // (Assuming 'Wireless Mouse' from your mockup needs removal)
    $stmt2 = $pdo->prepare("DELETE FROM products WHERE name = ?");
    $stmt2->execute(['Wireless Mouse']);
    echo "<p style='color:orange; font-weight:bold;'>[SUCCESS] Test 2: Deleted old 'Wireless Mouse' entry from inventory.</p>";

    // 3. Third Operation: INSERT AGAIN
    $stmt3 = $pdo->prepare("INSERT INTO products (name, price, stock, icon) VALUES (?, ?, ?, ?)");
    $stmt3->execute(['Gaming Controller', 250.00, 8, '🎮']);
    echo "<p style='color:green; font-weight:bold;'>[SUCCESS] Test 3: Inserted 'Gaming Controller' into the database.</p>";

} catch (Exception $e) {
    echo "<p style='color:red;'>Test Failed: " . $e->getMessage() . "</p>";
    echo "<p><em>Note: If tables don't exist yet, make sure Person A has executed their SQL schema code in your phpMyAdmin!</em></p>";
}
?>