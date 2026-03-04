PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS software (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    type TEXT NOT NULL CHECK (type IN ('wp_plugin', 'other')),
    slug TEXT NULL,
    canonical_url TEXT NOT NULL,
    name TEXT NOT NULL,
    description TEXT NULL,
    wp_version_tested TEXT NULL,
    plugin_icon_url TEXT NULL,
    plugin_banner_url TEXT NULL,
    is_hidden INTEGER NOT NULL DEFAULT 0 CHECK (is_hidden IN (0, 1)),
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE UNIQUE INDEX IF NOT EXISTS idx_software_unique_identity
    ON software (type, COALESCE(slug, canonical_url));

CREATE TABLE IF NOT EXISTS admin_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    admin_token TEXT NOT NULL UNIQUE,
    password_hash TEXT NULL,
    is_active INTEGER NOT NULL DEFAULT 1 CHECK (is_active IN (0, 1)),
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS template_emails (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email_address TEXT NOT NULL UNIQUE,
    expected_valid INTEGER NOT NULL CHECK (expected_valid IN (0, 1)),
    severity_weight INTEGER NOT NULL CHECK (severity_weight IN (1, 2, 3)),
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS submissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    software_id INTEGER NOT NULL,
    wordpress_version TEXT NULL,
    submitter_name TEXT NOT NULL,
    submitter_email TEXT NOT NULL,
    submitter_role TEXT NULL CHECK (submitter_role IN ('developer', 'user') OR submitter_role IS NULL),
    submission_comment TEXT NULL,
    severity_auto TEXT NOT NULL CHECK (severity_auto IN ('none', 'low', 'medium', 'high')),
    severity_admin_override TEXT NULL CHECK (severity_admin_override IN ('none', 'low', 'medium', 'high') OR severity_admin_override IS NULL),
    is_hidden INTEGER NOT NULL DEFAULT 0 CHECK (is_hidden IN (0, 1)),
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (software_id) REFERENCES software (id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_submissions_software_id ON submissions (software_id);
CREATE INDEX IF NOT EXISTS idx_submissions_created_at ON submissions (created_at DESC);

CREATE TABLE IF NOT EXISTS submission_tests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    submission_id INTEGER NOT NULL,
    template_email_id INTEGER NOT NULL,
    email_address TEXT NOT NULL,
    expected_valid INTEGER NOT NULL CHECK (expected_valid IN (0, 1)),
    actual_result TEXT NOT NULL CHECK (actual_result IN ('accepted', 'rejected')),
    failure_detected INTEGER NOT NULL CHECK (failure_detected IN (0, 1)),
    severity_weight INTEGER NOT NULL CHECK (severity_weight IN (1, 2, 3)),
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (submission_id) REFERENCES submissions (id) ON DELETE CASCADE,
    FOREIGN KEY (template_email_id) REFERENCES template_emails (id) ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS idx_submission_tests_submission_id ON submission_tests (submission_id);

CREATE TABLE IF NOT EXISTS plugin_comments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    software_id INTEGER NOT NULL,
    author_name TEXT NOT NULL,
    author_role TEXT NOT NULL CHECK (author_role IN ('admin', 'user')),
    comment TEXT NOT NULL,
    is_admin_solution INTEGER NOT NULL DEFAULT 0 CHECK (is_admin_solution IN (0, 1)),
    is_hidden INTEGER NOT NULL DEFAULT 0 CHECK (is_hidden IN (0, 1)),
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (software_id) REFERENCES software (id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_plugin_comments_software_id ON plugin_comments (software_id);

CREATE TABLE IF NOT EXISTS submission_comments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    submission_id INTEGER NOT NULL,
    author_name TEXT NOT NULL,
    author_role TEXT NOT NULL,
    comment TEXT NOT NULL,
    is_hidden INTEGER NOT NULL DEFAULT 0 CHECK (is_hidden IN (0, 1)),
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (submission_id) REFERENCES submissions (id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_submission_comments_submission_id ON submission_comments (submission_id);

INSERT OR IGNORE INTO template_emails (id, email_address, expected_valid, severity_weight) VALUES
    (1, 'max@müller.de', 1, 3),
    (2, 'info@büro.at', 1, 3),
    (3, 'max@info.versicherung', 1, 3),
    (4, 'max@newsletter.müller.de', 1, 2),
    (5, 'max@news.info.versicherung', 1, 2),
    (6, '用户@例子.广告', 1, 1),
    (7, 'max@-müller.de', 0, 3),
    (8, 'max@müller..de', 0, 3),
    (9, 'max@müller', 0, 3);
