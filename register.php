<?php
require 'db.php';
require 'vendor/autoload.php';
use Kreait\Firebase\Factory;
use Kreait\Firebase\Exception\AuthException;

session_start();

// Security Headers
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; frame-ancestors 'none'");
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Initialize registration attempts if not set
    if (!isset($_SESSION['registration_attempts'])) {
        $_SESSION['registration_attempts'] = 0;
        $_SESSION['first_attempt_time'] = time();
    }
    
    // Reset attempts after 1 hour
    if (time() - $_SESSION['first_attempt_time'] > 3600) {
        $_SESSION['registration_attempts'] = 0;
        $_SESSION['first_attempt_time'] = time();
    }
    
    // Check if too many attempts
    if ($_SESSION['registration_attempts'] >= 5) {
        $error = "Too many registration attempts. Please try again in an hour.";
        exit();
    }
    
    $_SESSION['registration_attempts']++;
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || 
        $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid request";
        exit();
    }
    unset($_SESSION['csrf_token']);
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
    } else if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password)) {
        $error = "Password must be at least 8 characters and include uppercase, lowercase, numbers and special characters";
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
            // Hash answers instead of encrypting them
            $hashedAnswer1 = password_hash(strtolower(trim($answer1)), PASSWORD_BCRYPT);
            $hashedAnswer2 = password_hash(strtolower(trim($answer2)), PASSWORD_BCRYPT);
            
            $stmt = $pdo->prepare('INSERT INTO users (email, firebase_uid, password, security_question1, security_answer1, security_question2, security_answer2, verification_token, verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)');
            $stmt->execute([$email, $user->uid, $hashedPassword, $question1, $hashedAnswer1, $question2, $hashedAnswer2, $verificationToken]);
            
            // Send email verification with callback URL
            $verificationUrl = "https://" . $_SERVER['HTTP_HOST'] . "/lab2_web/lab2/verify.php?token=$verificationToken";
            $auth->sendEmailVerificationLink($email, [
                'continueUrl' => $verificationUrl
            ]);
            
            $_SESSION['user'] = $email;
            header('Location: verify.php');
            exit();
        } catch (AuthException $e) {
            error_log("Registration failed for email {$email}: " . $e->getMessage());
            $error = "Registration failed. Please try again later.";
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
            <?php 
            $csrf_token = bin2hex(random_bytes(32));
            $_SESSION['csrf_token'] = $csrf_token;
            ?>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <label>Email:</label>
            <input type="email" name="email" required pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$">
            <label>Password:</label>
            <input type="password" name="password" required minlength="8" 
                   pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$"
                   title="Must contain at least 8 characters, including uppercase, lowercase, numbers and special characters">
            
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