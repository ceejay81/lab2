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
    
    // Check if the account is locked
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Check if account is locked
        if ($user['lockout_until'] !== null && strtotime($user['lockout_until']) > time()) {
            $waitTime = ceil((strtotime($user['lockout_until']) - time()) / 60);
            $error = "Account is locked. Please try again after {$waitTime} minutes.";
        } else {
            try {
                // Reset lockout if it's expired
                if ($user['lockout_until'] !== null && strtotime($user['lockout_until']) <= time()) {
                    $stmt = $pdo->prepare('UPDATE users SET login_attempts = 0, lockout_until = NULL WHERE email = ?');
                    $stmt->execute([$email]);
                }
                
                // Verify Firebase service account
                $factory = (new Factory)->withServiceAccount('firebase_ias.json');
                $auth = $factory->createAuth();
                
                // Authenticate with Firebase
                $signInResult = $auth->signInWithEmailAndPassword($email, $password);
                
                if (password_verify($password, $user['password'])) {
                    // Reset login attempts on successful login
                    $stmt = $pdo->prepare('UPDATE users SET login_attempts = 0, last_attempt_time = NULL, lockout_until = NULL WHERE email = ?');
                    $stmt->execute([$email]);
                    
                    // Set secure session
                    $_SESSION['user'] = $email;
                    $_SESSION['last_activity'] = time();
                    
                    // Set refresh token
                    $refreshToken = bin2hex(random_bytes(32));
                    $stmt = $pdo->prepare('UPDATE users SET refresh_token = ? WHERE email = ?');
                    $stmt->execute([$refreshToken, $email]);
                    
                    // Set secure cookie
                    setcookie('refresh_token', $refreshToken, [
                        'expires' => time() + 86400 * 30, // 30 days
                        'path' => '/',
                        'domain' => $_SERVER['HTTP_HOST'],
                        'secure' => true,
                        'httponly' => true,
                        'samesite' => 'Strict'
                    ]);
                    
                    header('Location: dashboard.php');
                    exit();
                } else {
                    // Increment login attempts
                    $newAttempts = $user['login_attempts'] + 1;
                    $lockoutUntil = null;
                    
                    // Implement progressive lockout
                    if ($newAttempts >= 5) {
                        // Lock for 30 minutes after 5 attempts
                        $lockoutUntil = date('Y-m-d H:i:s', strtotime('+30 minutes'));
                    } else if ($newAttempts >= 3) {
                        // Lock for 15 minutes after 3 attempts
                        $lockoutUntil = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                    }
                    
                    $stmt = $pdo->prepare('UPDATE users SET login_attempts = ?, last_attempt_time = NOW(), lockout_until = ? WHERE email = ?');
                    $stmt->execute([$newAttempts, $lockoutUntil, $email]);
                    
                    if ($lockoutUntil) {
                        $waitTime = ($newAttempts >= 5) ? '30' : '15';
                        $error = "Account locked for {$waitTime} minutes due to multiple failed attempts.";
                    } else {
                        $remainingAttempts = 3 - $newAttempts;
                        $error = "Invalid credentials. {$remainingAttempts} attempts remaining before lockout.";
                    }
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
    } else {
        // Generic error message to prevent user enumeration
        $error = "Invalid email or password.";
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