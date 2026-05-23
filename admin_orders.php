<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");
require 'db_connect.php';

// ===============================
// SECURITY CHECK: ADMIN ONLY
// ===============================
if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    header("Location: login.php");
    exit();
}

$admin_id = (int) $_SESSION['user_id'];

// ===============================
// GET ADMIN NAME SECURELY
// ===============================
$stmt = $pdo->prepare("
    SELECT full_name
    FROM user
    WHERE user_id = ?
");

$stmt->execute([$admin_id]);

$owner_name = $stmt->fetchColumn() ?: 'Admin';

$success_msg = '';
$error_msg = '';

// ===============================
// HANDLE DRIVER ASSIGNMENT
// ===============================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_driver'])) {

    $order_id = (int) $_POST['order_id'];
    $driver_id = (int) $_POST['driver_id'];

    try {

        // ==========================================
        // TRANSACTION CONTROL START
        // ==========================================
        $pdo->beginTransaction();

        // ==========================================
        // VERIFY ORDER EXISTS
        // LOCK ORDER TO PREVENT RACE CONDITION
        // ==========================================
        $order_stmt = $pdo->prepare("
            SELECT
                order_id,
                location_id,
                order_status
            FROM orders
            WHERE order_id = ?
            AND order_status IN ('pending', 'processing')
            FOR UPDATE
        ");

        $order_stmt->execute([$order_id]);

        $order = $order_stmt->fetch();

        if (!$order) {
            throw new Exception("Order not available.");
        }

        // ==========================================
        // CHECK IF ORDER ALREADY ASSIGNED
        // DRIVER ASSIGNMENT PROTECTION
        // ==========================================
        $existing_stmt = $pdo->prepare("
            SELECT delivery_id
            FROM delivery
            WHERE order_id = ?
            LIMIT 1
        ");

        $existing_stmt->execute([$order_id]);

        if ($existing_stmt->fetch()) {
            throw new Exception("Order already assigned.");
        }

        // ==========================================
        // VALIDATE DRIVER
        // ONLY ACTIVE DRIVER ROLE ALLOWED
        // ==========================================
        $driver_stmt = $pdo->prepare("
            SELECT
                u.user_id,
                u.full_name
            FROM user u
            JOIN user_role ur
                ON u.user_id = ur.user_id
            WHERE u.user_id = ?
            AND ur.role_id = 3
            AND u.account_status = 'active'
            LIMIT 1
        ");

        $driver_stmt->execute([$driver_id]);

        $driver = $driver_stmt->fetch();

        if (!$driver) {
            throw new Exception("Invalid driver.");
        }

        $location_id = (int) $order['location_id'];

        // ==========================================
        // CREATE DELIVERY RECORD
        // ==========================================
        $insert_stmt = $pdo->prepare("
            INSERT INTO delivery
            (
                order_id,
                driver_id,
                location_id,
                delivery_status
            )
            VALUES
            (
                ?,
                ?,
                ?,
                'assigned'
            )
        ");

        $insert_stmt->execute([
            $order_id,
            $driver_id,
            $location_id
        ]);

        // ==========================================
        // UPDATE ORDER STATUS
        // ==========================================
        $update_stmt = $pdo->prepare("
            UPDATE orders
            SET order_status = 'shipped'
            WHERE order_id = ?
            AND order_status IN ('pending', 'processing')
        ");

        $update_stmt->execute([$order_id]);

        // ==========================================
        // AUDIT LOGGING
        // ==========================================
        logAudit(
            $pdo,
            $admin_id,
            'DRIVER_ASSIGNMENT',
            'Admin assigned driver ID ' .
            $driver_id .
            ' to order ID ' .
            $order_id
        );

        // ==========================================
        // COMMIT TRANSACTION
        // ==========================================
        $pdo->commit();

        $success_msg =
            "<div class='msg-success'>
                Order #" . $order_id . "
                successfully dispatched to Driver #" . $driver_id . ".
            </div>";

    } catch (Exception $e) {

        // ==========================================
        // ROLLBACK TRANSACTION
        // ==========================================
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        // ==========================================
        // FAILED AUDIT LOG
        // ==========================================
        logAudit(
            $pdo,
            $admin_id,
            'DRIVER_ASSIGNMENT_FAILED',
            'Admin failed to assign driver ID ' .
            $driver_id .
            ' to order ID ' .
            $order_id
        );

        $error_msg =
            "<div class='error-msg'>
                Assignment failed.
                The order may already be assigned,
                unavailable,
                or the driver may be invalid.
            </div>";
    }
}

// ==========================================
// FETCH PENDING ORDERS
// ==========================================
$orders_stmt = $pdo->query("
    SELECT
        o.order_id,
        o.total_amount,
        o.order_status,
        o.ordered_at,
        u.full_name AS customer_name
    FROM orders o
    JOIN user u
        ON o.customer_id = u.user_id
    WHERE o.order_status IN ('pending', 'processing')
    AND NOT EXISTS (
        SELECT 1
        FROM delivery d
        WHERE d.order_id = o.order_id
    )
    ORDER BY o.ordered_at ASC
");

$pending_orders = $orders_stmt->fetchAll();

// ==========================================
// FETCH ACTIVE DRIVERS
// ==========================================
$drivers_stmt = $pdo->query("
    SELECT
        u.user_id,
        u.full_name
    FROM user u
    JOIN user_role ur
        ON u.user_id = ur.user_id
    WHERE ur.role_id = 3
    AND u.account_status = 'active'
    ORDER BY u.full_name ASC
");

$available_drivers = $drivers_stmt->fetchAll();
?>
