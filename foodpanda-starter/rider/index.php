<?php
require __DIR__ . '/../bootstrap.php';
requireRole($pdo, 'rider', '/login.php');

if (isset($_GET['logout'])) {
    logoutUser();
    header('Location: /login.php');
    exit;
}

$orders = $pdo->query('SELECT o.*, r.name AS rider_name FROM orders o LEFT JOIN riders r ON r.id = o.rider_id WHERE o.rider_id IS NOT NULL ORDER BY o.id DESC')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accept_order'])) {
    $orderId = (int) ($_POST['order_id'] ?? 0);
    $pdo->prepare('UPDATE orders SET status = :status WHERE id = :id')->execute([
        ':status' => 'accepted_by_rider',
        ':id' => $orderId
    ]);
    $pdo->prepare('INSERT INTO deliveries (order_id, rider_id, status, created_at) VALUES (:order_id, :rider_id, :status, :created_at)')->execute([
        ':order_id' => $orderId,
        ':rider_id' => 1,
        ':status' => 'accepted',
        ':created_at' => date('Y-m-d H:i:s')
    ]);
    $success = 'Order accepted. Please pick it up and complete delivery.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['decline_order'])) {
    $orderId = (int) ($_POST['order_id'] ?? 0);
    $pdo->prepare('UPDATE orders SET status = :status WHERE id = :id')->execute([
        ':status' => 'declined_by_rider',
        ':id' => $orderId
    ]);
    $success = 'Order declined.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_order'])) {
    $orderId = (int) ($_POST['order_id'] ?? 0);
    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK || empty($_FILES['photo']['name'])) {
        $error = 'Please upload a delivery photo before marking the order complete.';
    } else {
        $photoUrl = saveUpload($_FILES['photo'], 'uploads');
        $pdo->prepare('UPDATE orders SET status = :status WHERE id = :id')->execute([
            ':status' => 'delivered',
            ':id' => $orderId
        ]);
        $pdo->prepare('UPDATE deliveries SET status = :status, photo_url = :photo_url, completed_at = :completed_at WHERE order_id = :order_id')->execute([
            ':status' => 'completed',
            ':photo_url' => $photoUrl,
            ':completed_at' => date('Y-m-d H:i:s'),
            ':order_id' => $orderId
        ]);
        $success = 'Order marked complete with proof of delivery.';
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rider App</title>
    <link rel="stylesheet" href="../shared.css">
</head>
<body>
    <div class="shell">
        <nav class="top-nav"><a href="../index.php">← Back home</a> <a href="?logout=1">Logout</a></nav>
        <header class="page-header">
            <div>
                <p class="eyebrow">Rider mobile experience</p>
                <h1>Accept deliveries and upload proof of completion.</h1>
            </div>
        </header>

        <?php if (!empty($error)): ?>
            <div class="alert error"><?= e($error) ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert success"><?= e($success) ?></div>
        <?php endif; ?>

        <?php foreach ($orders as $order): ?>
            <div class="order-card rider-card">
                <div>
                    <h3>Order #<?= (int) $order['id'] ?></h3>
                    <p><?= e($order['customer_name']) ?> • <?= e($order['delivery_address']) ?></p>
                    <p>Status: <strong><?= e($order['status']) ?></strong></p>
                    <p>Total: <?= formatCurrency((float) $order['total_amount']) ?></p>
                </div>
                <div class="actions">
                    <form method="post" class="inline-form">
                        <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                        <button type="submit" name="accept_order" class="primary-btn">Accept</button>
                        <button type="submit" name="decline_order" class="secondary-btn">Decline</button>
                    </form>
                    <form method="post" enctype="multipart/form-data" class="inline-form">
                        <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                        <input type="file" name="photo" accept="image/*" required>
                        <button type="submit" name="complete_order" class="primary-btn">Complete</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
