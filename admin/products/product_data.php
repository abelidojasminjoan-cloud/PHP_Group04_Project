<?php
require_once __DIR__ . '/../../services/ProductSuitability.php';

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
    $read = function (string $sql, int $parameterCount = 1) use ($conn, $productId): array {
        $stmt = $conn->prepare($sql);
        $parameters = array_fill(0, $parameterCount, $productId);
        $stmt->bind_param(str_repeat('i', $parameterCount), ...$parameters);
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
    // One prepared request for all selected taxonomy values, including inactive values.
    $selections = $read("
        SELECT 'skin_types' AS collection, s.id, s.skin_type_name AS label, s.status
        FROM product_skin_types p INNER JOIN skin_types s ON s.id=p.skin_type_id WHERE p.product_id=?
        UNION ALL
        SELECT 'concerns', s.id, s.concern_name, s.status
        FROM product_concerns p INNER JOIN skin_concerns s ON s.id=p.concern_id WHERE p.product_id=?
        UNION ALL
        SELECT 'ingredients', s.id, s.ingredient_name, s.status
        FROM product_ingredients p INNER JOIN ingredients s ON s.id=p.ingredient_id WHERE p.product_id=?
        UNION ALL
        SELECT 'suitability_tags', t.id, t.name, t.status
        FROM product_suitability_tags p INNER JOIN suitability_tags t ON t.id=p.suitability_tag_id WHERE p.product_id=?
        ORDER BY collection, id
    ", 4);
    foreach (['skin_types','concerns','ingredients','suitability_tags'] as $collection) $product[$collection]=[];
    foreach ($selections as $selection) {
        $collection=$selection['collection'];
        unset($selection['collection']);
        $product[$collection][]=$selection;
    }
    $product['suitability_settings'] = ProductSuitability::read($conn, $productId);
    $product['revision'] = hash('sha256', json_encode($product, JSON_THROW_ON_ERROR));
    return $product;
}
