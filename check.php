<?php
$pdo = new PDO('sqlite:data/library.sqlite');
$stmt = $pdo->query('SELECT name FROM sqlite_master WHERE type="table"');
print_r($stmt->fetchAll());
