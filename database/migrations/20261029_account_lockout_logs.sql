CREATE TABLE IF NOT EXISTS account_lockout_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    source_attempt_id BIGINT UNSIGNED NULL UNIQUE,
    name_snapshot VARCHAR(210) NOT NULL,
    email_snapshot VARCHAR(255) NOT NULL,
    failed_attempts SMALLINT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    browser VARCHAR(255) NOT NULL,
    locked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_lockout_time (locked_at, id),
    KEY idx_lockout_user (user_id),
    CONSTRAINT fk_account_lockout_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Only explicit lockout events are imported, not attempts blocked by an existing lock.
-- Old attempts did not store a failure count; do not guess it from today's policy.
INSERT INTO account_lockout_logs (user_id,source_attempt_id,name_snapshot,email_snapshot,failed_attempts,ip_address,browser,locked_at)
SELECT la.user_id,la.id,COALESCE(NULLIF(TRIM(CONCAT_WS(' ',u.first_name,u.last_name)),''),'Unknown account'),
       la.email_attempted,NULL,la.ip_address,COALESCE(NULLIF(la.browser,''),'Unknown'),la.attempted_at
FROM login_attempts la LEFT JOIN users u ON u.id=la.user_id
LEFT JOIN account_lockout_logs existing ON existing.source_attempt_id=la.id
WHERE la.successful=0 AND la.failure_reason='Account locked.' AND existing.id IS NULL;
