<?php
$pdo = require __DIR__ . '/db.php';
$stmt = $pdo->prepare('UPDATE users SET role = :role WHERE email = :email');
$stmt->execute([
    ':role' => 'administrator',
    ':email' => 'orpilla.romel19@gmail.com',
]);
echo $stmt->rowCount();
