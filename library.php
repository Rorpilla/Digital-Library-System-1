<?php
require __DIR__ . '/init.php';
$user = current_user();
$admin = is_admin($user);

$flash = get_flash();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $admin) {
    $bookId = filter_input(INPUT_POST, 'delete_book_id', FILTER_VALIDATE_INT);
    if ($bookId !== false && $bookId !== null) {
        $stmt = $pdo->prepare('SELECT file, cover FROM books WHERE id = :id');
        $stmt->execute([':id' => $bookId]);
        $book = $stmt->fetch();

        if ($book) {
            $targetDir = __DIR__ . '/books';
            if (!empty($book['file']) && file_exists($targetDir . '/' . $book['file'])) {
                unlink($targetDir . '/' . $book['file']);
            }
            if (!empty($book['cover']) && file_exists($targetDir . '/' . $book['cover'])) {
                unlink($targetDir . '/' . $book['cover']);
            }

            $stmt = $pdo->prepare('DELETE FROM books WHERE id = :id');
            $stmt->execute([':id' => $bookId]);
            set_flash('Book deleted successfully.');
            redirect('library.php');
        }
    }
}

$stmt = $pdo->query('SELECT id, title, author, description, file, cover FROM books ORDER BY title');
$books = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library | Jacobo Z. Gonzales Memorial School of Arts and Trades</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="admin-page library-page">
<div class="container admin-layout library-layout">
    <header class="site-header">
        <a class="site-brand" href="index.php">
            <img src="jzgmsat logo.jpg" alt="School logo" class="site-brand__mark" style="width:56px;height:56px;object-fit:cover;border-radius:12px;">
            <div>
                <div class="site-title">Jacobo Z. Gonzales Memorial School of Arts and Trades</div>
                <div class="site-subtitle">Discover books, download PDFs, and keep learning.</div>
            </div>
        </a>
        <nav class="nav-links">
            <?php if ($user !== null): ?>
                <?php if ($admin): ?>
                    <a class="nav-link" href="books.php">Add books</a>
                <?php endif; ?>
                <a class="nav-link nav-link--button" href="logout.php">Logout</a>
            <?php else: ?>
                <a class="nav-link" href="login.php">Login</a>
            <?php endif; ?>
        </nav>
    </header>

    <section class="card">
        <div class="form-card__header">
            <div class="form-card__eyebrow">Library catalog</div>
            <h1>Browse the full collection</h1>
            <p class="form-card__intro">Open books in the browser, download PDFs, and manage titles from one place.</p>
        </div>

        <?php if ($flash): ?>
            <div class="alert"><?= escape_html($flash) ?></div>
        <?php endif; ?>

        <div class="results-header">
            <h2>All available books</h2>
            <p>Showing <?= count($books) ?> book<?= count($books) === 1 ? '' : 's' ?> from the library.</p>
        </div>

        <?php if (!empty($books)): ?>
            <div class="grid">
                <?php foreach ($books as $book): ?>
                    <article class="book-card">
                        <?php if (!empty($book['cover'])): ?>
                            <img class="book-cover-image" src="books/<?= escape_html($book['cover']) ?>" alt="Cover for <?= escape_html($book['title']) ?>">
                        <?php else: ?>
                            <div class="book-cover-preview">
                                <span><?= strtoupper(substr($book['title'], 0, 1)) ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="book-card__meta">
                            <span><?= escape_html($book['author']) ?></span>
                            <span>PDF</span>
                        </div>
                        <h3 class="book-card__title"><?= escape_html($book['title']) ?></h3>
                        <p class="book-card__description"><?= escape_html($book['description']) ?></p>
                        <?php if ($user !== null): ?>
                            <div class="form-actions library-actions">
                                <a class="button button--primary" href="view.php?id=<?= $book['id'] ?>">Open book</a>
                                <?php if ($admin): ?>
                                    <a class="button button--secondary" href="edit_book.php?id=<?= $book['id'] ?>">Edit</a>
                                    <form method="post" style="display:inline;" onsubmit="return confirm('Delete this book?');">
                                        <input type="hidden" name="delete_book_id" value="<?= (int) $book['id'] ?>">
                                        <button class="button button--secondary" type="submit">Delete</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <a class="button button--secondary" href="login.php">Login to open this book</a>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                No books have been uploaded yet. Please check back soon.
            </div>
        <?php endif; ?>
    </section>

    <footer class="footer">Browse the full library and open any title directly from here.</footer>
</div>
</body>
</html>
