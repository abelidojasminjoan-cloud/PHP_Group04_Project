CREATE TABLE IF NOT EXISTS paypal_checkouts (
    order_id INT UNSIGNED NOT NULL PRIMARY KEY,
    request_key CHAR(40) NOT NULL UNIQUE,
    environment ENUM('sandbox','live') NOT NULL,
    paypal_order_id VARCHAR(32) NULL UNIQUE,
    approval_url VARCHAR(512) NULL,
    capture_id VARCHAR(64) NULL UNIQUE,
    items_snapshot LONGTEXT NOT NULL,
    state ENUM('pending','paid','cancelled') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_paypal_checkout_order FOREIGN KEY (order_id) REFERENCES orders(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
