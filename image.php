<?php
require __DIR__ . '/init.php';
require_login();

$bookId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$token = $_GET['token'] ?? '';

if ($bookId === false || $bookId === null || $page === false || $page === null) {
    header('HTTP/1.1 400 Bad Request');
    echo 'Invalid parameters.';
    exit;
}

$stmt = $pdo->prepare('SELECT id, title, file FROM books WHERE id = :id');
$stmt->execute([':id' => $bookId]);
$book = $stmt->fetch();
if (!$book) {
    header('HTTP/1.1 404 Not Found');
    echo 'Book not found.';
    exit;
}

$filename = __DIR__ . '/books/' . $book['file'];
if (!file_exists($filename)) {
    header('HTTP/1.1 404 Not Found');
    echo 'Book file not available.';
    exit;
}

// validate token
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$valid = false;
if (!empty($_SESSION['pdf_view_tokens'][$bookId] ?? null)) {
    $entry = $_SESSION['pdf_view_tokens'][$bookId];
    if (is_array($entry) && ($entry['token'] ?? '') === $token && ($entry['expires'] ?? 0) >= time()) {
        $valid = true;
    }
}
if (!$valid) {
    header('HTTP/1.1 403 Forbidden');
    echo 'Invalid or expired token.';
    exit;
}

$cacheDir = __DIR__ . '/tmp_images';
if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0755, true);
}
$cacheFile = $cacheDir . '/' . $bookId . '-page-' . $page . '.png';

if (file_exists($cacheFile)) {
    header('Content-Type: image/png');
    readfile($cacheFile);
    exit;
}

// Try Imagick first
if (extension_loaded('imagick')) {
    try {
        $im = new Imagick();
        $im->setResolution(150,150);
        $im->readImage(sprintf('%s[%d]', $filename, $page - 1));
        $im->setImageFormat('png');
        $im->setImageBackgroundColor('white');
        $im = $im->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
        $im->writeImage($cacheFile);
        header('Content-Type: image/png');
        readfile($cacheFile);
        exit;
    } catch (Exception $e) {
        // fallthrough to other methods
    }
}

// Fallback: try pdftoppm (poppler)
$pdftoppm = trim(shell_exec('where pdftoppm 2>NUL || which pdftoppm 2>/dev/null'));
if (!empty($pdftoppm)) {
    $tmpPrefix = $cacheDir . '/' . uniqid('p');
    $cmd = escapeshellcmd($pdftoppm) . ' -f ' . intval($page) . ' -singlefile -png ' . escapeshellarg($filename) . ' ' . escapeshellarg($tmpPrefix);
    exec($cmd, $output, $rc);
    $generated = $tmpPrefix . '.png';
    if ($rc === 0 && file_exists($generated)) {
        rename($generated, $cacheFile);
        header('Content-Type: image/png');
        readfile($cacheFile);
        exit;
    }
}

header('HTTP/1.1 501 Not Implemented');
echo 'Server cannot render PDF to image (no Imagick or pdftoppm).';
exit;

?>