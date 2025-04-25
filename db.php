<?php
$host = 'localhost';
$db   = 'ias2_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
try {
     $pdo = new PDO($dsn, $user, $pass, $options);
     error_log("Database connection successful"); // Add this for debugging
} catch (\PDOException $e) {
     error_log("Database connection failed: " . $e->getMessage()); // Add this for debugging
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>