<?php
session_start();

// Clear refresh token from database
if (isset($_SESSION['user'])) {
    require 'db.php';
    $stmt = $pdo->prepare('UPDATE users SET refresh_token = NULL WHERE email = ?');
    $stmt->execute([$_SESSION['user']]);
}

// Clear session and cookies
session_unset();
session_destroy();
setcookie('refresh_token', '', [
    'expires' => time() - 3600,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST']
]);

header('Location: index.php');
exit();
?>