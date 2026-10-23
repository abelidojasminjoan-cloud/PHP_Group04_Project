-- MariaDB / PureVia. Additive and repeatable; existing profiles and catalog links remain intact.
ALTER TABLE skin_types ADD COLUMN IF NOT EXISTS assessment_key VARCHAR(20) DEFAULT NULL;
CREATE UNIQUE INDEX IF NOT EXISTS uq_skin_assessment_key ON skin_types(assessment_key);
UPDATE skin_types SET assessment_key=LOWER(skin_type_name) WHERE assessment_key IS NULL AND LOWER(skin_type_name) IN ('normal','dry','oily','combination');
ALTER TABLE skin_profiles
 ADD COLUMN IF NOT EXISTS assessment_method ENUM('manual','guided') DEFAULT NULL,
 ADD COLUMN IF NOT EXISTS sensitivity_score DECIMAL(5,4) DEFAULT NULL,
 ADD COLUMN IF NOT EXISTS main_concern_id INT UNSIGNED DEFAULT NULL,
 ADD COLUMN IF NOT EXISTS ingredient_preference ENUM('selected','none','unsure') NOT NULL DEFAULT 'none',
 ADD COLUMN IF NOT EXISTS avoidance_reason VARCHAR(40) DEFAULT NULL,
 ADD COLUMN IF NOT EXISTS routine_preference ENUM('basic','targeted','unsure') NOT NULL DEFAULT 'basic',
 ADD COLUMN IF NOT EXISTS assessment_answers TEXT DEFAULT NULL;
UPDATE skin_profiles SET assessment_method=IF(skin_type_source='finder','guided','manual') WHERE assessment_method IS NULL AND skin_type_source IS NOT NULL;
CREATE TABLE IF NOT EXISTS product_suitability (
 product_id INT UNSIGNED NOT NULL PRIMARY KEY,
 ingredients_complete TINYINT(1) NOT NULL DEFAULT 0,
 routine_role ENUM('none','cleanser','moisturizer','sunscreen','treatment') NOT NULL DEFAULT 'none',
 usage_period ENUM('am','pm','both') NOT NULL DEFAULT 'both',
 routine_instructions TEXT DEFAULT NULL,
 gentle TINYINT(1) NOT NULL DEFAULT 0,
 targeted_approved TINYINT(1) NOT NULL DEFAULT 0,
 sunscreen_verified TINYINT(1) NOT NULL DEFAULT 0,
 revision INT UNSIGNED NOT NULL DEFAULT 1,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_suitability_product FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS skin_assessments (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 user_id INT UNSIGNED DEFAULT NULL,
 submission_key CHAR(64) NOT NULL UNIQUE,
 assessment_method ENUM('manual','guided') NOT NULL,
 origin ENUM('quiz','profile_edit') NOT NULL DEFAULT 'quiz',
 skin_type_id INT UNSIGNED DEFAULT NULL,
 skin_type_label VARCHAR(100) DEFAULT NULL,
 clarity ENUM('clear','ambiguous','insufficient') NOT NULL,
 profile_snapshot MEDIUMTEXT NOT NULL,
 usefulness TINYINT UNSIGNED DEFAULT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_assessments_date(origin,created_at),
 INDEX idx_assessments_user(user_id),
 CONSTRAINT fk_assessment_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL,
 CONSTRAINT fk_assessment_type FOREIGN KEY(skin_type_id) REFERENCES skin_types(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO skin_concerns(concern_name,description) VALUES
 ('Excess Oil','Excess oil or shine'),('Dryness / Dehydration','Dryness or dehydration'),('Dullness','Dull-looking skin'),
 ('Dark Spots / Uneven Tone','Dark spots or uneven-looking tone'),('Large / Visible Pores','Visible pores'),
 ('Redness / Irritation','Redness or irritation'),('Fine Lines / Aging','Fine lines'),('Uneven Texture','Uneven-looking texture');
INSERT IGNORE INTO skin_concerns(concern_name,description) SELECT 'Acne','Acne or breakouts' WHERE NOT EXISTS(SELECT 1 FROM skin_concerns WHERE LOWER(concern_name) IN ('acne','acne / breakouts'));
