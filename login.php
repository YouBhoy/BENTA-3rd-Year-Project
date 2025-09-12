<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/lib/utils.php';
require_once __DIR__ . '/lib/auth.php';

start_app_session();
if (current_user_id()) {
    redirect('items.php');
}

$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (login_user($email, $password)) {
        redirect('items.php');
    } else {
        $error = 'Invalid email or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BENTA</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <h1>Login</h1>
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo e($error); ?></div>
    <?php endif; ?>
    <form method="post" action="">
        <label>Email
            <input type="email" name="email" required>
        </label>
        <label>Password
            <input type="password" name="password" required>
        </label>
        <button type="submit">Login</button>
    </form>
    <p>No account? <a href="signup.php">Create one</a></p>
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


