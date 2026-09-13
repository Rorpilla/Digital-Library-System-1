<?php
require __DIR__ . '/../bootstrap.php';
requireRole($pdo, 'merchant', '/login.php');

if (isset($_GET['logout'])) {
    logoutUser();
    header('Location: /login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $stmt = $pdo->prepare('INSERT INTO products (merchant_id, name, description, price, stock, category, image_url) VALUES (:merchant_id, :name, :description, :price, :stock, :category, :image_url)');
    $stmt->execute([
        ':merchant_id' => 1,
        ':name' => trim($_POST['name'] ?? ''),
        ':description' => trim($_POST['description'] ?? ''),
        ':price' => (float) ($_POST['price'] ?? 0),
        ':stock' => (int) ($_POST['stock'] ?? 0),
        ':category' => trim($_POST['category'] ?? ''),
        ':image_url' => trim($_POST['image_url'] ?? '')
    ]);
    $success = 'Product added successfully.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_rider'])) {
    $orderId = (int) ($_POST['order_id'] ?? 0);
    $riderId = (int) ($_POST['rider_id'] ?? 0);
    $pdo->prepare('UPDATE orders SET rider_id = :rider_id, status = :status WHERE id = :id')->execute([
        ':rider_id' => $riderId,
        ':status' => 'assigned_to_rider',
        ':id' => $orderId
    ]);

    $pdo->prepare('INSERT INTO notifications (user_type, user_id, message, created_at) VALUES (:user_type, :user_id, :message, :created_at)')->execute([
        ':user_type' => 'rider',
        ':user_id' => $riderId,
        ':message' => 'A new delivery order is waiting for you.',
        ':created_at' => date('Y-m-d H:i:s')
    ]);

    $success = 'Rider assigned successfully.';
}

$products = $pdo->query('SELECT * FROM products ORDER BY id DESC')->fetchAll();
$orders = $pdo->query('SELECT o.*, r.name AS rider_name FROM orders o LEFT JOIN riders r ON r.id = o.rider_id ORDER BY o.id DESC')->fetchAll();
$riders = $pdo->query('SELECT * FROM riders ORDER BY id')->fetchAll();
$summary = $pdo->query('SELECT COUNT(*) AS sales_count, COALESCE(SUM(total_amount), 0) AS total_sales FROM orders WHERE status = "delivered"')->fetch();
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Merchant Dashboard</title>
    <link rel="stylesheet" href="../shared.css">
</head>
<body>
    <div class="shell">
        <nav class="top-nav"><a href="../index.php">← Back home</a> <a href="?logout=1">Logout</a></nav>
        <header class="page-header">
            <div>
                <p class="eyebrow">Merchant web dashboard</p>
                <h1>Manage products, orders, and sales.</h1>
            </div>
        </header>

        <?php if (!empty($success)): ?>
            <div class="alert success"><?= e($success) ?></div>
        <?php endif; ?>

        <section class="panel">
            <h2>Add product</h2>
            <form method="post" class="stacked-form">
                <div class="row">
                    <label><span>Name</span><input type="text" name="name" required></label>
                    <label><span>Category</span><input type="text" name="category" required></label>
                </div>
                <div class="row">
                    <label><span>Price</span><input type="number" step="0.01" name="price" required></label>
                    <label><span>Stock</span><input type="number" name="stock" required></label>
                </div>
                <label><span>Description</span><textarea name="description" rows="3"></textarea></label>
                <label><span>Image URL</span><input type="text" name="image_url"></label>
                <button type="submit" name="add_product" class="primary-btn">Save product</button>
            </form>
        </section>

        <section class="panel">
            <h2>Sales summary</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <strong><?= (int) ($summary['sales_count'] ?? 0) ?></strong>
                    <span>Completed orders</span>
                </div>
                <div class="stat-card">
                    <strong><?= formatCurrency((float) ($summary['total_sales'] ?? 0)) ?></strong>
                    <span>Total sales</span>
                </div>
            </div>
        </section>

        <section class="panel">
            <h2>Products</h2>
            <div class="product-list">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <div>
                            <h3><?= e($product['name']) ?></h3>
                            <p><?= e($product['description']) ?></p>
                            <strong><?= formatCurrency((float) $product['price']) ?></strong>
                        </div>
                        <span class="pill">Stock: <?= (int) $product['stock'] ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="panel">
            <h2>Incoming orders</h2>
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div>
                        <h3>Order #<?= (int) $order['id'] ?></h3>
                        <p><?= e($order['customer_name']) ?> • <?= e($order['delivery_address']) ?></p>
                        <p>Status: <strong><?= e($order['status']) ?></strong></p>
                        <p>Total: <?= formatCurrency((float) $order['total_amount']) ?></p>
                    </div>
                    <form method="post" class="assign-form">
                        <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                        <select name="rider_id">
                            <?php foreach ($riders as $rider): ?>
                                <option value="<?= (int) $rider['id'] ?>" <?= ((int) $order['rider_id'] === (int) $rider['id']) ? 'selected' : '' ?>><?= e($rider['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="assign_rider" class="primary-btn">Assign rider</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </section>
    </div>
</body>
</html>
