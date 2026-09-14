<?php
require __DIR__ . '/init.php';
require_login();

$bookId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($bookId === false || $bookId === null) {
    set_flash('Invalid book selection.');
    redirect('index.php');
}

$stmt = $pdo->prepare('SELECT id, title, file FROM books WHERE id = :id');
$stmt->execute([':id' => $bookId]);
$book = $stmt->fetch();
if (!$book) {
    set_flash('Invalid book selection.');
    redirect('index.php');
}

$filename = __DIR__ . '/books/' . $book['file'];
if (!file_exists($filename)) {
    set_flash('Book file is currently unavailable.');
    redirect('index.php');
}

$user = current_user();
if ($user !== null) {
    $stmt = $pdo->prepare('INSERT INTO book_views (user_id, book_id, viewed_at) VALUES (:user_id, :book_id, :viewed_at)');
    $stmt->execute([
        ':user_id' => $user['id'],
        ':book_id' => $book['id'],
        ':viewed_at' => date('Y-m-d H:i:s')
    ]);
}

// generate a short-lived token for viewing to avoid direct linking to the PDF
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$pdfToken = bin2hex(random_bytes(16));
$_SESSION['pdf_view_tokens'][$book['id']] = [
    'token' => $pdfToken,
    'expires' => time() + 300 // valid for 5 minutes
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview | <?= escape_html($book['title']) ?></title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body { user-select: none; -webkit-user-select: none; background: #fbf8f2; color: #3e2a1d; }
        .viewer-shell { padding: 0; overflow: hidden; background: linear-gradient(135deg, #fffdf9, #f3eadc); border: 1px solid rgba(111,75,45,0.16); }
        .viewer-toolbar { display: flex; justify-content: space-between; align-items: center; gap: 16px; padding: 14px 18px; border-bottom: 1px solid rgba(111,75,45,0.16); background: rgba(255,253,249,0.92); }
        .viewer-toolbar__meta { min-width: 0; }
        .viewer-toolbar__meta strong { color: #3e2a1d; }
        .viewer-toolbar__meta span { color: #765b45; }
        .viewer-subtitle { font-size: 0.95rem; color: #765b45; }
        .viewer-toolbar__actions { display: flex; align-items: center; justify-content: flex-end; gap: 8px; flex-wrap: wrap; }
        .viewer-controls { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        #page-display { min-width: 80px; text-align: center; color: #765b45; }
        .book-reader-stage { display: flex; justify-content: center; align-items: center; padding: 24px; background: radial-gradient(circle at top, rgba(188,139,89,0.18), transparent 30%), linear-gradient(135deg, #f3eadc, #eadbc7); min-height: 78vh; }
        .book-reader-page { position: relative; width: min(100%, 940px); border-radius: 20px; border: 1px solid rgba(111,75,45,0.2); box-shadow: 0 30px 80px rgba(91,57,31,0.18); overflow: hidden; background: #fffdf9; min-height: 72vh; }
        .book-reader-page::before { content: ''; position: absolute; inset: 0; pointer-events: none; box-shadow: inset 0 0 0 1px rgba(111,75,45,0.05), inset 0 0 30px rgba(91,57,31,0.08); }
        .reader-note { margin: 0; font-size: 0.94rem; color: #765b45; padding: 0 24px 20px; background: #fffdf9; }
        .page-indicator { display: inline-flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 999px; background: rgba(138,90,53,0.1); color: #6f472b; font-weight: 600; border: 1px solid rgba(111,75,45,0.18); }
        .button--secondary { background: #f3eadc; color: #5f3f29; border: 1px solid rgba(111,75,45,0.2); }
        .button--secondary:hover { background: #eadbc7; }
        #pdf-viewer { width: 100%; height: 72vh; min-height: 72vh; display: flex; align-items: center; justify-content: center; position: relative; overflow: auto; padding: 8px; box-sizing: border-box; background: #fffdf9; border-radius: 8px; }
        #pdf-canvas, #pdf-image { max-width: 100%; max-height: 100%; display: block; border-radius: 8px; background: #fffdf9; }
        @media (max-width: 900px) { .book-reader-stage { padding: 12px; min-height: 70vh; } .book-reader-page { width: 100%; min-height: 68vh; } #pdf-viewer { height: 68vh; min-height: 68vh; } }
        @media (max-width: 640px) { .viewer-toolbar { align-items: stretch; flex-direction: column; padding: 12px; } .viewer-toolbar__actions { justify-content: flex-start; } .viewer-toolbar__actions > div { width: 100%; display: grid !important; grid-template-columns: repeat(2, minmax(0, 1fr)); } .viewer-toolbar__actions .button, .viewer-toolbar__actions .page-indicator, .viewer-toolbar__actions #page-display { width: 100%; min-width: 0; } .viewer-toolbar__actions #page-display { display: inline-flex; align-items: center; justify-content: center; } .book-reader-stage { padding: 8px; } .book-reader-page { border-radius: 12px; min-height: 62vh; } #pdf-viewer { height: 62vh; min-height: 62vh; padding: 4px; } .reader-note { padding: 0 14px 16px; font-size: 0.82rem; } }
    </style>
</head>
<body>
<div class="container">
    <header class="site-header">
        <a class="site-brand" href="index.php">
            <img src="jzgmsat logo.jpg" alt="School logo" class="site-brand__mark" style="width:56px;height:56px;object-fit:cover;border-radius:12px;">
            <div>
                <div class="site-title">Previewing: <?= escape_html($book['title']) ?></div>
                    <div class="viewer-subtitle">Read the PDF directly in your browser.</div>
            </div>
        </a>
        <nav class="nav-links">
            <a class="nav-link nav-link--button" href="logout.php">Logout</a>
        </nav>
    </header>

    <main class="card viewer-shell">
        <div class="viewer-toolbar">
            <div class="viewer-toolbar__meta">
                <strong><?= escape_html($book['title']) ?></strong>
                <span>Viewing only • no download • no save • no screenshot</span>
            </div>
            <div class="viewer-toolbar__actions">
                <span class="page-indicator" id="viewer-mode-indicator">📖 Viewer mode</span>
                    <div class="viewer-controls">
                    <button id="zoom-out" class="button button--secondary">− Zoom</button>
                    <button id="zoom-in" class="button button--secondary">+ Zoom</button>
                    <button id="prev-page" class="button button--secondary">◀ Prev</button>
                    <span id="page-display">Page 1 / 1</span>
                    <button id="next-page" class="button button--secondary">Next ▶</button>
                    <a class="button button--secondary" href="library.php">Back to library</a>
                </div>
            </div>
        </div>
        <div class="book-reader-stage">
            <div class="book-reader-page">
                <div id="pdf-viewer">
                    <canvas id="pdf-canvas"></canvas>
                    <img id="pdf-image" alt="">
                </div>
            </div>
        </div>
        <p class="reader-note">This viewer renders the PDF page-only (no native toolbar).</p>
    </main>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
<script>
    document.addEventListener('contextmenu', (event) => event.preventDefault());
    document.addEventListener('selectstart', (event) => event.preventDefault());
    document.addEventListener('dragstart', (event) => event.preventDefault());
    document.addEventListener('copy', (event) => event.preventDefault());
    document.addEventListener('cut', (event) => event.preventDefault());
    document.addEventListener('paste', (event) => event.preventDefault());

    document.addEventListener('keydown', (event) => {
        const key = event.key.toLowerCase();
        if (event.shiftKey) {
            applyBlurState(false);
            event.preventDefault();
            event.stopPropagation();
            return false;
        }
        if ((event.ctrlKey || event.metaKey) && ['p','s','u','c','a','w','l','q'].includes(key)) {
            applyBlurState(false);
            event.preventDefault();
            event.stopPropagation();
            return false;
        }
        if (['f12', 'printscreen', 'insert', 'scrolllock', 'pause'].includes(key)) {
            applyBlurState(false);
            event.preventDefault();
            event.stopPropagation();
            return false;
        }
        if (event.altKey && key === 'tab') {
            applyBlurState(false);
            event.preventDefault();
            event.stopPropagation();
        }
    });

    document.addEventListener('keyup', (event) => {
        if (event.key === 'Shift') {
            applyBlurState(document.hasFocus());
        }
    });

    window.addEventListener('beforeprint', (event) => event.preventDefault());
    window.addEventListener('afterprint', () => window.focus());

    function applyBlurState(isFocused) {
        document.body.style.filter = isFocused ? 'none' : 'blur(18px)';
        document.body.style.transition = 'filter 0.2s ease';
    }

    window.addEventListener('focus', () => applyBlurState(true));
    window.addEventListener('blur', () => applyBlurState(false));
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            applyBlurState(false);
            document.title = 'Viewer locked';
        } else {
            applyBlurState(document.hasFocus());
            document.title = 'Preview';
        }
    });

    document.addEventListener('keyup', (event) => {
        if (event.key === 'PrintScreen') {
            navigator.clipboard?.writeText('').catch(() => {});
        }
    });

    applyBlurState(document.hasFocus());

    // PDF.js multi-page rendering + server-image fallback
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';
    (async function() {
        const url = 'download.php?id=<?= $book['id'] ?>&mode=view&token=<?= $pdfToken ?>';
        let useServerImages = false;
        let pdf = null;
        let numPages = 0;
        let currentPage = 1;
        let zoomLevel = 1.0;

        const canvas = document.getElementById('pdf-canvas');
        const img = document.getElementById('pdf-image');
        const pageDisplay = document.getElementById('page-display');
        const modeIndicator = document.getElementById('viewer-mode-indicator');

        async function loadPdf() {
            try {
                const loadingTask = pdfjsLib.getDocument(url);
                pdf = await loadingTask.promise;
                numPages = pdf.numPages;
                updatePageDisplay();
            } catch (err) {
                console.error('PDF load error', err);
                pdf = null;
                numPages = 0;
                updatePageDisplay();
                document.getElementById('pdf-viewer').innerText = 'Unable to load book preview.';
            }
        }

        async function renderPage(pageNum) {
            if (useServerImages) {
                canvas.style.display = 'none';
                img.style.display = 'block';
                img.src = 'image.php?id=<?= $book['id'] ?>&page=' + pageNum + '&token=<?= $pdfToken ?>';
                updatePageDisplay();
                return;
            }
            if (!pdf) return;
            const page = await pdf.getPage(pageNum);
            const viewer = document.getElementById('pdf-viewer');
            const pageWidth = page.getViewport({ scale: 1 }).width;
            const pageHeight = page.getViewport({ scale: 1 }).height;
            const viewerWidth = Math.max(320, viewer.clientWidth - 16);
            const viewerHeight = Math.max(240, viewer.clientHeight - 16);
            const scaleX = viewerWidth / pageWidth;
            const scaleY = viewerHeight / pageHeight;
            const baseScale = Math.min(scaleX, scaleY, 1.8);
            const scale = baseScale * zoomLevel;
            const viewport = page.getViewport({ scale });
            canvas.width = Math.floor(viewport.width);
            canvas.height = Math.floor(viewport.height);
            canvas.style.display = 'block';
            img.style.display = 'none';
            const ctx = canvas.getContext('2d');
            const renderContext = { canvasContext: ctx, viewport };
            await page.render(renderContext).promise;
            updatePageDisplay();
        }

        function updatePageDisplay() {
            pageDisplay.textContent = 'Page ' + currentPage + (numPages ? ' / ' + numPages : '');
            modeIndicator.textContent = 'Zoom ' + zoomLevel.toFixed(1) + 'x';
        }

        document.getElementById('prev-page').addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage -= 1;
                renderPage(currentPage);
            }
        });
        document.getElementById('next-page').addEventListener('click', () => {
            if (numPages === 0 || currentPage < numPages) {
                currentPage += 1;
                renderPage(currentPage);
            }
        });
        document.getElementById('zoom-out').addEventListener('click', () => {
            zoomLevel = Math.max(0.8, zoomLevel - 0.2);
            updatePageDisplay();
            renderPage(currentPage);
        });
        document.getElementById('zoom-in').addEventListener('click', () => {
            zoomLevel = Math.min(2.5, zoomLevel + 0.2);
            updatePageDisplay();
            renderPage(currentPage);
        });

        await loadPdf();
        if (numPages > 0) {
            currentPage = 1;
            renderPage(currentPage);
        }
    })();
</script>
</body>
</html>
