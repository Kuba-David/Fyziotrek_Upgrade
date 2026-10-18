CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL UNIQUE,
    name VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    reset_token_hash VARCHAR(64) NULL,
    reset_expires_at INT NULL,
    created_at VARCHAR(19) NOT NULL,
    last_login_at VARCHAR(19) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

CREATE TABLE IF NOT EXISTS courses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(190) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    summary TEXT NOT NULL,
    badge VARCHAR(255) NOT NULL DEFAULT '',
    image VARCHAR(255) NOT NULL DEFAULT '',
    location TEXT NOT NULL,
    lecturer TEXT NOT NULL,
    translation TEXT NOT NULL,
    price TEXT NOT NULL,
    price_note TEXT NOT NULL,
    accommodation TEXT NOT NULL,
    credits TEXT NOT NULL,
    description MEDIUMTEXT NOT NULL,
    ask_translation TINYINT NOT NULL DEFAULT 0,
    registration_open TINYINT NOT NULL DEFAULT 1,
    is_published TINYINT NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    legacy_paths TEXT NOT NULL,
    created_at VARCHAR(19) NOT NULL,
    updated_at VARCHAR(19) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

CREATE TABLE IF NOT EXISTS course_terms (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id INT UNSIGNED NOT NULL,
    label VARCHAR(255) NOT NULL DEFAULT '',
    date_text VARCHAR(255) NOT NULL DEFAULT '',
    start_date VARCHAR(10) NULL,
    end_date VARCHAR(10) NULL,
    registration_open TINYINT NOT NULL DEFAULT 1,
    content MEDIUMTEXT NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    INDEX idx_course_terms_course (course_id),
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

CREATE TABLE IF NOT EXISTS course_files (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id INT UNSIGNED NOT NULL,
    label VARCHAR(255) NOT NULL,
    path VARCHAR(255) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    INDEX idx_course_files_course (course_id),
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

CREATE TABLE IF NOT EXISTS applications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id INT UNSIGNED NULL,
    course_title VARCHAR(255) NOT NULL,
    terms TEXT NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(64) NOT NULL DEFAULT '',
    birth_date VARCHAR(32) NOT NULL DEFAULT '',
    billing TEXT NOT NULL,
    is_student TINYINT NOT NULL DEFAULT 0,
    needs_translation TINYINT NULL,
    discount_code VARCHAR(64) NOT NULL DEFAULT '',
    referral VARCHAR(255) NOT NULL DEFAULT '',
    note TEXT NOT NULL,
    consent_marketing TINYINT NOT NULL DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT 'nova',
    admin_note TEXT NOT NULL,
    ip VARCHAR(64) NOT NULL DEFAULT '',
    created_at VARCHAR(19) NOT NULL,
    INDEX idx_applications_course (course_id),
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

CREATE TABLE IF NOT EXISTS messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(64) NOT NULL DEFAULT '',
    message TEXT NOT NULL,
    is_read TINYINT NOT NULL DEFAULT 0,
    ip VARCHAR(64) NOT NULL DEFAULT '',
    created_at VARCHAR(19) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

CREATE TABLE IF NOT EXISTS settings (
    name VARCHAR(100) PRIMARY KEY,
    value MEDIUMTEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

CREATE TABLE IF NOT EXISTS rate_limits (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bucket VARCHAR(32) NOT NULL,
    ip VARCHAR(64) NOT NULL,
    created_at INT NOT NULL,
    INDEX idx_rate_limits (bucket, ip, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;
