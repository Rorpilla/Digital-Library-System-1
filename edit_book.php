<?php
require __DIR__ . '/init.php';
require_login();
if (!is_admin()) {
    set_flash('Only administrators can edit books.');
    redirect('library.php');
}

$user = current_user();
$errors = [];
$flash = get_flash();

$title = '';
$author = '';
$description = '';

$bookId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($bookId === false || $bookId === null) {
    set_flash('Invalid book selection.');
    redirect('library.php');
}

$stmt = $pdo->prepare('SELECT id, title, author, description, file, cover FROM books WHERE id = :id');
$stmt->execute([':id' => $bookId]);
$book = $stmt->fetch();
if (!$book) {
    set_flash('Book not found.');
    redirect('library.php');
}

$title = $book['title'];
$author = $book['author'];
$description = $book['description'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $coverFile = $_FILES['cover_file'] ?? null;

    if ($title === '' || $author === '' || $description === '') {
        $errors[] = 'Title, author, and description are required.';
    }

    if (empty($errors) && $coverFile && isset($coverFile['tmp_name']) && $coverFile['error'] === UPLOAD_ERR_OK && is_uploaded_file($coverFile['tmp_name'])) {
        $coverExtension = strtolower(pathinfo($coverFile['name'] ?? '', PATHINFO_EXTENSION));
        if (!in_array($coverExtension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $errors[] = 'Cover image must be a JPG, PNG, or WebP file.';
        }
    }

    if (empty($errors)) {
        $targetDir = __DIR__ . '/books';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $newCoverName = $book['cover'];
        if ($coverFile && isset($coverFile['tmp_name']) && $coverFile['error'] === UPLOAD_ERR_OK && is_uploaded_file($coverFile['tmp_name'])) {
            $coverExtension = strtolower(pathinfo($coverFile['name'], PATHINFO_EXTENSION));
            $newCoverName = 'cover-' . $bookId . '-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $coverExtension;
            $coverTargetPath = $targetDir . '/' . $newCoverName;
            if (!move_uploaded_file($coverFile['tmp_name'], $coverTargetPath)) {
                $errors[] = 'The cover image could not be saved.';
            }
        }

        if (empty($errors)) {
            if (!empty($book['cover']) && $newCoverName !== $book['cover'] && file_exists($targetDir . '/' . $book['cover'])) {
                unlink($targetDir . '/' . $book['cover']);
            }

            $stmt = $pdo->prepare('UPDATE books SET title = :title, author = :author, description = :description, cover = :cover WHERE id = :id');
            $stmt->execute([
                ':title' => $title,
                ':author' => $author,
                ':description' => $description,
                ':cover' => $newCoverName,
                ':id' => $bookId,
            ]);
            set_flash('Book updated successfully.');
            redirect('library.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Book | Jacobo Z. Gonzales Memorial School of Arts and Trades</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="admin-page">
<div class="container admin-layout">
    <header class="site-header">
        <a class="site-brand" href="index.php">
            <img src="jzgmsat logo.jpg" alt="School logo" class="site-brand__mark" style="width:56px;height:56px;object-fit:cover;border-radius:12px;">
            <div>
                <div class="site-title">Edit Book</div>
                <div class="site-subtitle">Update the book details and cover image.</div>
            </div>
        </a>
        <nav class="nav-links">
            <a class="nav-link" href="library.php">Back to library</a>
            <a class="nav-link nav-link--button" href="logout.php">Logout</a>
        </nav>
    </header>

    <main class="card form-card">
        <div class="form-card__header">
            <div class="form-card__eyebrow">Book updates</div>
            <h1>Edit <?= escape_html($book['title']) ?></h1>
            <p class="form-card__intro">Update the title, author, description, or replace the cover image for this resource.</p>
        </div>

        <?php if ($flash): ?>
            <div class="alert"><?= escape_html($flash) ?></div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= escape_html($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <div class="form-field">
                <label for="title">Title</label>
                <input id="title" name="title" value="<?= escape_html($title) ?>" required>
            </div>
            <div class="form-field">
                <label for="author">Author</label>
                <input id="author" name="author" value="<?= escape_html($author) ?>" required>
            </div>
            <div class="form-field">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4" required><?= escape_html($description) ?></textarea>
            </div>
            <div class="form-field">
                <label for="cover_file">Replace cover image</label>
                <input id="cover_file" type="file" name="cover_file" accept="image/jpeg,image/png,image/webp">
            </div>
            <div class="form-actions">
                <button class="button button--primary" type="submit">Save changes</button>
                <a class="nav-link" href="library.php">Cancel</a>
            </div>
        </form>
    </main>

    <footer class="footer">Logged in as <?= escape_html($user['name']) ?>.</footer>
</div>
</body>
</html>
