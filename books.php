<?php
require __DIR__ . '/init.php';
require_login();
$user = current_user();
$admin = is_admin($user);

$flash = get_flash();
$errors = [];
$editBookId = filter_input(INPUT_POST, 'edit_book_id', FILTER_VALIDATE_INT);

if (!$admin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    set_flash('Only administrators can manage books.');
    redirect('books.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upload_book') {
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $uploadedFile = $_FILES['book_file'] ?? null;
    $coverFile = $_FILES['cover_file'] ?? null;

    // try to raise memory limit for handling large uploads (may not affect PHP upload limits)
    @ini_set('memory_limit', '1024M');

    if ($title === '' || $author === '' || $description === '') {
        $errors[] = 'Title, author, and description are required.';
    }

    // Validate upload and provide specific error messages even when tmp file is missing
    if (!$uploadedFile || !isset($uploadedFile['error'])) {
        $errors[] = 'Please choose a valid PDF file to upload.';
    } else {
        switch ($uploadedFile['error']) {
            case UPLOAD_ERR_OK:
                if (!isset($uploadedFile['tmp_name']) || !is_uploaded_file($uploadedFile['tmp_name'])) {
                    $errors[] = 'Upload failed: temporary upload file not found.';
                }
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $errors[] = 'The uploaded file is too large. Server limits: upload_max_filesize=' . ini_get('upload_max_filesize') . ', post_max_size=' . ini_get('post_max_size') . '. Consider increasing these in php.ini or upload smaller files.';
                break;
            case UPLOAD_ERR_PARTIAL:
                $errors[] = 'The uploaded file was only partially uploaded. Please try again.';
                break;
            case UPLOAD_ERR_NO_FILE:
                $errors[] = 'No file was uploaded.';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $errors[] = 'Missing a temporary folder on the server.';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $errors[] = 'Failed to write file to disk on the server.';
                break;
            case UPLOAD_ERR_EXTENSION:
            default:
                $errors[] = 'An unexpected upload error occurred (code ' . intval($uploadedFile['error']) . ').';
                break;
        }
    }

    if (empty($errors)) {
        $extension = strtolower(pathinfo($uploadedFile['name'] ?? '', PATHINFO_EXTENSION));
        if ($extension !== 'pdf') {
            $errors[] = 'Only PDF files are supported.';
        }
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

        $safeName = preg_replace('/[^a-z0-9._-]+/i', '-', pathinfo($uploadedFile['name'], PATHINFO_FILENAME));
        $safeName = trim($safeName, '-_.');
        if ($safeName === '') {
            $safeName = 'book';
        }

        $storedName = $safeName . '-' . time() . '-' . bin2hex(random_bytes(4)) . '.pdf';
        $targetPath = $targetDir . '/' . $storedName;

        if (!move_uploaded_file($uploadedFile['tmp_name'], $targetPath)) {
            $errors[] = 'The file could not be saved. Please try again.';
        }

        $coverName = null;
        if ($coverFile && isset($coverFile['tmp_name']) && $coverFile['error'] === UPLOAD_ERR_OK && is_uploaded_file($coverFile['tmp_name'])) {
            $coverExtension = strtolower(pathinfo($coverFile['name'], PATHINFO_EXTENSION));
            $coverName = $safeName . '-cover-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $coverExtension;
            $coverTargetPath = $targetDir . '/' . $coverName;
            if (!move_uploaded_file($coverFile['tmp_name'], $coverTargetPath)) {
                $errors[] = 'The cover image could not be saved.';
            }
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('INSERT INTO books (title, author, description, file, cover) VALUES (:title, :author, :description, :file, :cover)');
            $stmt->execute([
                ':title' => $title,
                ':author' => $author,
                ':description' => $description,
                ':file' => $storedName,
                ':cover' => $coverName,
            ]);
            set_flash('Book uploaded successfully.');
            redirect('books.php');
        } catch (Throwable $e) {
            if (file_exists($targetPath)) {
                unlink($targetPath);
            }
            $errors[] = 'Unable to save the book right now.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit_book' && $editBookId !== false && $editBookId !== null) {
    $editTitle = trim($_POST['edit_title'] ?? '');
    $editAuthor = trim($_POST['edit_author'] ?? '');
    $editDescription = trim($_POST['edit_description'] ?? '');
    $editCoverFile = $_FILES['edit_cover_file'] ?? null;

    if ($editTitle === '' || $editAuthor === '' || $editDescription === '') {
        $errors[] = 'Title, author, and description are required.';
    }

    if (empty($errors) && $editCoverFile && isset($editCoverFile['tmp_name']) && $editCoverFile['error'] === UPLOAD_ERR_OK && is_uploaded_file($editCoverFile['tmp_name'])) {
        $editCoverExtension = strtolower(pathinfo($editCoverFile['name'] ?? '', PATHINFO_EXTENSION));
        if (!in_array($editCoverExtension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $errors[] = 'Cover image must be a JPG, PNG, or WebP file.';
        }
    }

    if (empty($errors)) {
        $targetDir = __DIR__ . '/books';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $currentBookStmt = $pdo->prepare('SELECT cover FROM books WHERE id = :id');
        $currentBookStmt->execute([':id' => $editBookId]);
        $currentBook = $currentBookStmt->fetch();

        $newCoverName = $currentBook['cover'] ?? null;
        if ($editCoverFile && isset($editCoverFile['tmp_name']) && $editCoverFile['error'] === UPLOAD_ERR_OK && is_uploaded_file($editCoverFile['tmp_name'])) {
            $editCoverExtension = strtolower(pathinfo($editCoverFile['name'], PATHINFO_EXTENSION));
            $newCoverName = 'cover-' . $editBookId . '-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $editCoverExtension;
            $coverTargetPath = $targetDir . '/' . $newCoverName;
            if (!move_uploaded_file($editCoverFile['tmp_name'], $coverTargetPath)) {
                $errors[] = 'The cover image could not be saved.';
            }
        }

        if (empty($errors)) {
            if ($currentBook['cover'] && $newCoverName !== $currentBook['cover'] && file_exists($targetDir . '/' . $currentBook['cover'])) {
                unlink($targetDir . '/' . $currentBook['cover']);
            }

            $stmt = $pdo->prepare('UPDATE books SET title = :title, author = :author, description = :description, cover = :cover WHERE id = :id');
            $stmt->execute([
                ':title' => $editTitle,
                ':author' => $editAuthor,
                ':description' => $editDescription,
                ':cover' => $newCoverName,
                ':id' => $editBookId,
            ]);
            set_flash('Book updated successfully.');
            redirect('books.php');
        }
    }
}

$stmt = $pdo->query('SELECT id, title, author, description, file, cover FROM books ORDER BY title');
$books = $stmt->fetchAll();

function render_error_list(array $errors): string
{
    $items = '';
    foreach ($errors as $error) {
        $items .= '<li>' . escape_html($error) . '</li>';
    }
    return '<ul>' . $items . '</ul>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Books | Jacobo Z. Gonzales Memorial School of Arts and Trades</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="admin-page">
<div class="container admin-layout">
    <header class="site-header">
        <a class="site-brand" href="index.php">
            <img src="jzgmsat logo.jpg" alt="School logo" class="site-brand__mark" style="width:56px;height:56px;object-fit:cover;border-radius:12px;">
            <div>
                <div class="site-title">Jacobo Z. Gonzales Memorial School of Arts and Trades</div>
                <div style="font-size:0.95rem;color:#6b7280;">Add your PDF Book.</div>
            </div>
        </a>
        <nav class="nav-links">
            <?php if ($admin): ?>
                <a class="nav-link" href="accounts.php">User accounts</a>
                <a class="nav-link" href="register.php">Create account</a>
            <?php endif; ?>
            <a class="nav-link nav-link--button" href="logout.php">Logout</a>
        </nav>
    </header>

    <?php if ($flash): ?>
        <div class="alert"><?= escape_html($flash) ?></div>
    <?php endif; ?>

    <?php if ($admin): ?>
        <section class="card form-card">
            <div class="form-card__header">
                <div class="form-card__eyebrow">Library management</div>
                <h1>Add a new PDF book</h1>
                <p class="form-card__intro">Upload a PDF resource and an optional cover image to make it available to the school library.</p>
            </div>
            <?php if (!empty($errors)): ?>
                <div class="alert">
                    <?= render_error_list($errors) ?>
                </div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload_book">
                        <input type="hidden" name="MAX_FILE_SIZE" value="52428800">
                <div class="form-field">
                    <label for="title">Title</label>
                    <input id="title" name="title" required>
                </div>
                <div class="form-field">
                    <label for="author">Author</label>
                    <input id="author" name="author" required>
                </div>
                <div class="form-field">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4" required></textarea>
                </div>
                <div class="form-field">
                    <label for="book_file">PDF File</label>
                    <input id="book_file" type="file" name="book_file" accept="application/pdf" required>
                </div>
                <div class="form-field">
                    <label for="cover_file">Book Cover Image</label>
                    <input id="cover_file" type="file" name="cover_file" accept="image/jpeg,image/png,image/webp">
                </div>
                <div class="form-actions">
                    <button class="button button--primary" type="submit">Upload book</button>
                    <a class="nav-link" href="index.php">Browse library</a>
                </div>
            </form>
        </section>
    <?php else: ?>
        <section class="card">
            <div class="form-card__header">
                <div class="form-card__eyebrow">Library access</div>
                <h1>Browse and download books</h1>
                <p class="form-card__intro">You can access the collection from the library view. Administrators can add and update books here.</p>
            </div>
        </section>
    <?php endif; ?>


    <footer class="footer">Logged in as <?= escape_html($user['name']) ?>.</footer>
</div>
</body>
</html>
