PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS software (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    software_type TEXT NOT NULL CHECK (software_type IN ('wp_plugin', 'other')),
    name TEXT NOT NULL,
    software_url TEXT NOT NULL,
    slug TEXT NULL,
    canonical_url TEXT NULL,
    description TEXT NULL,
    plugin_icon_url TEXT NULL,
    plugin_banner_url TEXT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);

CREATE UNIQUE INDEX IF NOT EXISTS idx_software_unique_name_url
    ON software (name, software_url);

CREATE TABLE IF NOT EXISTS template_emails (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email_address TEXT NOT NULL UNIQUE,
    expected_valid INTEGER NOT NULL CHECK (expected_valid IN (0, 1)),
    severity_level TEXT NOT NULL CHECK (severity_level IN ('high', 'medium', 'low')),
    complexity_label TEXT NOT NULL,
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
    created_at TEXT NOT NULL,
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
    severity_level TEXT NOT NULL CHECK (severity_level IN ('high', 'medium', 'low')),
    created_at TEXT NOT NULL,
    FOREIGN KEY (submission_id) REFERENCES submissions (id) ON DELETE CASCADE,
    FOREIGN KEY (template_email_id) REFERENCES template_emails (id) ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS idx_submission_tests_submission_id ON submission_tests (submission_id);

INSERT OR IGNORE INTO template_emails (id, email_address, expected_valid, severity_level, complexity_label) VALUES
    (1, 'max@müller.de', 1, 'high', 'Simple IDN domain'),
    (2, 'info@büro.at', 1, 'high', 'Simple IDN domain'),
    (3, 'max@info.versicherung', 1, 'high', 'Long TLD usage'),
    (4, 'max@newsletter.müller.de', 1, 'medium', 'Subdomain with IDN label'),
    (5, 'max@news.info.versicherung', 1, 'medium', 'Nested subdomain + long TLD'),
    (6, '用户@例子.广告', 1, 'low', 'Complex-script IDN case'),
    (7, 'θσερ@εχαμπλε.ψομ', 1, 'low', 'Greek-script IDN case');
