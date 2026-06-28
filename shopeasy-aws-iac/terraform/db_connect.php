<?php
// AWS RDS database configuration is injected by EC2 user data from Secrets Manager.
$host = getenv('DB_HOST') ?: '';
$db   = getenv('DB_NAME') ?: '';
$user = getenv('DB_USER') ?: '';
$pass = getenv('DB_PASSWORD') ?: '';

if ($host === '' || $db === '' || $user === '' || $pass === '') {
    die("Database connection failed. Missing database configuration.");
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed. Please check your RDS endpoint and credentials.");
}

if (!function_exists('logAudit')) {
    function logAudit($pdo, $user_id, $action_type, $action_description) {
        if (empty($user_id)) {
            return false;
        }

        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt = $pdo->prepare("INSERT INTO audit_log (user_id, action_type, action_description, ip_address) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$user_id, $action_type, $action_description, $ip_address]);
    }
}
?>
