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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_SESSION['user'];
    $token = $_GET['token'] ?? '';

    if ($token) {
        try {
            // Verify token in database
            $stmt = $pdo->prepare('UPDATE users SET verified = 1 WHERE verification_token = ?');
            $stmt->execute([$token]);
            
            if ($stmt->rowCount() > 0) {
                $_SESSION['verification_success'] = "Account verified successfully. Please login.";
                header('Location: index.php');
                exit();
            } else {
                $error = "Invalid or expired verification token.";
            }
        } catch (Exception $e) {
            $error = "Verification failed: " . $e->getMessage();
        }
    } else {
        $error = "No verification token provided.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Verify Email</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Verify Email</h1>
        <?php if (isset($error)): ?>
            <p class="error"><?= $error ?></p>
        <?php endif; ?>
        <p>We've sent a verification email to <?= htmlspecialchars($_SESSION['user']) ?>.</p>
        <form method="POST">
            <button type="submit">Check Verification</button>
        </form>
    </div>
</body>
</html>