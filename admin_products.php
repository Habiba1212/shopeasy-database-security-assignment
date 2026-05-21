<?php
session_start();
require 'db_connect.php';

// Security Check: Allow admin Only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Get Owner Name securely
$stmt = $pdo->prepare("SELECT full_name FROM user WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$owner_name = $stmt->fetchColumn();

// --- HANDLE FORM SUBMISSIONS  ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
   if (isset($_POST['add_product'])) {

    // 1. Insert into Product table
    $stmt = $pdo->prepare("
        INSERT INTO product 
        (product_name, price, is_active) 
        VALUES (?, ?, 1)
    ");

    $stmt->execute([
        $_POST['new_name'],
        $_POST['new_price']
    ]);

    $new_product_id = $pdo->lastInsertId();

    // 2. Create inventory record
    $stmt2 = $pdo->prepare("
        INSERT INTO inventory 
        (product_id, quantity_available) 
        VALUES (?, 0)
    ");

    $stmt2->execute([$new_product_id]);

    // 3. AUDIT LOGGING
    logAudit(
        $pdo,
        $_SESSION['user_id'],
        'PRODUCT_ADD',
        'Admin added new product: ' . $_POST['new_name'] . ' with product ID ' . $new_product_id
    );

} elseif (isset($_POST['delete_product'])) {

    // Delete product
    $stmt = $pdo->prepare("
        DELETE FROM product 
        WHERE product_id = ?
    ");

    $stmt->execute([
        $_POST['delete_id']
    ]);

    // AUDIT LOGGING
    logAudit(
        $pdo,
        $_SESSION['user_id'],
        'PRODUCT_DELETE',
        'Admin deleted product with product ID ' . $_POST['delete_id']
    );
}
    // Refresh to prevent duplicate form submissions
    header("Location: admin_products.php");
    exit();
}

// Fetch all Products
$products = $pdo->query("SELECT product_id, product_name, price, is_active, created_at FROM product ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>ShopEasy - Products</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 30px; }
  h1 { text-align: center; font-size: 20px; color: #1a1a2e; margin-bottom: 8px; }
  .subtitle { text-align: center; font-size: 13px; color: #666; margin-bottom: 30px; }
  .screen { background: #fff; border: 1px solid #ddd; border-radius: 12px; overflow: hidden; }
  .navbar { display: flex; justify-content: space-between; align-items: center; padding: 12px 20px; background: #1a1a2e; color: #fff; }
  .nav-brand { font-size: 16px; font-weight: bold; color: #fff; }
  .nav-links { display: flex; gap: 18px; font-size: 13px; color: #ccc; }
  .nav-links a { color: #f87171; text-decoration: none; font-weight: bold;}
  .content { padding: 20px; }
  .sidebar-layout { display: grid; grid-template-columns: 160px 1fr; gap: 14px; }
  .side-menu { background: #1a1a2e; border-radius: 10px; padding: 12px; height: fit-content; }
  .side-item { padding: 8px 12px; font-size: 13px; border-radius: 6px; margin-bottom: 3px; cursor: pointer; color: #aaa; }
  .side-item.active { background: #185FA5; color: #fff; font-weight: bold; }
  .card { background: #fff; border: 1px solid #e0e0e0; border-radius: 10px; padding: 16px; margin-bottom: 14px; }
  .card-title { font-size: 16px; font-weight: bold; color: #1a1a2e; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px solid #eee;}
  table { width: 100%; border-collapse: collapse; font-size: 13px; }
  th { background: #f5f5f5; text-align: left; padding: 10px; font-size: 12px; color: #555; border-bottom: 1px solid #ddd; }
  td { padding: 10px; border-bottom: 1px solid #eee; color: #333; }
  .badge { display: inline-block; font-size: 11px; padding: 4px 10px; border-radius: 20px; font-weight: bold; background: #d4edda; color: #155724; }
  .btn { padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: bold; color: #fff; }
  .btn-blue { background: #185FA5; }
  .btn-red { background: #dc3545; }
  .form-row { display: flex; gap: 10px; margin-bottom: 15px; }
  .form-row input { padding: 8px; border: 1px solid #ccc; border-radius: 4px; font-size: 13px; flex: 1; }
</style>
</head>
<body>
<h1>ShopEasy — Admin Panel</h1>
<p class="subtitle">Product Catalog Management</p>
<div class="screen">
  <div class="navbar">
    <span class="nav-brand">ShopEasy Admin Panel</span>
    <div class="nav-links">
      <span>Logged in as: <strong><?php echo htmlspecialchars($owner_name); ?></strong></span>
      <a href="logout.php">Logout</a>
    </div>
  </div>
  <div class="content">
    <div class="sidebar-layout">
      <div class="side-menu">
  <a href="admin_dashboard.php" style="text-decoration: none;"><div class="side-item <?php echo (basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php') ? 'active' : ''; ?>">Dashboard</div></a>
  <a href="admin_orders.php" style="text-decoration: none;"><div class="side-item <?php echo (basename($_SERVER['PHP_SELF']) == 'admin_orders.php') ? 'active' : ''; ?>">Orders</div></a>
  <a href="admin_products.php" style="text-decoration: none;"><div class="side-item <?php echo (basename($_SERVER['PHP_SELF']) == 'admin_products.php') ? 'active' : ''; ?>">Products</div></a>
  <a href="admin_stock.php" style="text-decoration: none;"><div class="side-item <?php echo (basename($_SERVER['PHP_SELF']) == 'admin_stock.php') ? 'active' : ''; ?>">Stock</div></a>
  <a href="admin_drivers.php" style="text-decoration: none;"><div class="side-item <?php echo (basename($_SERVER['PHP_SELF']) == 'admin_drivers.php') ? 'active' : ''; ?>">Drivers</div></a>
  <a href="admin_customers.php" style="text-decoration: none;"><div class="side-item <?php echo (basename($_SERVER['PHP_SELF']) == 'admin_customers.php') ? 'active' : ''; ?>">Customers</div></a>
</div>
      <div>
        <div class="card" style="background: #f8f9fa;">
          <div class="card-title">Add New Product</div>
          <form method="POST" action="admin_products.php" class="form-row">
            <input type="text" name="new_name" placeholder="Product Name" required>
            <input type="number" name="new_price" step="0.01" placeholder="Price (RM)" required>
            <button type="submit" name="add_product" class="btn btn-blue">+ Add Product</button>
          </form>
        </div>

        <div class="card">
          <div class="card-title">Product Catalog</div>
          <table>
            <thead>
              <tr><th>ID</th><th>Product Name</th><th>Price</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody>
              <?php
              foreach ($products as $p) {
                  echo "<tr>
                          <td>#{$p['product_id']}</td>
                          <td><strong>" . htmlspecialchars($p['product_name']) . "</strong></td>
                          <td>RM " . number_format($p['price'], 2) . "</td>
                          <td><span class='badge'>Active</span></td>
                          <td>
                            <form method='POST' action='admin_products.php' style='display:inline;'>
                              <input type='hidden' name='delete_id' value='{$p['product_id']}'>
                              <button type='submit' name='delete_product' class='btn btn-red'>Delete</button>
                            </form>
                          </td>
                        </tr>";
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
