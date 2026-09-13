<?php
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log');
error_reporting(E_ALL);
require __DIR__ . '/init.php';
$user = current_user();

$search = trim($_GET['search'] ?? '');
$books = [];
$totalBooks = (int) $pdo->query('SELECT COUNT(*) FROM books')->fetchColumn();
$featuredBooks = $pdo->query('SELECT id, title, author, description, file, cover FROM books ORDER BY title LIMIT 6')->fetchAll();
$monthlyStats = $pdo->query("SELECT b.title, COUNT(v.id) AS view_count
    FROM books b
    LEFT JOIN book_views v ON v.book_id = b.id
        AND strftime('%Y-%m', v.viewed_at) = strftime('%Y-%m', 'now', 'localtime')
    GROUP BY b.id, b.title
    ORDER BY view_count DESC, b.title
    LIMIT 6")->fetchAll();
$monthlyViewTotal = array_sum(array_map(static fn(array $stat): int => (int) $stat['view_count'], $monthlyStats));
$monthlyMaxViews = max(1, ...array_map(static fn(array $stat): int => (int) $stat['view_count'], $monthlyStats));
$monthLabel = date('F Y');

$defaultHero = 'pictures/jzgmsat-front.png';
$heroImage = $defaultHero;
$preferredBase = 'pictures/JZGMSAT School';
$exts = ['jpg', 'jpeg', 'png', 'webp'];
foreach ($exts as $ext) {
    $candidate = $preferredBase . '.' . $ext;
    if (file_exists(__DIR__ . '/' . $candidate)) {
        $heroImage = $candidate;
        break;
    }
}
if (!file_exists(__DIR__ . '/' . $heroImage) && file_exists(__DIR__ . '/' . $defaultHero)) {
    $heroImage = $defaultHero;
}

if ($search !== '') {
    $escapedSearch = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
    $stmt = $pdo->prepare('SELECT id, title, author, description, file, cover FROM books WHERE LOWER(title) LIKE LOWER(:term) OR LOWER(author) LIKE LOWER(:term) ORDER BY title');
    $stmt->execute([':term' => '%' . $escapedSearch . '%']);
    $books = $stmt->fetchAll();
} else {
    if ($user !== null) {
        $stmt = $pdo->prepare('SELECT b.id, b.title, b.author, b.description, b.file, b.cover
            FROM book_views v
            JOIN books b ON b.id = v.book_id
            WHERE v.user_id = :user_id
            GROUP BY b.id, b.title, b.author, b.description, b.file, b.cover
            ORDER BY MAX(v.viewed_at) DESC, b.id DESC
            LIMIT 6');
        $stmt->execute([':user_id' => $user['id']]);
        $books = $stmt->fetchAll();
    } else {
        $books = $featuredBooks;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home | Jacobo Z. Gonzales Memorial School of Arts and Trades</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/ScrollTrigger.min.js"></script>
</head>
<body>
    <div class="ambient-layer ambient-layer--one" data-depth="0.12"></div>
    <div class="ambient-layer ambient-layer--two" data-depth="0.2"></div>
    <div class="ambient-layer ambient-layer--three" data-depth="0.08"></div>

    <div class="page-shell">
        <div class="container">
            <header class="site-header">
                <a class="site-brand" href="index.php">
                    <img src="jzgmsat logo.jpg" alt="School logo" class="site-brand__mark">
                    <div>
                        <div class="site-title">Jacobo Z. Gonzales Memorial School of Arts and Trades</div>
                        <div class="site-subtitle">A modern digital library for learning, discovery, and creativity.</div>
                    </div>
                </a>

                <nav class="nav-links" aria-label="Main navigation">
                    <a class="nav-link" href="#explore">Explore</a>
                    <a class="nav-link" href="#collections">Collections</a>
                    <a class="nav-link" href="#about">About</a>
                    <?php if ($user !== null): ?>
                        <?php if (is_admin($user)): ?>
                            <a class="nav-link" href="books.php">Add books</a>
                            <a class="nav-link" href="accounts.php">View users</a>
                            <a class="nav-link nav-link--button" href="register.php">Create account</a>
                        <?php endif; ?>
                        <a class="nav-link nav-link--button" href="logout.php">Logout</a>
                    <?php else: ?>
                        <a class="nav-link" href="login.php">Login</a>
                    <?php endif; ?>
                </nav>
            </header>

            <main>
                <section class="hero-immersive" id="explore">
                    <div class="hero-stage" data-scroll-scene>
                        <div class="hero-copy">
                            <div class="eyebrow">School digital library</div>
                            <h1>
                                <span>Your</span>
                                <span>Library</span>
                                <span>Reimagined.</span>
                            </h1>
                            <p>Browse a calm collection of books, research, and creative learning resources in one quiet space.</p>
                            <a href="library.php" class="button button--primary">Explore the archive</a>
                        </div>

                        <div class="publication-canvas" aria-hidden="true">
                            <article class="publication publication--one" data-start-x="-18" data-start-y="-10" data-start-z="18" data-start-rotate-x="2" data-start-rotate-y="-1" data-start-rotate-z="-2" data-start-scale="1" data-end-x="-150" data-end-y="28" data-end-z="180" data-end-rotate-x="0" data-end-rotate-y="-18" data-end-rotate-z="-2" data-end-scale="0.96">
                                <div class="publication__spine"></div>
                                <div class="publication__cover publication__cover--stone">
                                    <div class="publication__content">
                                        <span class="publication__kicker">TESDA Course</span>
                                        <strong class="publication__title">Cookery<br>NC II</strong>
                                        <span class="publication__meta">Training Guide</span>
                                    </div>
                                </div>
                            </article>

                            <article class="publication publication--two" data-start-x="-6" data-start-y="-4" data-start-z="24" data-start-rotate-x="1" data-start-rotate-y="0" data-start-rotate-z="-1" data-start-scale="0.99" data-end-x="-82" data-end-y="12" data-end-z="200" data-end-rotate-x="1" data-end-rotate-y="12" data-end-rotate-z="2" data-end-scale="1.01">
                                <div class="publication__spine"></div>
                                <div class="publication__cover publication__cover--blue">
                                    <div class="publication__content">
                                        <span class="publication__kicker">TESDA Course</span>
                                        <strong class="publication__title">Electrical<br>Installation</strong>
                                        <span class="publication__meta">Training Guide</span>
                                    </div>
                                </div>
                            </article>

                            <article class="publication publication--three" data-start-x="0" data-start-y="0" data-start-z="28" data-start-rotate-x="0" data-start-rotate-y="0" data-start-rotate-z="0" data-start-scale="1" data-end-x="0" data-end-y="-12" data-end-z="260" data-end-rotate-x="-2" data-end-rotate-y="0" data-end-rotate-z="0" data-end-scale="1.06">
                                <div class="publication__spine"></div>
                                <div class="book-scene" aria-hidden="true">
                                    <div class="book-shadow"></div>
                                    <div class="floating-letter letter-a">A</div>
                                    <div class="floating-letter letter-b">B</div>
                                    <div class="floating-letter letter-c">C</div>
                                    <div class="floating-letter letter-d">D</div>
                                    <div class="floating-letter letter-e">E</div>
                                    <div class="floating-letter letter-f">F</div>
                                    <div class="floating-letter letter-g">G</div>
                                    <div class="floating-letter letter-r">R</div>
                                    <div class="floating-letter letter-read">R</div>
                                    <div class="scatter-page scatter-page--1"></div>
                                    <div class="scatter-page scatter-page--2"></div>
                                    <div class="scatter-page scatter-page--3"></div>
                                    <div class="scatter-page scatter-page--4"></div>
                                    <div class="scatter-page scatter-page--5"></div>
                                    <div class="book-shell">
                                        <div class="book-back-cover"></div>
                                        <div class="book-pages">
                                            <span class="book-page page-1"></span>
                                            <span class="book-page page-2"></span>
                                            <span class="book-page page-3"></span>
                                            <span class="book-page page-4"></span>
                                            <span class="book-page page-5"></span>
                                        </div>
                                        <div class="book-front-cover">
                                            <div class="book-cover-inner">
                                                <span class="book-kicker">TESDA</span>
                                                <strong>Early<br>Childhood<br>Care</strong>
                                                <small>Competency-Based Training</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </article>

                            <article class="publication publication--four" data-start-x="12" data-start-y="6" data-start-z="30" data-start-rotate-x="-1" data-start-rotate-y="1" data-start-rotate-z="1" data-start-scale="0.98" data-end-x="88" data-end-y="-18" data-end-z="200" data-end-rotate-x="-1" data-end-rotate-y="-10" data-end-rotate-z="-2" data-end-scale="1">
                                <div class="publication__spine"></div>
                                <div class="publication__cover publication__cover--charcoal">
                                    <div class="publication__content">
                                        <span class="publication__kicker">TESDA Course</span>
                                        <strong class="publication__title">Automotive<br>Servicing</strong>
                                        <span class="publication__meta">Training Guide</span>
                                    </div>
                                </div>
                            </article>

                            <article class="publication publication--five" data-start-x="22" data-start-y="10" data-start-z="34" data-start-rotate-x="-2" data-start-rotate-y="2" data-start-rotate-z="2" data-start-scale="0.97" data-end-x="140" data-end-y="20" data-end-z="130" data-end-rotate-x="-1" data-end-rotate-y="-16" data-end-rotate-z="2" data-end-scale="0.96">
                                <div class="publication__spine"></div>
                                <div class="publication__cover publication__cover--charcoal">
                                    <div class="publication__content">
                                        <span class="publication__kicker">TESDA Course</span>
                                        <strong class="publication__title">Computer<br>Systems</strong>
                                        <span class="publication__meta">Servicing NC II</span>
                                    </div>
                                </div>
                            </article>

                            <article class="publication publication--six" data-start-x="-12" data-start-y="4" data-start-z="26" data-start-rotate-x="1" data-start-rotate-y="-1" data-start-rotate-z="-1" data-start-scale="0.96" data-end-x="-176" data-end-y="46" data-end-z="150" data-end-rotate-x="1" data-end-rotate-y="14" data-end-rotate-z="-4" data-end-scale="0.94">
                                <div class="publication__spine"></div>
                                <div class="publication__cover publication__cover--blue">
                                    <div class="publication__content">
                                        <span class="publication__kicker">TESDA Course</span>
                                        <strong class="publication__title">Welding<br>NC II</strong>
                                        <span class="publication__meta">Training Guide</span>
                                    </div>
                                </div>
                            </article>

                            <article class="publication publication--seven" data-start-x="14" data-start-y="6" data-start-z="30" data-start-rotate-x="-1" data-start-rotate-y="1" data-start-rotate-z="1" data-start-scale="0.95" data-end-x="176" data-end-y="48" data-end-z="142" data-end-rotate-x="-1" data-end-rotate-y="-14" data-end-rotate-z="4" data-end-scale="0.94">
                                <div class="publication__spine"></div>
                                <div class="publication__cover publication__cover--stone">
                                    <div class="publication__content">
                                        <span class="publication__kicker">TESDA Course</span>
                                        <strong class="publication__title">Computer Systems<br>Servicing</strong>
                                        <span class="publication__meta">NC II Training Guide</span>
                                    </div>
                                </div>
                            </article>
                        </div>
                    </div>
                </section>

                <section class="editorial-showcase reveal-block" id="collections">
                    <div class="showcase-copy">
                        <div class="form-card__eyebrow">Monthly usage</div>
                        <h2>Book activity at a glance.</h2>
                        <p>See which titles are being opened most this month across the digital library.</p>
                    </div>

                    <div class="usage-statistics">
                        <div class="usage-chart" aria-label="Book views for <?= escape_html($monthLabel) ?>">
                            <div class="usage-chart__header">
                                <span><?= escape_html($monthLabel) ?></span>
                                <strong><?= $monthlyViewTotal ?> total views</strong>
                            </div>
                            <?php foreach ($monthlyStats as $stat): ?>
                                <?php $viewCount = (int) $stat['view_count']; ?>
                                <div class="usage-chart__row">
                                    <span class="usage-chart__label"><?= escape_html($stat['title']) ?></span>
                                    <div class="usage-chart__track"><span style="width: <?= (int) round(($viewCount / $monthlyMaxViews) * 100) ?>%"></span></div>
                                    <strong class="usage-chart__value"><?= $viewCount ?></strong>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="usage-ranking">
                            <div class="usage-chart__header">
                                <span>Most viewed</span>
                                <strong>Top titles</strong>
                            </div>
                            <?php foreach ($monthlyStats as $rank => $stat): ?>
                                <div class="usage-ranking__item">
                                    <span class="usage-ranking__rank"><?= $rank + 1 ?></span>
                                    <span class="usage-ranking__title"><?= escape_html($stat['title']) ?></span>
                                    <strong><?= (int) $stat['view_count'] ?> views</strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>

                <section class="library-discovery card reveal-block" id="discover">
                    <div class="discovery-header">
                        <div>
                            <div class="form-card__eyebrow">Library discovery</div>
                            <h2>Search the collection</h2>
                        </div>
                        <div class="compact-meta">
                            <span><?= $totalBooks ?> titles</span>
                            <span>Curated shelves</span>
                        </div>
                    </div>

                    <form class="search-panel" method="get" action="index.php">
                        <input type="text" name="search" value="<?= escape_html($search !== '' ? $search : 'Search books, authors, topics...') ?>" aria-label="Search library" onfocus="if(this.value==='Search books, authors, topics...'){this.value='';}" onblur="if(this.value===''){this.value='Search books, authors, topics...';}">
                        <button class="button button--primary" type="submit">Explore Library</button>
                    </form>

                    <div class="filter-row" aria-label="Library filters">
                        <a class="filter-pill is-active" href="library.php">All</a>
                        <a class="filter-pill" href="library.php">Books</a>
                        <a class="filter-pill" href="library.php">Research</a>
                        <a class="filter-pill" href="library.php">Learning</a>
                    </div>
                </section>

                <section class="card results-section reveal-block" id="featured">
                    <div class="form-card__header">
                        <div class="form-card__eyebrow">Featured books</div>
                        <h1><?= $search !== '' ? 'Search results for “' . escape_html($search) . '”' : 'Books available in the archive' ?></h1>
                        <p class="form-card__intro"><?= $search !== '' ? 'Showing books that match your query.' : ($user !== null ? 'Your recent reading history is presented here for easy return.' : 'Start with a search above or open the full library to browse everything.') ?></p>
                    </div>

                    <?php if ($search !== ''): ?>
                        <div class="results-header">
                            <h2>Matching books</h2>
                            <p>Showing <?= count($books) ?> matching book<?= count($books) === 1 ? '' : 's' ?>.</p>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($books)): ?>
                        <div class="grid">
                            <?php foreach ($books as $book): ?>
                                <article class="book-card">
                                    <a class="book-card__link" href="<?= $user !== null ? 'view.php?id=' . (int) $book['id'] : 'login.php' ?>">
                                        <?php if (!empty($book['cover'])): ?>
                                            <img class="book-cover-image" src="books/<?= escape_html($book['cover']) ?>" alt="Cover for <?= escape_html($book['title']) ?>">
                                        <?php else: ?>
                                            <div class="book-cover-preview">
                                                <span><?= strtoupper(substr($book['title'], 0, 1)) ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <div class="book-card__meta">
                                            <span><?= escape_html($book['author']) ?></span>
                                            <span><?= !empty($book['file']) ? 'PDF' : 'Read' ?></span>
                                        </div>
                                        <h3 class="book-card__title"><?= escape_html($book['title']) ?></h3>
                                        <p class="book-card__description"><?= escape_html($book['description']) ?></p>
                                        <div class="book-card__footer">
                                            <span class="book-card__tag">Open title</span>
                                            <span class="book-card__arrow">→</span>
                                        </div>
                                    </a>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            No books matched that search yet. Try another title or author, or <a href="register.php">create an account</a> to add your own PDF.
                        </div>
                    <?php endif; ?>
                </section>

                <section class="footer-band reveal-block" id="about">
                    <div class="footer-card">
                        <div>
                            <div class="form-card__eyebrow">About the library</div>
                            <h2>Simple by design, rich by use.</h2>
                        </div>
                        <p>Built for students and readers who want a calm place to browse, discover, and return to meaningful books.</p>
                    </div>
                </section>
            </main>

            <footer class="footer">Built for quick discovery, creative learning, and easy access to classroom resources.</footer>
        </div>
    </div>

    <script>
        const layers = document.querySelectorAll('.ambient-layer');
        const pageShell = document.querySelector('.page-shell');
        const revealBlocks = document.querySelectorAll('.reveal-block');
        const heroStage = document.querySelector('.hero-stage');
        const heroScene = document.querySelector('.hero-immersive');
        const publicationCanvas = document.querySelector('.publication-canvas');
        const publications = document.querySelectorAll('.publication');
        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        let pointerX = window.innerWidth / 2;
        let pointerY = window.innerHeight / 2;
        let ticking = false;

        const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

        const resetBookCardState = () => {
            document.querySelectorAll('.book-card').forEach(card => {
                card.style.transform = '';
                card.style.zIndex = '';
                card.style.transition = '';
                card.style.boxShadow = '';
                card.style.opacity = '';
            });
        };

        const attachBookDetailHandlers = () => {
            const bookDetailPanel = document.getElementById('bookDetailPanel');
            if (!bookDetailPanel) {
                return;
            }

            const closeButton = bookDetailPanel.querySelector('#closeBookDetail');
            if (closeButton) {
                closeButton.onclick = () => {
                    bookDetailPanel.classList.remove('active');
                    document.querySelectorAll('.book-selected').forEach(el => {
                        el.classList.remove('book-selected');
                    });
                    resetBookCardState();
                };
            }

            bookDetailPanel.onclick = (e) => {
                if (e.target === bookDetailPanel) {
                    bookDetailPanel.classList.remove('active');
                    document.querySelectorAll('.book-selected').forEach(el => {
                        el.classList.remove('book-selected');
                    });
                    resetBookCardState();
                }
            };
        };

        const revealOnScroll = () => {
            const trigger = window.innerHeight * 0.88;

            revealBlocks.forEach((block) => {
                const top = block.getBoundingClientRect().top;
                if (top < trigger) {
                    block.classList.add('is-visible');
                }
            });

            const header = document.querySelector('.site-header');
            if (header) {
                header.classList.toggle('is-scrolled', window.scrollY > 24);
            }

            const sections = document.querySelectorAll('main section[id]');
            const scrollPosition = window.scrollY + 140;

            sections.forEach((section) => {
                const link = document.querySelector(`.nav-link[href="#${section.id}"]`);
                if (link) {
                    const sectionTop = section.offsetTop;
                    const sectionBottom = sectionTop + section.offsetHeight;
                    if (scrollPosition >= sectionTop && scrollPosition < sectionBottom) {
                        document.querySelectorAll('.nav-link[href^="#"]').forEach((navLink) => navLink.classList.remove('is-active'));
                        link.classList.add('is-active');
                    }
                }
            });
        };

        const smoothStep = (value) => value * value * (3 - 2 * value);

        const getScenePhase = (progress) => {
            if (progress < 0.2) {
                return Math.pow(progress / 0.2, 0.7);
            }
            if (progress < 0.45) {
                return 0.12 + smoothStep((progress - 0.2) / 0.25) * 0.38;
            }
            if (progress < 0.7) {
                return 0.5 + Math.pow((progress - 0.45) / 0.25, 1.18) * 0.3;
            }
            if (progress < 0.9) {
                return 0.8 + smoothStep((progress - 0.7) / 0.2) * 0.14;
            }
            return 0.94 + Math.pow((progress - 0.9) / 0.1, 0.85) * 0.06;
        };

        const handleScrollComposition = () => {
            if (!heroStage || !heroScene || publications.length === 0) {
                return;
            }

            const sectionTop = heroScene.getBoundingClientRect().top + window.scrollY;
            const sectionHeight = heroScene.offsetHeight;
            const startPoint = sectionTop - window.innerHeight * 0.25;
            const endPoint = sectionTop + sectionHeight - window.innerHeight * 0.65;
            const rawProgress = (window.scrollY + window.innerHeight * 0.25 - startPoint) / Math.max(1, endPoint - startPoint);
            const scrollProgress = clamp(rawProgress, 0, 1);
            const scenePhase = getScenePhase(scrollProgress);

            heroStage.style.setProperty('--scene-progress', scenePhase.toFixed(3));

            if (publicationCanvas) {
                const cameraZ = -70 + scenePhase * 420;
                const cameraY = -12 + scenePhase * 12;
                const cameraRotateX = -2.5 + scenePhase * 6.5;
                const cameraRotateY = scenePhase * 2.2;
                publicationCanvas.style.transform = `translate3d(0, ${cameraY}px, ${-cameraZ}px) rotateX(${cameraRotateX}deg) rotateY(${cameraRotateY}deg)`;
            }

            const heroCopy = document.querySelector('.hero-copy');
            if (heroCopy) {
                const textProgress = clamp(scrollProgress * 1.15, 0, 1);
                const copyX = -16 + Math.sin(textProgress * Math.PI * 0.7) * 18;
                const copyY = -18 + textProgress * 34;
                const copyZ = -70 + textProgress * 80;
                heroCopy.style.setProperty('--copy-x', `${copyX}px`);
                heroCopy.style.setProperty('--copy-y', `${copyY}px`);
                heroCopy.style.setProperty('--copy-z', `${copyZ}px`);
                heroCopy.style.opacity = '1';
                heroCopy.style.filter = 'none';
            }

        };

        // Book selection animation (FEATURE 3) and Book detail reveal (FEATURE 4)
        const handleBookSelection = () => {
            const bookCards = document.querySelectorAll('.book-card');
            const bookDetailPanel = document.getElementById('bookDetailPanel');
            const bookDetailTitle = bookDetailPanel ? bookDetailPanel.querySelector('.book-detail-title') : null;
            const bookDetailAuthor = bookDetailPanel ? bookDetailPanel.querySelector('.book-detail-author') : null;
            const bookDetailDescription = bookDetailPanel ? bookDetailPanel.querySelector('.book-detail-description') : null;
            const bookDetailFile = bookDetailPanel ? bookDetailPanel.querySelector('.book-detail-file') : null;
            const bookDetailCover = bookDetailPanel ? bookDetailPanel.querySelector('.book-detail-cover') : null;

            bookCards.forEach(card => {
                card.addEventListener('click', (e) => {
                    // Prevent following the link immediately to allow animation
                    e.preventDefault();

                    // Get the target URL if it's a link
                    const link = card.querySelector('a.button--primary');
                    const targetUrl = link ? link.href : null;

                    // Deselect any currently selected book
                    document.querySelectorAll('.book-selected').forEach(el => {
                        el.classList.remove('book-selected');
                    });

                    // Select this book
                    card.classList.add('book-selected');

                    // Get book data from the card
                    const title = card.querySelector('.book-card__title').textContent;
                    const author = card.querySelector('.book-card__meta span:nth-child(1)').textContent;
                    const description = card.querySelector('.book-card__description').textContent;
                    const coverImg = card.querySelector('.book-cover-image');
                    const coverSrc = coverImg ? coverImg.src : '';

                    // Update book detail panel
                    if (bookDetailPanel && bookDetailTitle && bookDetailAuthor && bookDetailDescription && bookDetailFile && bookDetailCover) {
                        bookDetailTitle.textContent = title;
                        bookDetailAuthor.textContent = author;
                        bookDetailDescription.textContent = description;
                        bookDetailFile.textContent = coverSrc ? 'PDF Available' : 'No File';
                        bookDetailCover.textContent = coverSrc ? 'Cover Available' : 'No Cover';
                        bookDetailPanel.classList.add('active');
                    }

                    // Apply selection animation to this book
                    const cardRect = card.getBoundingClientRect();
                    const cardCenterX = cardRect.left + cardRect.width / 2;
                    const cardCenterY = cardRect.top + cardRect.height / 2;
                    const viewportCenterX = window.innerWidth / 2;
                    const viewportCenterY = window.innerHeight / 2;

                    // Calculate offset from center
                    const offsetX = cardCenterX - viewportCenterX;
                    const offsetY = cardCenterY - viewportCenterY;

                    // Apply dramatic transform to selected book
                    card.style.transform = `
                        translate3d(${-offsetX * 0.3}px, ${-offsetY * 0.3}px, 200px)
                        rotateX(${-offsetY * 0.1}deg)
                        rotateY(${offsetX * 0.1}deg)
                        scale(1.8)
                    `;
                    card.style.zIndex = '1000';
                    card.style.transition = 'transform 0.8s cubic-bezier(0.22, 1, 0.36, 1), z-index 0s';
                    card.style.boxShadow = '0 40px 80px rgba(0, 0, 0, 0.6)';

                    // Apply subtle transform to other books (push them back)
                    bookCards.forEach(otherCard => {
                        if (otherCard !== card && !otherCard.classList.contains('book-selected')) {
                            const otherRect = otherCard.getBoundingClientRect();
                            const otherCenterX = otherRect.left + otherRect.width / 2;
                            const otherCenterY = otherRect.top + otherRect.height / 2;

                            // Calculate relative position to selected book
                            const relX = (otherCenterX - cardCenterX) * 0.003;
                            const relY = (otherCenterY - cardCenterY) * 0.003;

                            otherCard.style.transform = `
                                translate3d(${relX * 100}px, ${relY * 100}px, -100px)
                                scale(0.7)
                            `;
                            otherCard.style.opacity = '0.6';
                            otherCard.style.transition = 'transform 0.8s cubic-bezier(0.22, 1, 0.36, 1), opacity 0.8s ease';
                        }
                    });

                    // After animation completes, navigate to the book
                    if (targetUrl) {
                        setTimeout(() => {
                            window.location.href = targetUrl;
                        }, 1500); // Increased delay to allow time to see the animation
                    }
                });
            });
        };

        // Call the function to set up book selection
        attachBookDetailHandlers();

        const initCinematicLibraryScene = () => {
            if (window.__cinematicLibrarySceneInitialized || !window.gsap || !window.ScrollTrigger || !heroStage || publications.length === 0) {
                return;
            }

            window.__cinematicLibrarySceneInitialized = true;
            window.gsap.registerPlugin(window.ScrollTrigger);

            const stageTimeline = window.gsap.timeline({
                scrollTrigger: {
                    trigger: '.hero-immersive',
                    start: 'top top',
                    end: '+=4200',
                    scrub: 1.2,
                    pin: true,
                    pinType: 'transform',
                    anticipatePin: 1,
                    invalidateOnRefresh: true
                }
            });

            window.gsap.set(publications, {
                x: 0,
                y: 0,
                z: 0,
                rotationX: 0,
                rotationY: 0,
                rotationZ: 0,
                scale: 1,
                opacity: 1,
                filter: 'saturate(1) brightness(1)'
            });
            window.gsap.set('.book-stack', {
                x: (index, target) => parseFloat(target.dataset.baseX || '0'),
                y: (index, target) => parseFloat(target.dataset.baseY || '0'),
                z: (index, target) => parseFloat(target.dataset.baseZ || '0'),
                rotationY: (index, target) => parseFloat(target.dataset.baseRotateY || '0'),
                rotationZ: (index, target) => parseFloat(target.dataset.baseRotateZ || '0'),
                scale: 1
            });

            stageTimeline
                .to('.hero-copy', {
                    x: 18,
                    y: -28,
                    opacity: 1,
                    filter: 'none',
                    duration: 1.2,
                    ease: 'none'
                }, 0)
                .to(publicationCanvas, {
                    y: 28,
                    rotationX: 10,
                    rotationY: 8,
                    z: 18,
                    duration: 2.2,
                    ease: 'none'
                }, 0)
                .to('.hero-stage', {
                    backgroundPosition: 'center 58%',
                    duration: 2.5,
                    ease: 'none'
                }, 0.2)
                .to(publications, {
                    duration: 3.4,
                    ease: 'none',
                    x: (index, target) => [-188, -116, 0, 124, 188, -122, 122][index],
                    y: (index, target) => [-54, -34, -8, 41, 60, 92, 94][index],
                    z: (index, target) => [160, 220, 168, 210, 145, 112, 108][index],
                    rotationX: (index, target) => parseFloat(target.dataset.endRotateX || '0'),
                    rotationY: (index, target) => [-11, 8, 0, -9, 12, 10, -10][index],
                    rotationZ: (index, target) => [-5, 4, 0, 5, -5, -3, 3][index],
                    scale: (index, target) => [0.98, 1, 1.06, 1, 0.98, 0.9, 0.9][index],
                    opacity: 1,
                    filter: 'saturate(1.08) brightness(1.02)'
                }, 0.24)
                .to('.book-stack', {
                    duration: 2.8,
                    ease: 'none',
                    x: (index, target) => [-36, 29, -21, 36, 49][index],
                    y: (index, target) => [-23, -13, 16, 23, 31][index],
                    z: (index, target) => [-23, -36, -49, -62, -75][index],
                    rotationY: (index, target) => [14, -10, 12, -14, 16][index],
                    rotationZ: (index, target) => [-10, 13, 10, -9, 8][index],
                    scale: (index) => [0.99, 1, 1.02, 1, 0.98][index]
                }, 0.36);

            document.querySelectorAll('.publication').forEach((book) => {
                book.addEventListener('pointermove', (event) => {
                    if (prefersReducedMotion) return;
                    const rect = book.getBoundingClientRect();
                    const x = (event.clientX - (rect.left + rect.width / 2)) / rect.width;
                    const y = (event.clientY - (rect.top + rect.height / 2)) / rect.height;
                    window.gsap.to(book, {
                        duration: 0.32,
                        rotateY: x * 18,
                        rotateX: -y * 18,
                        y: -12,
                        z: 26,
                        ease: 'power2.out'
                    });
                });

                book.addEventListener('pointerleave', () => {
                    window.gsap.to(book, {
                        duration: 0.45,
                        rotateY: 0,
                        rotateX: 0,
                        y: 0,
                        z: 0,
                        ease: 'power2.out'
                    });
                });
            });
        };

        const updateScene = () => {
            revealOnScroll();
            handleScrollComposition();
        };

        const requestSceneUpdate = () => {
            if (ticking) {
                return;
            }
            ticking = true;
            window.requestAnimationFrame(() => {
                updateScene();
                ticking = false;
            });
        };

        const initializeLibraryWorld = () => {
            if (window.__libraryWorldInitialized) {
                return;
            }
            window.__libraryWorldInitialized = true;

            revealOnScroll();
            handleScrollComposition();
            requestSceneUpdate();
            initCinematicLibraryScene();

            window.addEventListener('scroll', requestSceneUpdate, { passive: true });
            window.addEventListener('resize', requestSceneUpdate);

            window.addEventListener('pointermove', (event) => {
                pointerX = event.clientX;
                pointerY = event.clientY;

                const x = (event.clientX / window.innerWidth - 0.5) * 35;
                const y = (event.clientY / window.innerHeight - 0.5) * 35;

                layers.forEach((layer) => {
                    const depth = parseFloat(layer.dataset.depth || '0.1');
                    layer.style.transform = `translate3d(${x * depth}px, ${y * depth}px, 0)`;
                });

                if (pageShell && !prefersReducedMotion) {
                    pageShell.style.transform = `rotateX(${(-y * 0.16).toFixed(2)}deg) rotateY(${(x * 0.24).toFixed(2)}deg) translate3d(${x * 0.2}px, ${y * 0.12}px, 0)`;
                }
            });
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeLibraryWorld);
        } else {
            initializeLibraryWorld();
        }
        window.setTimeout(initializeLibraryWorld, 0);

        window.addEventListener('load', () => {
            initializeLibraryWorld();
            window.requestAnimationFrame(requestSceneUpdate);
        });

        // 3D Navigation Controls (FEATURE 5)
        const init3DNavigation = () => {
            const navPrev = document.querySelector('.nav-prev');
            const navNext = document.querySelector('.nav-next');
            const navHome = document.querySelector('.nav-home');

            // Define sections for navigation
            const sections = [
                { id: 'explore', name: 'Explore' },
                { id: 'collections', name: 'Collections' },
                { id: 'discover', name: 'Discover' },
                { id: 'about', name: 'About' }
            ];

            let currentIndex = 0;

            // Update URL hash and scroll to section
            const navigateToSection = (index) => {
                if (index < 0) index = sections.length - 1;
                if (index >= sections.length) index = 0;
                currentIndex = index;

                const section = sections[index];
                if (section.id) {
                    // Update URL without scrolling
                    history.pushState(null, null, `#${section.id}`);

                    // Smooth scroll to section
                    const element = document.getElementById(section.id);
                    if (element) {
                        element.scrollIntoView({ behavior: 'smooth' });
                    }
                }
            };

            // Event listeners
            if (navPrev) {
                navPrev.addEventListener('click', () => {
                    navigateToSection(currentIndex - 1);
                });
            }

            if (navNext) {
                navNext.addEventListener('click', () => {
                    navigateToSection(currentIndex + 1);
                });
            }

            if (navHome) {
                navHome.addEventListener('click', () => {
                    // Go to home/top of page
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                    history.pushState(null, null, ' ');
                });
            }

            // Handle hash changes (e.g., from direct links or back/forward buttons)
            window.addEventListener('hashchange', () => {
                const hash = window.location.hash.substring(1); // Remove '#'
                const index = sections.findIndex(section => section.id === hash);
                if (index !== -1) {
                    currentIndex = index;
                } else {
                    // Default to first section if hash doesn't match
                    currentIndex = 0;
                }
            });

            // Initialize to first section on load if hash is present
            const hash = window.location.hash.substring(1);
            if (hash) {
                const index = sections.findIndex(section => section.id === hash);
                if (index !== -1) {
                    currentIndex = index;
                }
            }
        };

        // Initialize
        revealOnScroll();
        handleScrollComposition();
        init3DNavigation(); // Initialize 3D navigation controls
        if (window.ScrollTrigger) {
            window.ScrollTrigger.refresh();
        }
    </script>
</body>
</html>
