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
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && is_array($user)) {
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
            
            <h3>Security Questions</h3>
            <label>Question 1:</label>
            <select name="question1" required>
                <option value="">Select a question</option>
                <option value="What was your first pet's name?">What was your first pet's name?</option>
                <option value="What city were you born in?">What city were you born in?</option>
                <option value="What is your mother's maiden name?">What is your mother's maiden name?</option>
            </select>
            <label>Answer 1:</label>
            <input type="text" name="answer1" required>
            
            <label>Question 2:</label>
            <select name="question2" required>
                <option value="">Select a question</option>
                <option value="What was your high school mascot?">What was your high school mascot?</option>
                <option value="What is your favorite book?">What is your favorite book?</option>
                <option value="What was your first car?">What was your first car?</option>
            </select>
            <label>Answer 2:</label>
            <input type="text" name="answer2" required>
            
            <button type="submit">Authenticate</button>
        </form>
        <!-- Back Button -->
        <button onclick="window.history.back()">Back</button>
    </div>
</body>
</html>