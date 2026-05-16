<?php
session_start();
require 'db_connect.php';

// Initialize cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle Add to Cart action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $product_name = $_POST['product_name'];
    $product_price = $_POST['product_price'];

    // Check if item is already in cart to increase quantity, otherwise add it
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]['quantity'] += 1;
    } else {
        $_SESSION['cart'][$product_id] = [
            'name' => $product_name,
            'price' => $product_price,
            'quantity' => 1
        ];
    }
    // Refresh page to stop duplicate submissions on reload
    header("Location: shop.php");
    exit();
}

// Handle Order Placement (Saves to Database & Updates Stock)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order']) && !empty($_SESSION['cart'])) {
    $user_id = $_SESSION['user_id'];
    $total_amount = 0;
    
    // Calculate total
    foreach ($_SESSION['cart'] as $item) {
        $total_amount += ($item['price'] * $item['quantity']);
    }
    
    // Grab the user's default location
    $loc_stmt = $pdo->prepare("SELECT location_id FROM location WHERE user_id = ? LIMIT 1");
    $loc_stmt->execute([$user_id]);
    $loc = $loc_stmt->fetch();
    $location_id = $loc ? $loc['location_id'] : 1; 

    try {
        // Start a Database Transaction for data integrity
        $pdo->beginTransaction();

        // 1. Create the main order
        $stmt = $pdo->prepare("INSERT INTO orders (customer_id, location_id, total_amount, order_status) VALUES (?, ?, ?, 'pending')");
        $stmt->execute([$user_id, $location_id, $total_amount]);
        $order_id = $pdo->lastInsertId(); 

        // 2. Process each item in the cart
        foreach ($_SESSION['cart'] as $product_id => $item) {
            // A. Add the item to the order record
            $stmt = $pdo->prepare("INSERT INTO order_item (order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)");
            $stmt->execute([$order_id, $product_id, $item['quantity'], $item['price']]);

            // B. Deduct the ordered quantity from the inventory table
            $stock_stmt = $pdo->prepare("UPDATE inventory SET quantity_available = quantity_available - ? WHERE product_id = ?");
            $stock_stmt->execute([$item['quantity'], $product_id]);
        }

        // Everything worked! Save the changes permanently.
        $pdo->commit();

        // Clear the cart and show success
        $_SESSION['cart'] = [];
        $order_success = "Success! Order #$order_id has been placed.";

    } catch (Exception $e) {
        // If anything fails, undo all database changes to prevent data corruption
        $pdo->rollBack();
        $order_success = "Error placing order. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>ShopEasy - Shop</title>
<style>
  /* Keeping Abdelrahman's original CSS exact */
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Arial, sans-serif; background: #f0f2f5; }
  nav { background: #1a1a2e; padding: 12px 30px; display: flex; justify-content: space-between; align-items: center; }
  nav .brand { color: #fff; font-size: 18px; font-weight: bold; }
  nav .links { display: flex; gap: 20px; align-items: center; }
  nav .links a { color: #ccc; text-decoration: none; font-size: 14px; }
  nav .links a:hover { color: #fff; }
  nav .links .cart-btn { background: #185FA5; color: #fff; padding: 6px 14px; border-radius: 6px; font-size: 13px; }
  .container { max-width: 1100px; margin: 0 auto; padding: 24px 20px; display: grid; grid-template-columns: 1fr 260px; gap: 20px; }
  .section-title { font-size: 16px; font-weight: bold; color: #1a1a2e; margin-bottom: 14px; }
  .product-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
  .product-card { background: #fff; border: 1px solid #e0e0e0; border-radius: 10px; padding: 14px; }
  .product-img { width: 100%; height: 80px; background: #e8eef7; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 28px; margin-bottom: 10px; }
  .product-name { font-size: 13px; font-weight: bold; color: #1a1a2e; margin-bottom: 4px; }
  .product-price { font-size: 14px; color: #185FA5; font-weight: bold; margin-bottom: 6px; }
  .badge { display: inline-block; font-size: 11px; padding: 2px 8px; border-radius: 20px; font-weight: bold; margin-bottom: 8px; }
  .in-stock { background: #d4edda; color: #155724; }
  .low-stock { background: #fff3cd; color: #856404; }
  .out-stock { background: #f8d7da; color: #721c24; }
  .add-btn { width: 100%; padding: 7px; font-size: 13px; font-weight: bold; border: 1px solid #185FA5; border-radius: 6px; background: #fff; color: #185FA5; cursor: pointer; }
  .add-btn:hover { background: #185FA5; color: #fff; }
  .add-btn:disabled { border-color: #ccc; color: #aaa; cursor: not-allowed; }
  .cart-box { background: #fff; border: 1px solid #ddd; border-radius: 10px; padding: 16px; position: sticky; top: 20px; }
  .cart-title { font-size: 15px; font-weight: bold; color: #1a1a2e; margin-bottom: 14px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
  .cart-item { display: flex; justify-content: space-between; font-size: 13px; padding: 7px 0; border-bottom: 1px solid #eee; color: #333; }
  .cart-empty { font-size: 13px; color: #aaa; text-align: center; padding: 20px 0; }
  .cart-total { display: flex; justify-content: space-between; font-size: 14px; font-weight: bold; padding: 10px 0; }
  .order-btn { width: 100%; padding: 10px; background: #185FA5; color: #fff; font-size: 14px; font-weight: bold; border: none; border-radius: 6px; cursor: pointer; margin-top: 10px; }
  .success-msg { background: #d4edda; color: #155724; padding: 10px; border-radius: 6px; font-size: 13px; margin-bottom: 15px; text-align: center; border: 1px solid #c3e6cb;}
</style>
</head>
<body>
<nav>
  <span class="brand">ShopEasy</span>
  <div class="links">
    <a href="shop.php">Products</a>
    <a href="orders.php">My Orders</a>
    <a href="#" class="cart-btn">Cart (<?php echo array_sum(array_column($_SESSION['cart'], 'quantity')); ?>)</a>
    <a href="login.php">Logout</a>
  </div>
</nav>

<div class="container">
  <div>
    <div class="section-title">All Products</div>
    
    <?php if(isset($order_success)) { echo "<div class='success-msg'>$order_success</div>"; } ?>

    <div class="product-grid">
      <?php
      // Join Habiba's Product table with her Inventory table
      $stmt = $pdo->query("SELECT p.product_id, p.product_name, p.price, i.quantity_available 
                           FROM product p 
                           LEFT JOIN inventory i ON p.product_id = i.product_id 
                           WHERE p.is_active = 1");
      $products = $stmt->fetchAll();

      foreach ($products as $row) {
          $stock = $row['quantity_available'] ?? 0;
          if ($stock > 10) {
              $badgeClass = "in-stock"; $badgeText = "In stock";
          } elseif ($stock > 0) {
              $badgeClass = "low-stock"; $badgeText = "Low stock ($stock)";
          } else {
              $badgeClass = "out-stock"; $badgeText = "Out of stock";
          }
          
          $disabled = ($stock <= 0) ? "disabled" : "";
          $btnText = ($stock <= 0) ? "Unavailable" : "+ Add to cart";

          // Form wrapper around the button to submit data to the cart
          echo '
          <div class="product-card">
            <div class="product-img">📦</div> 
            <div class="product-name">' . htmlspecialchars($row["product_name"]) . '</div>
            <div class="product-price">RM ' . number_format($row["price"], 2) . '</div>
            <span class="badge ' . $badgeClass . '">' . $badgeText . '</span><br>
            <form method="POST" action="shop.php">
                <input type="hidden" name="product_id" value="' . $row["product_id"] . '">
                <input type="hidden" name="product_name" value="' . htmlspecialchars($row["product_name"]) . '">
                <input type="hidden" name="product_price" value="' . $row["price"] . '">
                <button type="submit" name="add_to_cart" class="add-btn" ' . $disabled . '>' . $btnText . '</button>
            </form>
          </div>';
      }
      ?>
    </div>
  </div>

  <div>
    <div class="cart-box">
      <div class="cart-title">Your Cart</div>
      
      <?php
      $total_price = 0;
      if (empty($_SESSION['cart'])) {
          echo '<div class="cart-empty">Your cart is empty</div>';
      } else {
          foreach ($_SESSION['cart'] as $item) {
              $item_total = $item['price'] * $item['quantity'];
              $total_price += $item_total;
              echo '<div class="cart-item">
                      <span>' . htmlspecialchars($item['name']) . ' × ' . $item['quantity'] . '</span>
                      <span>RM ' . number_format($item_total, 2) . '</span>
                    </div>';
          }
      }
      ?>
      
      <div class="cart-total"><span>Total</span><span>RM <?php echo number_format($total_price, 2); ?></span></div>
      
      <form method="POST" action="shop.php">
          <button type="submit" name="place_order" class="order-btn" <?php echo empty($_SESSION['cart']) ? 'disabled style="background:#ccc;"' : ''; ?>>
            Place Order
          </button>
      </form>

    </div>
  </div>
</div>
</body>
</html>