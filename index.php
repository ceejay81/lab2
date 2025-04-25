<?php
require 'db.php';
require 'vendor/autoload.php';
use Kreait\Firebase\Factory;
use Kreait\Firebase\Exception\AuthException;

session_start();

// Show verification messages if they exist
if (isset($_SESSION['verification_success'])) {
    echo '<p class="success">' . $_SESSION['verification_success'] . '</p>';
    unset($_SESSION['verification_success']);
}
if (isset($_SESSION['verification_error'])) {
    echo '<p class="error">' . $_SESSION['verification_error'] . '</p>';
    unset($_SESSION['verification_error']);
}

if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address";
    } else {
        try {
            // Verify Firebase service account
            $factory = (new Factory)->withServiceAccount('firebase_ias.json');
            $auth = $factory->createAuth();
            
            // Authenticate with Firebase
            $signInResult = $auth->signInWithEmailAndPassword($email, $password);
            
            // Verify user exists in MySQL
            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND firebase_uid = ?');
            $stmt->execute([$email, $signInResult->data()['localId']]);
            $user = $stmt->fetch();
            
            if ($user) {
                if (!$user['verified']) {
                    $error = "Account not verified. Please check your email for verification instructions.";
                } else if (password_verify($password, $user['password'])) {
                    // Reset failed attempts on successful login
                    $stmt = $pdo->prepare('UPDATE users SET failed_attempts = 0 WHERE email = ?');
                    $stmt->execute([$email]);
                    
                    $_SESSION['user'] = $email;
                    header('Location: dashboard.php');
                    exit();
                } else {
                    // Increment failed attempts
                    $stmt = $pdo->prepare('UPDATE users SET failed_attempts = failed_attempts + 1 WHERE email = ?');
                    $stmt->execute([$email]);
                    
                    if ($user['failed_attempts'] >= 2) {
                        header('Location: backup_auth.php');
                        exit();
                    }
                    $error = "Wrong password. Please try again.";
                }
            } else {
                $error = "User not found. Please register first.";
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            if (strpos($e->getMessage(), 'INVALID_LOGIN_CREDENTIALS') !== false) {
                // Check if the account exists in the database
                $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if (!$user) {
                    $error = "Account not found. Please register first.";
                } else {
                    $error = "Invalid email or password. Please check your credentials.";
                }
            } elseif (strpos($e->getMessage(), 'TOO_MANY_ATTEMPTS_TRY_LATER') !== false) {
                $error = "Too many failed attempts. Please try again later.";
            } else {
                $error = "Login failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Login</h1>
        <?php if (isset($error)): ?>
            <p class="error"><?= $error ?></p>
        <?php endif; ?>
        <form method="POST">
            <label>Email:</label>
            <input type="email" name="email" required>
            <label>Password:</label>
            <input type="password" name="password" required>
            <button type="submit">Login</button>
        </form>
        <p>Don't have an account? <a href="register.php">Register here</a></p>
        <p>Having trouble logging in? <a href="backup_auth.php">Use backup authentication</a></p>
    </div>
</body>
</html>