-- Select the hosted database before running this file (for example defaultdb).
-- Repairs a missing security_settings table without replacing existing policy.
-- Does not create a database, remove data, or import users or credentials.
CREATE TABLE IF NOT EXISTS `security_settings` (
  `id` int(10) UNSIGNED NOT NULL PRIMARY KEY,
  `max_login_attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 3,
  `lockout_minutes` int(10) UNSIGNED NOT NULL DEFAULT 15,
  `session_timeout_minutes` int(10) UNSIGNED NOT NULL DEFAULT 30,
  `password_min_length` tinyint(3) UNSIGNED NOT NULL DEFAULT 12,
  `require_uppercase` tinyint(1) NOT NULL DEFAULT 1,
  `require_lowercase` tinyint(1) NOT NULL DEFAULT 1,
  `require_number` tinyint(1) NOT NULL DEFAULT 1,
  `require_special_character` tinyint(1) NOT NULL DEFAULT 1,
  `password_expiry_days` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `require_otp` tinyint(1) NOT NULL DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `min_uppercase` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `min_lowercase` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `min_number` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `min_special_character` tinyint(3) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
INSERT INTO `security_settings` (`id`)
SELECT 1 WHERE NOT EXISTS (SELECT 1 FROM `security_settings`);