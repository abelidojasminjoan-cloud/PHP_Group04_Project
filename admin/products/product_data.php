<?php

function productAdminAllowed(mysqli $conn): bool
{
    if (empty($_SESSION['user_id'])) return false;

    $userId = (int) $_SESSION['user_id'];
    $stmt = $conn->prepare("
        SELECT u.id FROM users u
        INNER JOIN roles r ON r.id = u.role_id
        WHERE u.id = ? AND u.status = 'active' AND LOWER(r.role_name) = 'admin'
        LIMIT 1
    ");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $allowed = $stmt->get_result()->num_rows === 1;
    $stmt->close();
    return $allowed;
}

function productEditData(mysqli $conn, int $productId, bool $lock = false): ?array
{
    $read = function (string $sql) use ($conn, $productId): array {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    };
    $locking = $lock ? ' FOR UPDATE' : '';
    $rows = $read("
        SELECT p.id, p.category_id, p.product_name, p.product_code, p.description,
            p.image, p.status, p.updated_at, c.category_name, c.status AS category_status
        FROM products p
        INNER JOIN categories c ON c.id = p.category_id
        WHERE p.id = ?
    " . $locking);
    if (!$rows) return null;

    $product = $rows[0];
    $product['variants'] = $read("
        SELECT id, size_capacity, variant_label, price, stock_quantity,
            low_stock_threshold, variant_sku, updated_at
        FROM product_variants WHERE product_id = ? ORDER BY id
    " . $locking);
    $product['images'] = $read("
        SELECT id, image_path, sort_order
        FROM product_images WHERE product_id = ? ORDER BY sort_order, id
    ");
    $product['benefits'] = array_column($read("
        SELECT benefit_text FROM product_benefits
        WHERE product_id = ? ORDER BY sort_order, id
    "), 'benefit_text');
    $product['skin_types'] = $read("
        SELECT s.id, s.skin_type_name AS label, s.status
        FROM product_skin_types p INNER JOIN skin_types s ON s.id = p.skin_type_id
        WHERE p.product_id = ? ORDER BY s.id
    ");
    $product['concerns'] = $read("
        SELECT s.id, s.concern_name AS label, s.status
        FROM product_concerns p INNER JOIN skin_concerns s ON s.id = p.concern_id
        WHERE p.product_id = ? ORDER BY s.id
    ");
    $product['ingredients'] = $read("
        SELECT s.id, s.ingredient_name AS label, s.status
        FROM product_ingredients p INNER JOIN ingredients s ON s.id = p.ingredient_id
        WHERE p.product_id = ? ORDER BY s.id
    ");
    $product['revision'] = hash('sha256', json_encode($product, JSON_THROW_ON_ERROR));
    return $product;
}
