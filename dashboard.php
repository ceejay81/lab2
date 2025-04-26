<?php
require 'db.php';
require 'vendor/autoload.php';
use Kreait\Firebase\Factory;

session_start();

// Check if user session exists
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

// Check session timeout (30 minutes)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit();
}

// Update last activity time
$_SESSION['last_activity'] = time();

$factory = (new Factory)->withServiceAccount('firebase_ias.json');
$auth = $factory->createAuth();

// Get user data from MySQL
$stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
$stmt->execute([$_SESSION['user']]);
$userData = $stmt->fetch(PDO::FETCH_ASSOC);

// Verify user exists and is authenticated
if (!$userData || !isset($userData['email']) || !isset($userData['firebase_uid'])) {
    // Clear invalid session and redirect
    unset($_SESSION['user']);
    header('Location: index.php');
    exit();
}
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