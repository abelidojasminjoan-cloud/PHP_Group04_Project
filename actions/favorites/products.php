<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function favoritesReply(array $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'GET') favoritesReply(['success' => false, 'message' => 'Invalid request method.'], 405);
$input = $_GET['ids'] ?? '';
if (!is_string($input) || strlen($input) > 2200 || ($input !== '' && !preg_match('/^[1-9]\d*(,[1-9]\d*)*$/D', $input))) {
    favoritesReply(['success' => false, 'message' => 'Invalid product selection.'], 422);
}
$ids = $input === '' ? [] : array_values(array_unique(explode(',', $input)));
if (count($ids) > 100) favoritesReply(['success' => false, 'message' => 'Too many products requested.'], 422);
foreach ($ids as $id) {
    if (filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 4294967295]]) === false) {
        favoritesReply(['success' => false, 'message' => 'Invalid product selection.'], 422);
    }
}
if (!$ids) favoritesReply(['success' => true, 'products' => []]);

function favoriteImage(?string $path): string
{
    $fallback = BASE_URL . '/assets/images/products/default-product.svg';
    $path = trim((string) $path);
    if ($path === '') return $fallback;
    if (filter_var($path, FILTER_VALIDATE_URL)) {
        return in_array(strtolower((string) parse_url($path, PHP_URL_SCHEME)), ['http', 'https'], true) ? $path : $fallback;
    }
    $filename = basename(str_replace('\\', '/', $path));
    return is_file(__DIR__ . '/../../uploads/products/' . $filename)
        ? BASE_URL . '/uploads/products/' . rawurlencode($filename) : $fallback;
}
try {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $conn->prepare("
        SELECT p.id, p.product_name, c.category_name,
               COALESCE(v.price, p.price) AS price, v.id AS variant_id,
               COALESCE(v.stock_quantity, 0) AS stock_quantity,
               COALESCE((SELECT pi.image_path FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.sort_order, pi.id LIMIT 1), p.image) AS image,
               (SELECT COUNT(*) FROM product_reviews r WHERE r.product_id = p.id AND r.status = 'approved') AS review_count,
               (SELECT COALESCE(AVG(r.rating), 0) FROM product_reviews r WHERE r.product_id = p.id AND r.status = 'approved') AS rating
        FROM products p
        JOIN categories c ON c.id = p.category_id
        LEFT JOIN product_variants v ON v.id = (
            SELECT pv.id FROM product_variants pv WHERE pv.product_id = p.id
            ORDER BY (pv.stock_quantity > 0) DESC, pv.id LIMIT 1
        )
        WHERE p.id IN ($placeholders) AND p.status = 'active' AND c.status = 'active'
    ");
    $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
    $stmt->execute();
    $products = [];
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
        $products[] = [
            'id' => (int) $row['id'], 'name' => $row['product_name'], 'category' => $row['category_name'],
            'price' => (float) $row['price'], 'variantId' => (int) ($row['variant_id'] ?? 0),
            'stock' => (int) $row['stock_quantity'], 'image' => favoriteImage($row['image']),
            'reviewCount' => (int) $row['review_count'], 'rating' => (float) $row['rating']
        ];
    }
    $stmt->close();
    favoritesReply(['success' => true, 'products' => $products]);
} catch (Throwable $error) {
    error_log('PureVia favorites products: ' . $error->getMessage());
    favoritesReply(['success' => false, 'message' => 'Unable to load saved products. Please refresh and try again.'], 500);
}
