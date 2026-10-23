-- Reusable suitability labels are separate from per-product ingredient/routine settings.
CREATE TABLE IF NOT EXISTS suitability_tags (
 id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100) NOT NULL UNIQUE,
 description TEXT DEFAULT NULL,
 status ENUM('active','inactive') NOT NULL DEFAULT 'active',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS product_suitability_tags (
 product_id INT UNSIGNED NOT NULL,
 suitability_tag_id INT UNSIGNED NOT NULL,
 PRIMARY KEY(product_id,suitability_tag_id),
 CONSTRAINT fk_product_suitability_tag_product FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE,
 CONSTRAINT fk_product_suitability_tag_label FOREIGN KEY(suitability_tag_id) REFERENCES suitability_tags(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO suitability_tags(name,description) VALUES
 ('Daily Use','Appropriate for consistent everyday routines.'),
 ('Beginner Friendly','Suitable for customers new to active skincare.'),
 ('Sensitive Skin Friendly','Formulated with reactive skin in mind.'),
 ('Pregnancy Friendly','Free from ingredients commonly avoided during pregnancy.'),
 ('Fragrance Free','Made without added fragrance.');
