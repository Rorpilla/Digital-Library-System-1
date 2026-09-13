<?php
// Usage: php insert_book.php "C:\path\to\file.pdf" "Optional Title" "Optional Author" "Optional Description"
require __DIR__ . '/init.php';

$src = $argv[1] ?? null;
$titleArg = $argv[2] ?? null;
$authorArg = $argv[3] ?? null;
$descArg = $argv[4] ?? null;

if (!$src || !file_exists($src)) {
    fwrite(STDERR, "Source file not found: $src\n");
    exit(1);
}

// derive metadata if not provided
$baseName = pathinfo($src, PATHINFO_FILENAME);
$derivedTitle = $titleArg ?: str_replace(['_', '-'], ' ', $baseName);
$derivedAuthor = $authorArg ?: (preg_match('/by[\s_-]*(.+)/i', $baseName, $m) ? trim($m[1]) : 'Unknown');
$derivedDesc = $descArg ?: 'Uploaded via helper script.';

$targetDir = __DIR__ . '/books';
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}

$safeName = preg_replace('/[^a-z0-9._-]+/i', '-', pathinfo($src, PATHINFO_FILENAME));
$safeName = trim($safeName, '-_.');
if ($safeName === '') { $safeName = 'book'; }
$storedName = $safeName . '-' . time() . '-' . bin2hex(random_bytes(4)) . '.pdf';
$targetPath = $targetDir . '/' . $storedName;

if (!@rename($src, $targetPath)) {
    // try copy then unlink
    if (!@copy($src, $targetPath)) {
        fwrite(STDERR, "Failed to move or copy file to $targetPath\n");
        exit(1);
    }
    // attempt to remove original
    @unlink($src);
}

try {
    $stmt = $pdo->prepare('INSERT INTO books (title, author, description, file, cover) VALUES (:title, :author, :description, :file, :cover)');
    $stmt->execute([
        ':title' => $derivedTitle,
        ':author' => $derivedAuthor,
        ':description' => $derivedDesc,
        ':file' => $storedName,
        ':cover' => null,
    ]);
    $id = $pdo->lastInsertId();
    echo "Inserted book ID $id with file $storedName\n";
    echo "Open it at: view.php?id=$id\n";
    exit(0);
} catch (Throwable $e) {
    // cleanup file
    if (file_exists($targetPath)) { @unlink($targetPath); }
    fwrite(STDERR, "Database error: " . $e->getMessage() . "\n");
    exit(1);
}

?>