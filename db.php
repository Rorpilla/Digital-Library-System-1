<?php
$databasePath = __DIR__ . '/data/library.sqlite';
$schemaPath = __DIR__ . '/schema.sql';

if (!is_dir(dirname($databasePath))) {
    mkdir(dirname($databasePath), 0755, true);
}

$shouldInitialize = !file_exists($databasePath) || filesize($databasePath) === 0;
$pdo = new PDO('sqlite:' . $databasePath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

if ($shouldInitialize) {
    initialize_database($pdo, $schemaPath);
} else {
    ensure_books_cover_column($pdo);
    ensure_users_role_column($pdo);
    ensure_book_views_table($pdo);
}

return $pdo;

function ensure_books_cover_column(PDO $pdo): void
{
    $columns = $pdo->query("PRAGMA table_info(books)")->fetchAll(PDO::FETCH_ASSOC);
    $hasCoverColumn = false;

    foreach ($columns as $column) {
        if (($column['name'] ?? '') === 'cover') {
            $hasCoverColumn = true;
            break;
        }
    }

    if (!$hasCoverColumn) {
        $pdo->exec('ALTER TABLE books ADD COLUMN cover TEXT');
    }
}

function ensure_users_role_column(PDO $pdo): void
{
    $columns = $pdo->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_ASSOC);
    $hasRoleColumn = false;

    foreach ($columns as $column) {
        if (($column['name'] ?? '') === 'role') {
            $hasRoleColumn = true;
            break;
        }
    }

    if (!$hasRoleColumn) {
        $pdo->exec("ALTER TABLE users ADD COLUMN role TEXT NOT NULL DEFAULT 'student'");
    }
}

function ensure_book_views_table(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS book_views (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        book_id INTEGER NOT NULL,
        viewed_at TEXT NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (book_id) REFERENCES books(id)
    )");

    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_book_views_user_time ON book_views(user_id, viewed_at DESC)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_book_views_book_time ON book_views(book_id, viewed_at DESC)");
}

function initialize_database(PDO $pdo, string $schemaPath): void
{
    if (!file_exists($schemaPath)) {
        throw new RuntimeException('Database schema file missing: ' . $schemaPath);
    }

    $schema = file_get_contents($schemaPath);
    if ($schema === false) {
        throw new RuntimeException('Unable to read schema file: ' . $schemaPath);
    }

    $pdo->exec($schema);
}
