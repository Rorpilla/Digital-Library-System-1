<?php
require __DIR__ . '/init.php';
require_login();
$user = current_user();

$flash = get_flash();
$errors = [];

$stmt = $pdo->query('SELECT id, name, email, role, created_at FROM users ORDER BY name');
$accounts = $stmt->fetchAll();
$accountRoleMap = [];
foreach ($accounts as $account) {
    $accountRoleMap[(int) $account['id']] = $account['role'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_user_id'])) {
        $deleteUserId = filter_input(INPUT_POST, 'delete_user_id', FILTER_VALIDATE_INT);
        if ($deleteUserId !== false && $deleteUserId !== null && (int) $deleteUserId !== (int) $user['id']) {
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
            $stmt->execute([':id' => $deleteUserId]);
            set_flash('User deleted successfully.');
            redirect('accounts.php');
        }

        set_flash('You cannot delete your own account.');
        redirect('accounts.php');
    }

    $accountIds = $_POST['account_id'] ?? [];
    $roles = $_POST['role'] ?? [];
    $saved = 0;

    if (!is_array($accountIds) || !is_array($roles)) {
        $errors[] = 'Invalid form submission.';
    } else {
        foreach ($accountIds as $index => $accountIdValue) {
            $accountId = filter_var($accountIdValue, FILTER_VALIDATE_INT);
            $newRole = strtolower(trim($roles[$index] ?? 'student'));
            $currentRole = $accountRoleMap[$accountId] ?? null;

            if ($accountId === false || $accountId === null || $currentRole === null) {
                continue;
            }

            if (!in_array($newRole, ['student', 'staff', 'administrator'], true)) {
                continue;
            }

            if ($currentRole === $newRole) {
                continue;
            }

            $stmt = $pdo->prepare('UPDATE users SET role = :role WHERE id = :id');
            $stmt->execute([
                ':role' => $newRole,
                ':id' => $accountId,
            ]);
            $saved++;
        }

        if ($saved > 0) {
            set_flash('User roles updated successfully.');
            redirect('accounts.php');
        }

        set_flash('No role changes were made.');
        redirect('accounts.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Accounts | Jacobo Z. Gonzales Memorial School of Arts and Trades</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="admin-page">
<div class="container admin-layout">
    <header class="site-header">
        <a class="site-brand" href="index.php">
            <img src="jzgmsat logo.jpg" alt="School logo" class="site-brand__mark" style="width:56px;height:56px;object-fit:cover;border-radius:12px;">
            <div>
                <div class="site-title">User Accounts</div>
                <div class="site-subtitle">View the registered users in this library.</div>
            </div>
        </a>
        <nav class="nav-links">
            <?php if (is_admin($user)): ?>
                <a class="nav-link" href="library.php">Manage books</a>
                <a class="nav-link" href="register.php">Create account</a>
            <?php endif; ?>
            <a class="nav-link nav-link--button" href="logout.php">Logout</a>
        </nav>
    </header>

    <main class="card">
        <div class="form-card__header">
            <div class="form-card__eyebrow">Account control</div>
            <h1>Manage library accounts</h1>
            <p class="form-card__intro">Adjust roles, review registrations, and remove inactive accounts from the library.</p>
        </div>

        <?php if ($flash): ?>
            <div class="alert"><?= escape_html($flash) ?></div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= escape_html($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" style="display:block;">
            <div class="results-header">
                <h2>Registered users</h2>
                <p>Showing <?= count($accounts) ?> account<?= count($accounts) === 1 ? '' : 's' ?>.</p>
                <div class="form-actions" style="margin-top:12px;">
                    <button class="button button--primary" type="submit">Save all changes</button>
                </div>
            </div>

            <?php if (!empty($accounts)): ?>
                <div class="grid" style="grid-template-columns: 1fr; gap: 14px;">
                    <?php foreach ($accounts as $account): ?>
                        <div class="book-card">
                            <div class="book-card__meta">
                                <span><?= escape_html($account['name']) ?></span>
                                <span><?= escape_html(ucfirst($account['role'])) ?></span>
                            </div>
                            <h3 class="book-card__title"><?= escape_html($account['email']) ?></h3>
                            <p class="book-card__description">Registered on <?= escape_html((new DateTimeImmutable($account['created_at']))->format('M j, Y H:i')) ?></p>
                            <div class="form-actions" style="margin-top:10px;justify-content:flex-start;">
                                <input type="hidden" name="account_id[]" value="<?= (int) $account['id'] ?>">
                                <label for="role-<?= (int) $account['id'] ?>" style="font-size:0.95rem;">Role</label>
                                <select id="role-<?= (int) $account['id'] ?>" name="role[]">
                                    <option value="student" <?= ($account['role'] === 'student' ? 'selected' : '') ?>>Student</option>
                                    <option value="staff" <?= ($account['role'] === 'staff' ? 'selected' : '') ?>>Staff</option>
                                    <option value="administrator" <?= ($account['role'] === 'administrator' ? 'selected' : '') ?>>Administrator</option>
                                </select>
                                <?php if ((int) $account['id'] !== (int) $user['id']): ?>
                                    <button class="button button--secondary" type="submit" name="delete_user_id" value="<?= (int) $account['id'] ?>" onclick="return confirm('Delete this user account?');">Delete</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">No accounts found.</div>
            <?php endif; ?>
        </form>
    </main>

    <footer class="footer">Logged in as <?= escape_html($user['name']) ?>.</footer>
</div>
</body>
</html>
