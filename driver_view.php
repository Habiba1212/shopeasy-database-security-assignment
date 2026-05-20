<?php

session_start();

require 'db_connect.php';

// SECURITY CHECK

if (
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'driver'
) {

    header("Location: login.php");

    exit();
}

// ---------------- START DELIVERY ACTION ----------------

if (
    $_SERVER['REQUEST_METHOD'] == 'POST' &&
    isset($_POST['start_delivery'])
) {

    $delivery_id = $_POST['delivery_id'];

    // UPDATE DELIVERY STATUS

    $stmt = $pdo->prepare("
        UPDATE delivery
        SET delivery_status = 'out_for_delivery'
        WHERE delivery_id = ?
    ");

    $stmt->execute([$delivery_id]);

    // AUDIT LOGGING

    $log_stmt = $pdo->prepare("
        INSERT INTO audit_log (user_id, action)
        VALUES (?, ?)
    ");

    $log_stmt->execute([
        $_SESSION['user_id'],
        'Driver Updated Delivery Status'
    ]);

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

        echo "

        <div class='card'>

            <h3>
                Order #{$delivery['order_id']}
            </h3>

            <p>
                Customer:
                {$delivery['full_name']}
            </p>

            <p>
                Status:
                {$delivery['delivery_status']}
            </p>

            <form method='POST'>

                <input
                    type='hidden'
                    name='delivery_id'
                    value='{$delivery['delivery_id']}'
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

</body>
</html>
