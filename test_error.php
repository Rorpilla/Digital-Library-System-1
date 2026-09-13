<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require __DIR__ . '/init.php';
$user = current_user();
echo "User: "; var_dump($user);
try {
    $totalBooks = (int) $pdo->query('SELECT COUNT(*) FROM books')->fetchColumn();
    echo "Total books: $totalBooks\n";
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
