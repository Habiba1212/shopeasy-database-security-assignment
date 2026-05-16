<?php
$host = '127.0.0.1'; 
$db   = 'shopeasy_db';
$user = 'root'; // default XAMPP user
$pass = '';     // default XAMPP password is empty

// Create the PDO connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    // Set PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>