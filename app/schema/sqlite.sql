PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    password_hash TEXT NOT NULL,
    reset_token_hash TEXT,
    reset_expires_at INTEGER,
    created_at TEXT NOT NULL,
    last_login_at TEXT
);

CREATE TABLE IF NOT EXISTS courses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    slug TEXT NOT NULL UNIQUE,
    title TEXT NOT NULL,
    summary TEXT NOT NULL DEFAULT '',
    badge TEXT NOT NULL DEFAULT '',
    image TEXT NOT NULL DEFAULT '',
    location TEXT NOT NULL DEFAULT '',
    lecturer TEXT NOT NULL DEFAULT '',
    translation TEXT NOT NULL DEFAULT '',
    price TEXT NOT NULL DEFAULT '',
    price_note TEXT NOT NULL DEFAULT '',
    accommodation TEXT NOT NULL DEFAULT '',
    credits TEXT NOT NULL DEFAULT '',
    description TEXT NOT NULL DEFAULT '',
    ask_translation INTEGER NOT NULL DEFAULT 0,
    registration_open INTEGER NOT NULL DEFAULT 1,
    is_published INTEGER NOT NULL DEFAULT 1,
    sort_order INTEGER NOT NULL DEFAULT 0,
    legacy_paths TEXT NOT NULL DEFAULT '',
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS course_terms (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    course_id INTEGER NOT NULL REFERENCES courses(id) ON DELETE CASCADE,
    label TEXT NOT NULL DEFAULT '',
    date_text TEXT NOT NULL DEFAULT '',
    start_date TEXT,
    end_date TEXT,
    registration_open INTEGER NOT NULL DEFAULT 1,
    content TEXT NOT NULL DEFAULT '',
    sort_order INTEGER NOT NULL DEFAULT 0
);
CREATE INDEX IF NOT EXISTS idx_course_terms_course ON course_terms(course_id);

CREATE TABLE IF NOT EXISTS course_files (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    course_id INTEGER NOT NULL REFERENCES courses(id) ON DELETE CASCADE,
    label TEXT NOT NULL,
    path TEXT NOT NULL,
    sort_order INTEGER NOT NULL DEFAULT 0
);
CREATE INDEX IF NOT EXISTS idx_course_files_course ON course_files(course_id);

CREATE TABLE IF NOT EXISTS applications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    course_id INTEGER REFERENCES courses(id) ON DELETE SET NULL,
    course_title TEXT NOT NULL,
    terms TEXT NOT NULL DEFAULT '',
    name TEXT NOT NULL,
    email TEXT NOT NULL,
    phone TEXT NOT NULL DEFAULT '',
    birth_date TEXT NOT NULL DEFAULT '',
    billing TEXT NOT NULL DEFAULT '',
    is_student INTEGER NOT NULL DEFAULT 0,
    needs_translation INTEGER,
    discount_code TEXT NOT NULL DEFAULT '',
    referral TEXT NOT NULL DEFAULT '',
    note TEXT NOT NULL DEFAULT '',
    consent_marketing INTEGER NOT NULL DEFAULT 0,
    status TEXT NOT NULL DEFAULT 'nova',
    admin_note TEXT NOT NULL DEFAULT '',
    ip TEXT NOT NULL DEFAULT '',
    created_at TEXT NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_applications_course ON applications(course_id);

CREATE TABLE IF NOT EXISTS messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL,
    phone TEXT NOT NULL DEFAULT '',
    message TEXT NOT NULL,
    is_read INTEGER NOT NULL DEFAULT 0,
    ip TEXT NOT NULL DEFAULT '',
    created_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS settings (
    name TEXT PRIMARY KEY,
    value TEXT NOT NULL DEFAULT ''
);

CREATE TABLE IF NOT EXISTS rate_limits (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    bucket TEXT NOT NULL,
    ip TEXT NOT NULL,
    created_at INTEGER NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_rate_limits ON rate_limits(bucket, ip, created_at);
