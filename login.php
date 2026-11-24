<?php
session_start();
require_once __DIR__ . '/db.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identity = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($identity === '' || $password === '') {
        $error = 'Enter your username/email and password.';
    } else {
        $stmt = $conn->prepare('SELECT id, full_name, username, email, password, role, is_active FROM users WHERE username = ? OR email = ? LIMIT 1');
        $stmt->bind_param('ss', $identity, $identity);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($user && (int)$user['is_active'] === 1 && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $destinations = [
                'admin' => 'admin/dashboard.php',
                'customer' => 'customer/home.php',
                'storekeeper' => 'storekeeper/dashboard.php'
            ];
            header('Location: ' . ($destinations[$user['role']] ?? 'login.php'));
            exit;
        }
        $error = ($user && (int)$user['is_active'] !== 1) ? 'This account is inactive. Contact the administrator.' : 'Invalid username/email or password.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | EteteSupermarket</title>
    <link rel="stylesheet" href="assets/css/modern.css">
</head>
<body class="auth-page">
<main class="auth-card">
    <a class="brand" href="index.php">Etete<span>SuperMarket</span></a>
    <h1>Welcome </h1>
    <p class="muted">Sign in to continue to your workspace.</p>
    
    <?php if($error): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <?php if(isset($_GET['disabled'])): ?>
        <div class="alert error">Your account is inactive. Contact the administrator.</div>
    <?php endif; ?>
    
    <?php if(isset($_GET['registered'])): ?>
        <div class="alert success">Account created. Please sign in.</div>
    <?php endif; ?>
    
    <form method="post">
        <label>Username or email</label>
        <input name="username" autocomplete="username" required>
        
        <label>Password</label>
        <input type="password" name="password" autocomplete="current-password" required>
        
        <!-- Forgot Password Link placed nicely above or beside button -->
        <div style="margin-top: 8px; margin-bottom: 15px; text-align: right;">
            <a href="forgot-password.php" style="font-size: 13px; text-decoration: none; color: #0d6efd;">Forgot Password?</a>
        </div>

        <button class="btn primary full" type="submit">Sign in</button>
    </form>
    
    <p class="center" style="margin-top: 20px;">New customer? <a href="register.php">Create an account</a></p>
    <p class="center"><a href="index.php">Back to home</a></p>
</main>
</body>
</html>