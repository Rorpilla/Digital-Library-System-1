<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$dbDir = __DIR__ . '/data';
if (!is_dir($dbDir)) {
    mkdir($dbDir, 0755, true);
}

$dbPath = $dbDir . '/foodpanda.sqlite';
$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$pdo->exec('PRAGMA foreign_keys = ON');

initializeSchema($pdo);
seedData($pdo);

function initializeSchema(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            role TEXT NOT NULL,
            created_at TEXT NOT NULL
        )");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS merchants (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            shop_name TEXT NOT NULL,
            phone TEXT,
            address TEXT
        )");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS riders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            phone TEXT,
            vehicle TEXT,
            available INTEGER NOT NULL DEFAULT 1
        )");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            merchant_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            description TEXT,
            price REAL NOT NULL DEFAULT 0,
            stock INTEGER NOT NULL DEFAULT 0,
            image_url TEXT,
            category TEXT,
            is_available INTEGER NOT NULL DEFAULT 1,
            FOREIGN KEY (merchant_id) REFERENCES merchants(id)
        )");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            merchant_id INTEGER NOT NULL,
            rider_id INTEGER,
            customer_name TEXT NOT NULL,
            customer_phone TEXT,
            delivery_address TEXT NOT NULL,
            total_amount REAL NOT NULL DEFAULT 0,
            status TEXT NOT NULL DEFAULT 'pending',
            created_at TEXT NOT NULL,
            FOREIGN KEY (merchant_id) REFERENCES merchants(id),
            FOREIGN KEY (rider_id) REFERENCES riders(id)
        )");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS order_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id INTEGER NOT NULL,
            product_id INTEGER NOT NULL,
            quantity INTEGER NOT NULL DEFAULT 1,
            price REAL NOT NULL DEFAULT 0,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id)
        )");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS deliveries (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id INTEGER NOT NULL,
            rider_id INTEGER NOT NULL,
            status TEXT NOT NULL DEFAULT 'assigned',
            photo_url TEXT,
            created_at TEXT NOT NULL,
            completed_at TEXT,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (rider_id) REFERENCES riders(id)
        )");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS notifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_type TEXT NOT NULL,
            user_id INTEGER NOT NULL,
            message TEXT NOT NULL,
            is_read INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL
        )");
}

function seedData(PDO $pdo): void
{
    $merchantCount = (int) $pdo->query('SELECT COUNT(*) FROM merchants')->fetchColumn();
    $userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

    if ($merchantCount === 0) {
        $pdo->exec("INSERT INTO merchants (id, name, shop_name, phone, address) VALUES (1, 'Mina Reyes', 'Green Basket', '09170000001', 'Cebu City')");
        $pdo->exec("INSERT INTO merchants (id, name, shop_name, phone, address) VALUES (2, 'Rene Cruz', 'Fresh Corner', '09170000002', 'Mandaue City')");
        $pdo->exec("INSERT INTO riders (id, name, phone, vehicle, available) VALUES (1, 'Alex Santos', '09180000001', 'Motorcycle', 1)");
        $pdo->exec("INSERT INTO riders (id, name, phone, vehicle, available) VALUES (2, 'Jessa Lim', '09180000002', 'Bicycle', 1)");
        $pdo->exec("INSERT INTO products (merchant_id, name, description, price, stock, image_url, category) VALUES (1, 'Chicken Rice Bowl', 'Flavorful chicken bowl with rice and vegetables', 120, 20, 'https://images.unsplash.com/photo-1512058564366-18510be2db19?auto=format&fit=crop&w=800&q=80', 'Meals')");
        $pdo->exec("INSERT INTO products (merchant_id, name, description, price, stock, image_url, category) VALUES (1, 'Iced Coffee', 'Chilled coffee with creamy foam', 80, 30, 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=800&q=80', 'Beverages')");
        $pdo->exec("INSERT INTO products (merchant_id, name, description, price, stock, image_url, category) VALUES (2, 'Fresh Fruit Cup', 'Healthy mix of fruits and yogurt', 95, 15, 'https://images.unsplash.com/photo-1464965911861-746a04bca7f5?auto=format&fit=crop&w=800&q=80', 'Snacks')");
    }

    if ($userCount === 0) {
        $pdo->prepare('INSERT INTO users (name, email, password_hash, role, created_at) VALUES (:name, :email, :password_hash, :role, :created_at)')->execute([
            ':name' => 'Merchant Demo',
            ':email' => 'merchant@example.com',
            ':password_hash' => password_hash('password123', PASSWORD_DEFAULT),
            ':role' => 'merchant',
            ':created_at' => date('Y-m-d H:i:s')
        ]);
        $pdo->prepare('INSERT INTO users (name, email, password_hash, role, created_at) VALUES (:name, :email, :password_hash, :role, :created_at)')->execute([
            ':name' => 'Rider Demo',
            ':email' => 'rider@example.com',
            ':password_hash' => password_hash('password123', PASSWORD_DEFAULT),
            ':role' => 'rider',
            ':created_at' => date('Y-m-d H:i:s')
        ]);
    }
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function formatCurrency(float $value): string
{
    return '₱' . number_format($value, 2);
}

function saveUpload(array $file, string $folder): string
{
    $uploadDir = __DIR__ . '/' . $folder;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('upload_', true) . '.' . $extension;
    $target = $uploadDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        throw new RuntimeException('Unable to save uploaded file.');
    }

    return $folder . '/' . $filename;
}

function currentUser(PDO $pdo): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id');
    $stmt->execute([':id' => (int) $_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

function requireRole(PDO $pdo, string $role, string $redirect): void
{
    $user = currentUser($pdo);
    if ($user === null || ($user['role'] ?? '') !== $role) {
        $_SESSION['auth_error'] = 'Please sign in as a ' . $role . ' to continue.';
        header('Location: ' . $redirect);
        exit;
    }
}

function logoutUser(): void
{
    session_unset();
    session_destroy();
}
