<?php
require __DIR__ . '/init.php';

$currentUser = current_user();
if ($currentUser === null) {
    redirect('login.php');
}
if (!is_admin($currentUser)) {
    set_flash('Only administrators can create accounts.');
    redirect('index.php');
}

$email = '';
$name = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $role = strtolower(trim($_POST['role'] ?? 'student'));
    if (!in_array($role, ['student', 'staff', 'administrator'], true)) {
        $role = 'student';
    }

    if ($name === '') {
        $errors[] = 'Name is required.';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }
    if ($password === '' || strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Password confirmation does not match.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            $errors[] = 'This email is already registered.';
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :password_hash, :role)');
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ':role' => $role,
        ]);

        $_SESSION['user_id'] = $pdo->lastInsertId();
        set_flash('Welcome! Your account has been created.');
        redirect('books.php');
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
    <title>Register | Jacobo Z. Gonzales Memorial School of Arts and Trades</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="admin-page">
<div class="container admin-layout">
    <header class="site-header">
        <a class="site-brand" href="index.php">
            <img src="jzgmsat logo.jpg" alt="School logo" class="site-brand__mark" style="width:56px;height:56px;object-fit:cover;border-radius:12px;">
            <div>
                <div class="site-title">Jacobo Z. Gonzales Memorial School of Arts and Trades</div>
                <div style="font-size:0.95rem;color:#6b7280;">Sign up to access PDFs.</div>
            </div>
        </a>
        <nav class="nav-links">
            <a class="nav-link" href="login.php">Login</a>
        </nav>
    </header>

    <main class="card form-card">
        <div class="form-card__header">
            <div class="form-card__eyebrow">New account</div>
            <h1>Create your account</h1>
            <p class="form-card__intro">Join the digital library to browse materials, view PDFs, and download resources with ease.</p>
        </div>
        <?php if (!empty($errors)): ?>
            <div class="alert">
                <?= render_error_list($errors) ?>
            </div>
        <?php endif; ?>

        <form method="post" novalidate>
            <div class="form-field">
                <label for="name">Name</label>
                <input id="name" name="name" value="<?= escape_html($name) ?>" required>
            </div>
            <div class="form-field">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="<?= escape_html($email) ?>" required>
            </div>
            <div class="form-field">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" required>
            </div>
            <div class="form-field">
                <label for="confirm_password">Confirm Password</label>
                <input id="confirm_password" type="password" name="confirm_password" required>
            </div>
            <div class="form-field">
                <label for="role">Role</label>
                <select id="role" name="role">
                    <option value="student">Student</option>
                    <option value="staff">Staff</option>
                    <option value="administrator">Administrator</option>
                </select>
            </div>
            <div class="form-actions">
                <button class="button button--primary" type="submit">Create account</button>
                <a class="nav-link" href="login.php">Already have an account?</a>
            </div>
        </form>
    </main>

    <p class="footer">Already registered? <a href="login.php">Login here</a>.</p>
</div>
</body>
</html>
