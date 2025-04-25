<?php
require 'db.php';
require 'vendor/autoload.php';
use Kreait\Firebase\Factory;

session_start();
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

$factory = (new Factory)->withServiceAccount('firebase_ias.json');
$auth = $factory->createAuth();

// Get user data from MySQL
$stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
$stmt->execute([$_SESSION['user']]);
$userData = $stmt->fetch();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Welcome, <?= htmlspecialchars($userData['email']) ?></h1>
        <p>You are now logged in.</p>
        <p>User ID: <?= htmlspecialchars($userData['firebase_uid']) ?></p>
        <a href="logout.php">Logout</a>
    </div>
</body>
</html>