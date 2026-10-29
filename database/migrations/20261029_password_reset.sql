ALTER TABLE users ADD COLUMN session_version INT UNSIGNED NOT NULL DEFAULT 0;
CREATE TABLE IF NOT EXISTS password_reset_limits (
    key_hash CHAR(64) NOT NULL PRIMARY KEY,
    requests SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    expires_at DATETIME NOT NULL,
    KEY idx_reset_limit_expiry(expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
