CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'student',
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS books (
    id INTEGER PRIMARY KEY,
    title TEXT NOT NULL,
    author TEXT NOT NULL,
    description TEXT NOT NULL,
    file TEXT NOT NULL,
    cover TEXT
);

CREATE TABLE IF NOT EXISTS downloads (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    book_id INTEGER NOT NULL,
    downloaded_at TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (book_id) REFERENCES books(id)
);

CREATE TABLE IF NOT EXISTS book_views (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    book_id INTEGER NOT NULL,
    viewed_at TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (book_id) REFERENCES books(id)
);

CREATE INDEX IF NOT EXISTS idx_book_views_user_time ON book_views(user_id, viewed_at DESC);
CREATE INDEX IF NOT EXISTS idx_book_views_book_time ON book_views(book_id, viewed_at DESC);

INSERT OR IGNORE INTO books (id, title, author, description, file) VALUES
    (1, 'Modern Web Design', 'Avery Lane', 'A quick guide to modern responsive layout and CSS trends.', 'modern-web-design.pdf'),
    (2, 'Intro to PHP', 'Jordan Cruz', 'Learn PHP fundamentals with concise examples and practical apps.', 'intro-to-php.pdf'),
    (3, 'Digital Library UX', 'Mia Chen', 'Designing clean, accessible digital reading experiences for everyone.', 'digital-library-ux.pdf');
