<?php
require 'db.php';
require 'vendor/autoload.php';
use Kreait\Firebase\Factory;

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $answer1 = $_POST['answer1'];
    $answer2 = $_POST['answer2'];

    try {
        // Get user from database
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Verify security answers
            if (password_verify($answer1, $user['security_answer1']) && 
                password_verify($answer2, $user['security_answer2'])) {
                
                // Reset failed attempts
                $stmt = $pdo->prepare('UPDATE users SET failed_attempts = 0 WHERE email = ?');
                $stmt->execute([$email]);
                
                $_SESSION['user'] = $email;
                header('Location: dashboard.php');
                exit();
            } else {
                $error = "Incorrect security answers.";
            }
        } else {
            $error = "User not found.";
        }
    } catch (Exception $e) {
        $error = "Authentication failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Backup Authentication</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Backup Authentication</h1>
        <?php if (isset($error)): ?>
            <p class="error"><?= $error ?></p>
        <?php endif; ?>
        <form method="POST">
            <label>Email:</label>
            <input type="email" name="email" required>
            <label>Security Answer 1:</label>
            <input type="text" name="answer1" required>
            <label>Security Answer 2:</label>
            <input type="text" name="answer2" required>
            <button type="submit">Authenticate</button>
        </form>
    </div>
</body>
</html>