<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

function searchResponse(array $data, int $status = 200): void
{
    http_response_code($status);

    echo json_encode(
        $data,
        JSON_UNESCAPED_SLASHES |
        JSON_INVALID_UTF8_SUBSTITUTE
    );

    exit;
}

function searchImage(?string $path): string
{
    $fallback = BASE_URL .
        '/assets/images/products/default-product.jpg';

    $path = trim((string) $path);

    if ($path === '') {
        return $fallback;
    }

    if (filter_var($path, FILTER_VALIDATE_URL)) {
        $scheme = strtolower(
            (string) parse_url($path, PHP_URL_SCHEME)
        );

        return in_array($scheme, ['http', 'https'], true)
            ? $path
            : $fallback;
    }

    $filename = basename(str_replace('\\', '/', $path));

    if ($filename === '' || $filename === '.' || $filename === '..') {
        return $fallback;
    }

    $fullPath = __DIR__ .
        '/../../uploads/products/' . $filename;

    if (is_file($fullPath)) {
        return BASE_URL .
            '/uploads/products/' .
            rawurlencode($filename);
    }

    return $fallback;
}

$query = trim((string) ($_GET['q'] ?? ''));

if (mb_strlen($query) < 2) {
    searchResponse([
        'suggestions' => [],
        'products' => []
    ]);
}

if (mb_strlen($query) > 100) {
    searchResponse([
        'suggestions' => [],
        'products' => [],
        'error' => 'Search query is too long.'
    ], 400);
}

try {

    $contains = '%' . $query . '%';
    $startsWith = $query . '%';

    /*
    ==========================================
    FETCH MATCHING PRODUCTS
    ==========================================
    */

    $sql = "
        SELECT
            p.id,
            p.product_name,
            p.price,
            p.image,
            c.category_name,

            (
                SELECT pi.image_path
                FROM product_images pi
                WHERE pi.product_id = p.id
                ORDER BY pi.sort_order ASC, pi.id ASC
                LIMIT 1
            ) AS primary_image

        FROM products p

        INNER JOIN categories c
            ON c.id = p.category_id

        WHERE p.status = 'active'
          AND c.status = 'active'
          AND (
                p.product_name LIKE ?
                OR p.description LIKE ?
                OR c.category_name LIKE ?
          )

        ORDER BY
            CASE
                WHEN p.product_name = ? THEN 0
                WHEN p.product_name LIKE ? THEN 1
                ELSE 2
            END,
            p.product_name ASC

        LIMIT 6
    ";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        'sssss',
        $contains,
        $contains,
        $contains,
        $query,
        $startsWith
    );

    $stmt->execute();

    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $products = [];

    foreach ($rows as $row) {
        $products[] = [
            'id' => (int) $row['id'],
            'name' => $row['product_name'],
            'category' => $row['category_name'],
            'price' => (float) $row['price'],
            'image' => searchImage(
                $row['primary_image'] ?: $row['image']
            )
        ];
    }

    /*
    ==========================================
    FETCH CATEGORY SUGGESTIONS
    ==========================================
    */

    $suggestions = [];
    $seen = [];

    $sql = "
        SELECT DISTINCT c.category_name
        FROM categories c
        INNER JOIN products p
            ON p.category_id = c.id

        WHERE c.status = 'active'
          AND p.status = 'active'
          AND c.category_name LIKE ?

        ORDER BY c.category_name ASC
        LIMIT 6
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $contains);
    $stmt->execute();

    $categoryRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($categoryRows as $row) {
        $name = $row['category_name'];
        $key = mb_strtolower($name);

        if (!isset($seen[$key])) {
            $suggestions[] = $name;
            $seen[$key] = true;
        }
    }

    /*
    ==========================================
    FETCH PRODUCT NAME SUGGESTIONS
    ==========================================
    */

    $sql = "
        SELECT DISTINCT p.product_name
        FROM products p

        INNER JOIN categories c
            ON c.id = p.category_id

        WHERE p.status = 'active'
          AND c.status = 'active'
          AND p.product_name LIKE ?

        ORDER BY
            CASE
                WHEN p.product_name LIKE ? THEN 0
                ELSE 1
            END,
            p.product_name ASC

        LIMIT 10
    ";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        'ss',
        $contains,
        $startsWith
    );

    $stmt->execute();

    $nameRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($nameRows as $row) {
        $name = $row['product_name'];
        $key = mb_strtolower($name);

        if (!isset($seen[$key])) {
            $suggestions[] = $name;
            $seen[$key] = true;
        }

        if (count($suggestions) >= 6) {
            break;
        }
    }

    searchResponse([
        'suggestions' => array_slice($suggestions, 0, 6),
        'products' => $products
    ]);

} catch (Throwable $e) {

    error_log(
        'PureVia live search error: ' . $e->getMessage()
    );

    searchResponse([
        'suggestions' => [],
        'products' => [],
        'error' => 'Database search failed.'
    ], 500);
}