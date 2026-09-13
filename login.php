<?php
require __DIR__ . '/init.php';

if (current_user() !== null) {
    redirect('books.php');
}

$email = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errors[] = 'Email and password are required.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT id, password_hash, role FROM users WHERE email = :email');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $errors[] = 'Email or password is incorrect.';
        }
    }

    if (empty($errors)) {
        $_SESSION['user_id'] = $user['id'];
        set_flash('Welcome back!');
        redirect('index.php');
    }
}

function render_error_list(array $errors): string
{
    $items = '';
    foreach ($errors as $error) {
        $items .= '<li>' . escape_html($error) . '</li>';
    }
    return '<ul>' . $items . '</ul>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Jacobo Z. Gonzales Memorial School of Arts and Trades</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="admin-page auth-page">
<div class="container admin-layout auth-layout">
    <header class="site-header">
        <a class="site-brand" href="index.php">
            <img src="jzgmsat logo.jpg" alt="School logo" class="site-brand__mark" style="width:56px;height:56px;object-fit:cover;border-radius:12px;">
            <div>
                <div class="site-title">Jacobo Z. Gonzales Memorial School of Arts and Trades</div>
                <div style="font-size:0.95rem;color:#6b7280;">Sign in to download books.</div>
            </div>
        </a>
        <nav class="nav-links">
            <a class="nav-link" href="register.php">Register</a>
        </nav>
    </header>

    <main class="card form-card">
        <div class="form-card__header">
            <div class="form-card__eyebrow">Secure access</div>
            <h1>Welcome back</h1>
            <p class="form-card__intro">Sign in to open your library, download books, and keep track of your reading history.</p>
        </div>
        <?php if (!empty($errors)): ?>
            <div class="alert">
                <?= render_error_list($errors) ?>
            </div>
        <?php endif; ?>

        <form method="post" novalidate>
            <div class="form-field">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="<?= escape_html($email) ?>" required>
            </div>
            <div class="form-field">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" required>
            </div>
            <div class="form-actions">
                <button class="button button--primary" type="submit">Login</button>
                <a class="nav-link" href="index.php">Back to home</a>
            </div>
        </form>
    </main>

    <p class="footer">Need an account? <a href="register.php">Register now</a>.</p>
</div>
</body>
</html>
