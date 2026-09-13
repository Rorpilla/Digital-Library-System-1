<?php
require __DIR__ . '/../bootstrap.php';

$products = $pdo->query('SELECT * FROM products WHERE is_available = 1 ORDER BY id')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $customerName = trim($_POST['customer_name'] ?? '');
    $customerPhone = trim($_POST['customer_phone'] ?? '');
    $deliveryAddress = trim($_POST['delivery_address'] ?? '');
    $items = [];
    $totalAmount = 0;

    foreach ($products as $product) {
        $quantity = max(0, (int) ($_POST['qty_' . $product['id']] ?? 0));
        if ($quantity > 0) {
            $items[] = ['product' => $product, 'quantity' => $quantity];
            $totalAmount += (float) $product['price'] * $quantity;
        }
    }

    if ($customerName === '' || $deliveryAddress === '' || empty($items)) {
        $error = 'Please enter your details and select at least one item.';
    } else {
        $merchantId = (int) ($products[0]['merchant_id'] ?? 1);
        $stmt = $pdo->prepare('INSERT INTO orders (merchant_id, customer_name, customer_phone, delivery_address, total_amount, status, created_at) VALUES (:merchant_id, :customer_name, :customer_phone, :delivery_address, :total_amount, :status, :created_at)');
        $stmt->execute([
            ':merchant_id' => $merchantId,
            ':customer_name' => $customerName,
            ':customer_phone' => $customerPhone,
            ':delivery_address' => $deliveryAddress,
            ':total_amount' => $totalAmount,
            ':status' => 'pending',
            ':created_at' => date('Y-m-d H:i:s')
        ]);
        $orderId = (int) $pdo->lastInsertId();

        foreach ($items as $item) {
            $product = $item['product'];
            $pdo->prepare('INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (:order_id, :product_id, :quantity, :price)')->execute([
                ':order_id' => $orderId,
                ':product_id' => (int) $product['id'],
                ':quantity' => $item['quantity'],
                ':price' => (float) $product['price']
            ]);
        }

        $pdo->prepare('INSERT INTO notifications (user_type, user_id, message, created_at) VALUES (:user_type, :user_id, :message, :created_at)')->execute([
            ':user_type' => 'merchant',
            ':user_id' => $merchantId,
            ':message' => 'New customer order received.',
            ':created_at' => date('Y-m-d H:i:s')
        ]);

        $success = 'Order placed successfully. The merchant can now assign a rider.';
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer App</title>
    <link rel="stylesheet" href="../shared.css">
</head>
<body>
    <div class="shell">
        <nav class="top-nav"><a href="../index.php">← Back home</a></nav>
        <header class="page-header">
            <div>
                <p class="eyebrow">Customer mobile experience</p>
                <h1>Order your favorites in minutes.</h1>
            </div>
        </header>

        <?php if (!empty($error)): ?>
            <div class="alert error"><?= e($error) ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert success"><?= e($success) ?></div>
        <?php endif; ?>

        <form method="post" class="panel">
            <div class="row">
                <label>
                    <span>Your name</span>
                    <input type="text" name="customer_name" required>
                </label>
                <label>
                    <span>Phone</span>
                    <input type="text" name="customer_phone">
                </label>
            </div>
            <label>
                <span>Delivery address</span>
                <textarea name="delivery_address" rows="3" required></textarea>
            </label>

            <div class="product-list">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <div>
                            <h3><?= e($product['name']) ?></h3>
                            <p><?= e($product['description']) ?></p>
                            <strong><?= formatCurrency((float) $product['price']) ?></strong>
                        </div>
                        <input type="number" name="qty_<?= (int) $product['id'] ?>" min="0" value="0" class="qty-input">
                    </div>
                <?php endforeach; ?>
            </div>

            <button type="submit" name="place_order" class="primary-btn">Place order</button>
        </form>
    </div>
</body>
</html>
