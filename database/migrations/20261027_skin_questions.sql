-- Run using tools/migrate-skin-questions.php, which also seeds the core question bank.
CREATE TABLE IF NOT EXISTS skin_questions (
 id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 question_key VARCHAR(40) NOT NULL UNIQUE,
 prompt VARCHAR(1000) NOT NULL,
 options_json TEXT NOT NULL,
 source_key VARCHAR(40) NOT NULL,
 rationale VARCHAR(2000) NOT NULL,
 concern_id INT UNSIGNED NULL,
 is_core TINYINT(1) NOT NULL DEFAULT 0,
 status ENUM('active','inactive') NOT NULL DEFAULT 'active',
 revision INT UNSIGNED NOT NULL DEFAULT 1,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
