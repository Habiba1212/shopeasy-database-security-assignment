<?php

session_start();

if (!isset($_SESSION['user_id'])) {

    header("Location: login.php");

    exit();
}

?>
<?php
session_start();
require 'db_connect.php';

// Security Check: Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Initialize cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Add to Cart action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $product_name = $_POST['product_name'];
    $product_price = $_POST['product_price'];

    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]['quantity'] += 1;
    } else {
        $_SESSION['cart'][$product_id] = [
            'name' => $product_name,
            'price' => $product_price,
            'quantity' => 1
        ];
    }
    header("Location: shop.php");
    exit();
}

// Remove from Cart action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['remove_from_cart'])) {
    $product_id = $_POST['product_id'];
    
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
    }
    
    header("Location: shop.php");
    exit();
}

// Order & Payment Placement (Saves to orders, location, and payment tables!)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['process_checkout']) && !empty($_SESSION['cart'])) {
    $user_id = $_SESSION['user_id'];
    $payment_method = $_POST['payment_method'];
    
    // Capture Delivery Details from the form
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $postcode = trim($_POST['postcode']);
    
    $total_amount = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total_amount += ($item['price'] * $item['quantity']);
    }

    try {
        // Start transaction to keep data perfectly synchronized
        $pdo->beginTransaction();

        // 1. Handle Delivery Location (Update if exists, Insert if new)
        $loc_stmt = $pdo->prepare("SELECT location_id FROM location WHERE user_id = ? LIMIT 1");
        $loc_stmt->execute([$user_id]);
        $loc = $loc_stmt->fetch();

        if ($loc) {
            $location_id = $loc['location_id'];
            $upd_loc = $pdo->prepare("UPDATE location SET address_line = ?, city = ?, postcode = ? WHERE location_id = ?");
            $upd_loc->execute([$address, $city, $postcode, $location_id]);
        } else {
            $ins_loc = $pdo->prepare("INSERT INTO location (user_id, address_line, city, postcode) VALUES (?, ?, ?, ?)");
            $ins_loc->execute([$user_id, $address, $city, $postcode]);
            $location_id = $pdo->lastInsertId(); 
        }

        // 2. Create the main order record, now linked to the verified location
        $stmt = $pdo->prepare("INSERT INTO orders (customer_id, location_id, total_amount, order_status) VALUES (?, ?, ?, 'pending')");
        $stmt->execute([$user_id, $location_id, $total_amount]);
        $order_id = $pdo->lastInsertId(); 

        // 3. Insert items and deduct stock quantities
        foreach ($_SESSION['cart'] as $product_id => $item) {
            $stmt = $pdo->prepare("INSERT INTO order_item (order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)");
            $stmt->execute([$order_id, $product_id, $item['quantity'], $item['price']]);

            $stock_stmt = $pdo->prepare("UPDATE inventory SET quantity_available = quantity_available - ? WHERE product_id = ?");
            $stock_stmt->execute([$item['quantity'], $product_id]);
        }

        // 4. Populate Payment table
        $transaction_ref = "TXN-" . date("Y") . "-" . str_pad(rand(1, 9999), 4, "0", STR_PAD_LEFT);
        $pay_stmt = $pdo->prepare("INSERT INTO payment (order_id, payment_method, payment_status, transaction_reference, paid_at) VALUES (?, ?, 'paid', ?, CURRENT_TIMESTAMP)");
        $pay_stmt->execute([$order_id, $payment_method, $transaction_ref]);

        $pdo->commit();

        // Clear session cart state and signal success
        $_SESSION['cart'] = [];
        $order_success = "Payment Successful! Order #$order_id has been securely verified.";

    } catch (Exception $e) {
        $pdo->rollBack();
        $order_error = "Payment processing transaction aborted: " . $e->getMessage();
    }
}

// Fetch current user location to pre-fill the delivery form if it exists
$current_loc = null;
if (isset($_SESSION['user_id'])) {
    $loc_stmt = $pdo->prepare("SELECT * FROM location WHERE user_id = ? LIMIT 1");
    $loc_stmt->execute([$_SESSION['user_id']]);
    $current_loc = $loc_stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>ShopEasy - Shop</title>
<style>
  /* original UI CSS exactly intact */
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Arial, sans-serif; background: #f0f2f5; }
  nav { background: #1a1a2e; padding: 12px 30px; display: flex; justify-content: space-between; align-items: center; }
  nav .brand { color: #fff; font-size: 18px; font-weight: bold; }
  nav .links { display: flex; gap: 20px; align-items: center; }
  nav .links a { color: #ccc; text-decoration: none; font-size: 14px; }
  nav .links a:hover { color: #fff; }
  nav .links .cart-btn { background: #185FA5; color: #fff; padding: 6px 14px; border-radius: 6px; font-size: 13px; }
  .container { max-width: 1100px; margin: 0 auto; padding: 24px 20px; display: grid; grid-template-columns: 1fr 280px; gap: 20px; }
  .section-title { font-size: 16px; font-weight: bold; color: #1a1a2e; margin-bottom: 14px; }
  .product-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
  .product-card { background: #fff; border: 1px solid #e0e0e0; border-radius: 10px; padding: 14px; text-align: left; }
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
  
  /* Cart sidebar panel features */
  .cart-box { background: #fff; border: 1px solid #ddd; border-radius: 10px; padding: 16px; position: sticky; top: 20px; text-align: left; }
  .cart-title { font-size: 15px; font-weight: bold; color: #1a1a2e; margin-bottom: 14px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
  .cart-item { display: flex; justify-content: space-between; font-size: 13px; padding: 7px 0; border-bottom: 1px solid #eee; color: #333; }
  .cart-empty { font-size: 13px; color: #aaa; text-align: center; padding: 20px 0; }
  .cart-total { display: flex; justify-content: space-between; font-size: 14px; font-weight: bold; padding: 10px 0; }
  .order-btn { width: 100%; padding: 10px; background: #185FA5; color: #fff; font-size: 14px; font-weight: bold; border: none; border-radius: 6px; cursor: pointer; margin-top: 10px; text-align: center; display: block; text-decoration: none;}
  .btn-secondary { background: #e0e0e0; color: #333; margin-top: 6px;}
  .btn-secondary:hover { background: #d5d5d5; }
  .success-msg { background: #d4edda; color: #155724; padding: 10px; border-radius: 6px; font-size: 13px; margin-bottom: 15px; text-align: center; border: 1px solid #c3e6cb;}
  .error-msg { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 6px; font-size: 13px; margin-bottom: 15px; text-align: center; border: 1px solid #f5c6cb;}
  
  /* Added payment interface form rules */
  .pay-input { width: 100%; padding: 6px 10px; font-size: 12px; border: 1px solid #ccc; border-radius: 4px; margin-bottom: 8px; margin-top: 2px; display: block; background: #fafafa; color: #333; }
  .pay-label { font-size: 11px; color: #555; font-weight: bold; text-transform: uppercase; display: block; margin-top: 6px; }
</style>
</head>
<body>

<nav>
  <span class="brand">ShopEasy</span>
  <div class="links">
    <a href="shop.php">Products</a>
    <a href="orders.php">My Orders</a>
    <a href="#" class="cart-btn">Cart (<?php echo array_sum(array_column($_SESSION['cart'], 'quantity')); ?>)</a>
    <a href="logout.php">Logout</a>
  </div>
</nav>

<div class="container">
  <div>
    <div class="section-title">All Products</div>
    
    <?php if(isset($order_success)) { echo "<div class='success-msg'>$order_success</div>"; } ?>
    <?php if(isset($order_error)) { echo "<div class='error-msg'>$order_error</div>"; } ?>

    <div class="product-grid">
      <?php
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
      <?php if (!isset($_GET['stage']) || $_GET['stage'] !== 'payment' || empty($_SESSION['cart'])): ?>
          <div class="cart-title">Your Cart</div>
          <?php
          $total_price = 0;
          if (empty($_SESSION['cart'])) {
              echo '<div class="cart-empty">Your cart is empty</div>';
          } else {
              foreach ($_SESSION['cart'] as $product_id => $item) {
                  $item_total = $item['price'] * $item['quantity'];
                  $total_price += $item_total;
                  echo '<div class="cart-item" style="display: flex; justify-content: space-between; align-items: center;">
                          <div>
                            <strong>' . htmlspecialchars($item['name']) . '</strong><br>
                            <span style="color: #666;">' . $item['quantity'] . ' × RM ' . number_format($item['price'], 2) . '</span>
                          </div>
                          <div style="display: flex; align-items: center; gap: 10px;">
                            <span>RM ' . number_format($item_total, 2) . '</span>
                            <form method="POST" action="shop.php" style="margin: 0; padding: 0;">
                              <input type="hidden" name="product_id" value="' . $product_id . '">
                              <button type="submit" name="remove_from_cart" style="background: none; border: none; color: #dc3545; font-weight: bold; cursor: pointer; font-size: 14px; padding: 0 4px;" title="Remove Item">✕</button>
                            </form>
                          </div>
                        </div>';
              }
          }
          ?>
          <div class="cart-total"><span>Total</span><span>RM <?php echo number_format($total_price, 2); ?></span></div>
          
          <?php if (!empty($_SESSION['cart'])): ?>
              <a href="shop.php?stage=payment" class="order-btn">Proceed to Checkout</a>
          <?php else: ?>
              <button class="order-btn" disabled style="background:#ccc; cursor:not-allowed;">Proceed to Checkout</button>
          <?php endif; ?>

      <?php else: ?>
          <div class="cart-title">🛒 Checkout</div>
          
          <form method="POST" action="shop.php">
              
              <div style="margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #eee;">
                  <div style="font-size: 13px; font-weight: bold; color: #1a1a2e; margin-bottom: 10px;">📍 Step 1: Delivery Address</div>
                  
                  <label class="pay-label">Street Address</label>
                  <input type="text" name="address" class="pay-input" value="<?php echo htmlspecialchars($current_loc['address_line'] ?? ''); ?>" required>
                  
                  <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                      <div>
                          <label class="pay-label">City</label>
                          <input type="text" name="city" class="pay-input" value="<?php echo htmlspecialchars($current_loc['city'] ?? ''); ?>" required>
                      </div>
                      <div>
                          <label class="pay-label">Postcode</label>
                          <input type="text" name="postcode" class="pay-input" value="<?php echo htmlspecialchars($current_loc['postcode'] ?? ''); ?>" required maxlength="5" oninput="this.value = this.value.replace(/[^0-9]/g, '');">
                      </div>
                  </div>
              </div>

              <div style="font-size: 13px; font-weight: bold; color: #1a1a2e; margin-bottom: 10px;">💳 Step 2: Payment Details</div>
              <?php
              $total_price = 0;
              foreach ($_SESSION['cart'] as $product_id => $item) {
                  $item_total = $item['price'] * $item['quantity'];
                  $total_price += $item_total;
              }
              ?>
              <div class="cart-total" style="border-bottom: 1px dashed #ddd; margin-bottom: 10px;">
                  <span>Amount Due:</span><span>RM <?php echo number_format($total_price, 2); ?></span>
              </div>

              <label class="pay-label">Payment Method</label>
              <select name="payment_method" class="pay-input">
                  <option value="Credit Card">Credit Card</option>
                  <option value="Debit Card">Debit Card</option>
                  <option value="FPX Online Banking">FPX Online Banking</option>
              </select>

              <label class="pay-label">Cardholder Name</label>
              <input type="text" class="pay-input" placeholder="e.g. Nur Rafiqah" required>

              <label class="pay-label">Card Number</label>
              <input type="text" class="pay-input" placeholder="1234 5678 9101 1121" maxlength="19" oninput="this.value = this.value.replace(/[^0-9 ]/g, '');" required>

              <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 12px;">
                  <div>
                      <label class="pay-label">Expiry Date</label>
                      <input type="text" 
                             class="pay-input" 
                             placeholder="MM/YY" 
                             maxlength="5" 
                             inputmode="numeric"
                             oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/^([0-9]{2})/, '$1/').replace(/^(\d{2})\/(\d{2}).*/, '$1/$2');"
                             required>
                  </div>
                  <div>
                      <label class="pay-label">CVV</label>
                      <input type="text" 
                             class="pay-input" 
                             placeholder="123" 
                             maxlength="3" 
                             inputmode="numeric"
                             oninput="this.value = this.value.replace(/[^0-9]/g, '');"
                             required>
                  </div>
              </div>

              <button type="submit" name="process_checkout" class="order-btn" style="background: #22c55e; border: none;">Pay &amp; Place Order</button>
              <a href="shop.php" class="order-btn btn-secondary">← Back to Cart</a>
          </form>
      <?php endif; ?>
    </div>
  </div>
</div>

</body>
</html>
