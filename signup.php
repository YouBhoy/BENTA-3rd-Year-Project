<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/lib/utils.php';
require_once __DIR__ . '/lib/auth.php';

start_app_session();

$errors = [];
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $business = $_POST['business_name'] ?? '';

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required';
    }
    if (!$password || strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters';
    }
    if (!$business) {
        $errors[] = 'Business name is required';
    }

    if (!$errors) {
        if (signup_user($email, $password, $business)) {
            // Auto-login after signup for convenience
            login_user($email, $password);
            redirect('items.php');
        } else {
            $errors[] = 'Signup failed. Email may already be in use.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup - BENTA</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <h1>Create Account</h1>
    <?php if ($errors): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $err): ?>
                    <li><?php echo e($err); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <form method="post" action="">
        <label>Email
            <input type="email" name="email" required>
        </label>
        <label>Password
            <input type="password" name="password" minlength="8" required>
        </label>
        <label>Business Name
            <input type="text" name="business_name" required>
        </label>
        <button type="submit">Sign Up</button>
    </form>
    <p>Already have an account? <a href="login.php">Login</a></p>
    <script src="assets/app.js"></script>
</body>
<style>
    body{font-family:Arial,Helvetica,sans-serif;max-width:480px;margin:40px auto;padding:0 16px}
    form{display:flex;flex-direction:column;gap:12px}
    label{display:flex;flex-direction:column;gap:6px}
    input{padding:10px;border:1px solid #ccc;border-radius:6px}
    button{padding:10px 14px;border:0;background:#1f7aec;color:#fff;border-radius:6px;cursor:pointer}
    .alert-error{background:#ffe8e8;border:1px solid #ff9b9b;padding:10px;border-radius:6px;margin-bottom:12px}
    a{color:#1f7aec}
</style>
</html>


