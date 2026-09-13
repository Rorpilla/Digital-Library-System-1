<?php
session_start();
$pdo = require __DIR__ . '/db.php';

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    global $pdo;
    $stmt = $pdo->prepare('SELECT id, name, email, role FROM users WHERE id = :id');
    $stmt->execute([':id' => $_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

function is_admin(?array $user = null): bool
{
    if ($user === null) {
        $user = current_user();
    }

    return ($user['role'] ?? '') === 'administrator';
}

function require_login(): void
{
    if (current_user() === null) {
        header('Location: login.php');
        exit;
    }
}

function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        set_flash('Only administrators can manage books.');
        redirect('index.php');
    }
}

function redirect(string $target): void
{
    header('Location: ' . $target);
    exit;
}

function set_flash(string $message): void
{
    $_SESSION['flash_message'] = $message;
}

function get_flash(): ?string
{
    if (empty($_SESSION['flash_message'])) {
        return null;
    }

    $message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
    return $message;
}

function escape_html(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
