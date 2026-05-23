<?php
session_start();

// Disable browser cache
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// DRIVER ONLY
if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'driver'
) {
    header("Location: login.php");
    exit();
}

require 'db_connect.php';
require 'audit_helper.php';

// ---------------- START DELIVERY ACTION ----------------

if (
    $_SERVER['REQUEST_METHOD'] == 'POST' &&
    isset($_POST['start_delivery'])
) {

    $delivery_id = (int) $_POST['delivery_id'];
    $driver_id = (int) $_SESSION['user_id'];

    // UPDATE DELIVERY STATUS

       $stmt = $pdo->prepare("
        UPDATE delivery
        SET delivery_status = 'out_for_delivery'
        WHERE delivery_id = ?
        AND driver_id = ?
        AND delivery_status = 'assigned'
    ");

    $stmt->execute([
        $delivery_id,
        $driver_id
    ]);

    if ($stmt->rowCount() > 0) {

        // AUDIT LOGGING: Successful delivery status update
        logAudit(
            $pdo,
            $driver_id,
            'DELIVERY_STATUS_UPDATE',
            'Driver started delivery ID ' . $delivery_id
        );

    } else {

        // AUDIT LOGGING: Failed or unauthorized update attempt
        logAudit(
            $pdo,
            $driver_id,
            'DELIVERY_UPDATE_FAILED',
            'Driver attempted to update delivery ID ' . $delivery_id . ' but it was not assigned or already updated'
        );
    }

    header("Location: driver_view.php");

    exit();
}

// ---------------- FETCH DELIVERIES ----------------

$stmt = $pdo->prepare("
    SELECT
        d.delivery_id,
        d.delivery_status,
        o.order_id,
        u.full_name
    FROM delivery d
    JOIN orders o
        ON d.order_id = o.order_id
    JOIN user u
        ON o.customer_id = u.user_id
    WHERE d.driver_id = ?
");

$stmt->execute([$_SESSION['user_id']]);

$deliveries = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang='en'>

<head>

<meta charset='UTF-8'>

<title>Driver View</title>

<style>

body{
    font-family:Arial;
    background:#f5f5f5;
    padding:40px;
}

.card{
    background:white;
    border-radius:10px;
    padding:20px;
    margin-bottom:20px;
    border:1px solid #ddd;
}

.btn{
    background:#185FA5;
    color:white;
    border:none;
    padding:10px 15px;
    border-radius:6px;
    cursor:pointer;
}

.logout{
    float:right;
    color:red;
    text-decoration:none;
    font-weight:bold;
}

</style>

</head>

<body>

<h1>

My Deliveries

<a href="logout.php" class="logout">
    Logout
</a>

</h1>

<?php

if (count($deliveries) > 0) {

    foreach ($deliveries as $delivery) {

    $order_id = (int) $delivery['order_id'];
    $delivery_id = (int) $delivery['delivery_id'];
    $customer_name = htmlspecialchars($delivery['full_name'], ENT_QUOTES, 'UTF-8');
    $delivery_status = htmlspecialchars($delivery['delivery_status'], ENT_QUOTES, 'UTF-8');

    echo "

    <div class='card'>

        <h3>
            Order #{$order_id}
        </h3>

        <p>
            Customer:
            {$customer_name}
        </p>

        <p>
            Status:
            {$delivery_status}
        </p>

        <form method='POST'>

            <input
                type='hidden'
                name='delivery_id'
                value='{$delivery_id}'
            >

            <button
                type='submit'
                name='start_delivery'
                class='btn'
            >

                Start Delivery

            </button>

        </form>

    </div>

    ";
}

} else {

    echo "

    <div class='card'>

        You have completed all your deliveries!

    </div>

    ";
}

?>

</div>

<script>
window.addEventListener('pageshow', function(event) {

    if (
        event.persisted ||
        (window.performance &&
         window.performance.navigation.type === 2)
    ) {

        window.location.href = 'login.php';
    }

});
</script>

</body>
</html>
