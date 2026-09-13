<?php
require __DIR__ . '/init.php';
require_login();

$bookId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($bookId === false || $bookId === null) {
    set_flash('Invalid book selection.');
    redirect('books.php');
}

$stmt = $pdo->prepare('SELECT id, title, file FROM books WHERE id = :id');
$stmt->execute([':id' => $bookId]);
$book = $stmt->fetch();
if (!$book) {
    set_flash('Invalid book selection.');
    redirect('books.php');
}

$filename = __DIR__ . '/books/' . $book['file'];
if (!file_exists($filename)) {
    set_flash('Book file is currently unavailable.');
    redirect('books.php');
}

$mode = strtolower($_GET['mode'] ?? 'download');
if ($mode === 'view') {
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . basename($book['file']) . '"');
    header('Content-Length: ' . filesize($filename));
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Content-Security-Policy: default-src \'' . 'self' . '\'; object-src \'' . 'none' . '\'; frame-ancestors \'' . 'self' . '\'; base-uri \'' . 'self' . '\'; form-action \'' . 'self' . '\'; script-src \'' . 'none' . '\'; style-src \'' . 'none' . '\'; img-src \'' . 'self' . '\' data:;');
    header('Referrer-Policy: no-referrer');
    header('Cache-Control: no-store, no-cache, must-revalidate, private');
    header('Pragma: no-cache');
    header('Expires: 0');
    // require a valid short-lived token for view requests
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $token = $_GET['token'] ?? '';
    $valid = false;
    if (!empty($_SESSION['pdf_view_tokens'][$book['id']] ?? null)) {
        $entry = $_SESSION['pdf_view_tokens'][$book['id']];
        if (is_array($entry) && ($entry['token'] ?? '') === $token && ($entry['expires'] ?? 0) >= time()) {
            $valid = true;
        }
    }
    if (!$valid) {
        header('HTTP/1.1 403 Forbidden');
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Invalid or expired view token.';
        exit;
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . basename($book['file']) . '"');
    header('Content-Length: ' . filesize($filename));
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Content-Security-Policy: default-src \'self\'; object-src \'none\'; frame-ancestors \'self\'; base-uri \'self\'; form-action \'self\'; script-src \'none\'; style-src \'none\'; img-src \'self\' data:;');
    header('Referrer-Policy: no-referrer');
    header('Cache-Control: no-store, no-cache, must-revalidate, private');
    header('Pragma: no-cache');
    header('Expires: 0');
    readfile($filename);
    exit;
}

header('HTTP/1.1 403 Forbidden');
header('Content-Type: text/plain; charset=UTF-8');
echo 'File downloads are disabled for this library.';
exit;
