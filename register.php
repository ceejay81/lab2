<?php
require 'db.php';
require 'vendor/autoload.php';
use Kreait\Firebase\Factory;
use Kreait\Firebase\Exception\AuthException;

session_start();
if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $question1 = $_POST['question1'];
    $answer1 = $_POST['answer1'];
    $question2 = $_POST['question2'];
    $answer2 = $_POST['answer2'];
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address";
    } else if (empty($question1) || empty($answer1) || empty($question2) || empty($answer2)) {
        $error = "Please complete all security questions";
    } else {
        $factory = (new Factory)->withServiceAccount('firebase_ias.json');
        $auth = $factory->createAuth();
        
        try {
            // Create Firebase user
            $user = $auth->createUserWithEmailAndPassword($email, $password);
            
            // Generate verification token
            $verificationToken = bin2hex(random_bytes(32));
            
            // Store user data in MySQL with verification token
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $hashedAnswer1 = password_hash($answer1, PASSWORD_BCRYPT);
            $hashedAnswer2 = password_hash($answer2, PASSWORD_BCRYPT);
            
            $stmt = $pdo->prepare('INSERT INTO users (email, firebase_uid, password, security_question1, security_answer1, security_question2, security_answer2, verification_token, verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)');
            $stmt->execute([$email, $user->uid, $hashedPassword, $question1, $hashedAnswer1, $question2, $hashedAnswer2, $verificationToken]);
            
            // Send email verification with callback URL
            $verificationUrl = "http://localhost/lab2_web/lab2/verify.php?token=$verificationToken";
            $auth->sendEmailVerificationLink($email, [
                'continueUrl' => $verificationUrl
            ]);
            
            $_SESSION['user'] = $email;
            header('Location: verify.php');
            exit();
        } catch (AuthException $e) {
            $error = "Registration failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Register</h1>
        <?php if (isset($error)): ?>
            <p class="error"><?= $error ?></p>
        <?php endif; ?>
        <form method="POST">
            <label>Email:</label>
            <input type="email" name="email" required>
            <label>Password:</label>
            <input type="password" name="password" required minlength="6">
            
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
            
            <button type="submit">Register</button>
        </form>
        <p>Already have an account? <a href="index.php">Login here</a></p>
    </div>
</body>
</html>