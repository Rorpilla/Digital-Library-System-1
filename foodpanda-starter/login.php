<?php
require __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $redirect = $user['role'] === 'merchant' ? '/merchant/index.php' : '/rider/index.php';
        header('Location: ' . $redirect);
        exit;
    }

    $error = 'Invalid email or password.';
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in</title>
    <link rel="stylesheet" href="shared.css">
</head>
<body>
    <div class="shell">
        <div class="hero">
            <p class="eyebrow">Secure portal</p>
            <h1>Sign in to your merchant or rider account.</h1>
            <p>Demo accounts: merchant@example.com / password123 and rider@example.com / password123</p>
        </div>
        <?php if (!empty($error)): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
        <form method="post" class="panel">
            <label><span>Email</span><input type="email" name="email" required></label>
            <label><span>Password</span><input type="password" name="password" required></label>
            <button type="submit" name="login" class="primary-btn">Sign in</button>
        </form>
    </div>
</body>
</html>
