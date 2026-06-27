<?php
// --- AWS RDS DATABASE CONFIGURATION ---
$host = 'shopeasy-rds-mysql.ccl420w8eagx.us-east-1.rds.amazonaws.com'; 
$db   = 'shopeasy_db';
$user = 'shopeasyadmin'; 
$pass = 'ShopEasyDB2026!';

// Create the PDO connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    // Set PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed. Please check your RDS endpoint and credentials.");
}

// Audit logging function
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