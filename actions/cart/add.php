<?php

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function sendCartResponse(
    int $status,
    bool $success,
    string $message,
    array $data = []
): never {
    http_response_code($status);

    echo json_encode(
        array_merge([
            'success' => $success,
            'message' => $message
        ], $data),
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendCartResponse(405, false, 'Invalid request method.');
}

$userId = (int) ($_SESSION['user_id'] ?? 0);
$role = strtolower((string) ($_SESSION['role'] ?? ''));

if ($userId <= 0 || $role !== 'customer') {
    sendCartResponse(401, false, 'Please log in first.', [
        'login_required' => true
    ]);
}

$csrfToken = (string) ($_POST['csrf_token'] ?? '');

if (
    empty($_SESSION['csrf_token']) ||
    !hash_equals(
        (string) $_SESSION['csrf_token'],
        $csrfToken
    )
) {
    sendCartResponse(
        403,
        false,
        'Security session expired. Please refresh the page.'
    );
}

$productId = filter_var(
    $_POST['product_id'] ?? null,
    FILTER_VALIDATE_INT
);

$variantId = filter_var(
    $_POST['product_variant_id'] ?? null,
    FILTER_VALIDATE_INT
);

$quantity = filter_var(
    $_POST['quantity'] ?? null,
    FILTER_VALIDATE_INT
);

if (
    !$productId ||
    !$variantId ||
    !$quantity ||
    $productId < 1 ||
    $variantId < 1 ||
    $quantity < 1 ||
    $quantity > 99
) {
    sendCartResponse(
        422,
        false,
        'Please select a valid product, size and quantity.'
    );
}

$inTransaction = false;

try {
    $conn->begin_transaction();
    $inTransaction = true;

    // Verify the product and selected variant.
    $stmt = $conn->prepare("
        SELECT
            pv.id,
            pv.product_id,
            pv.size_capacity,
            pv.stock_quantity,
            p.product_name
        FROM product_variants pv
        INNER JOIN products p
            ON p.id = pv.product_id
        INNER JOIN categories c
            ON c.id = p.category_id
        WHERE pv.id = ?
          AND pv.product_id = ?
          AND p.status = 'active'
          AND c.status = 'active'
        LIMIT 1
        FOR UPDATE
    ");

    $stmt->bind_param('ii', $variantId, $productId);
    $stmt->execute();

    $variant = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$variant) {
        $conn->rollback();

        sendCartResponse(
            404,
            false,
            'The selected product or size is unavailable.'
        );
    }

    $stock = (int) $variant['stock_quantity'];

    if ($stock < 1) {
        $conn->rollback();

        sendCartResponse(
            409,
            false,
            'This product size is out of stock.'
        );
    }

    // Find the customer's cart.
    $stmt = $conn->prepare("
        SELECT id
        FROM carts
        WHERE user_id = ?
        LIMIT 1
        FOR UPDATE
    ");

    $stmt->bind_param('i', $userId);
    $stmt->execute();

    $cart = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($cart) {
        $cartId = (int) $cart['id'];
    } else {
        $stmt = $conn->prepare("
            INSERT INTO carts (user_id)
            VALUES (?)
        ");

        $stmt->bind_param('i', $userId);
        $stmt->execute();

        $cartId = (int) $conn->insert_id;
        $stmt->close();
    }

    // Check whether the same variant already exists.
    $stmt = $conn->prepare("
        SELECT id, quantity
        FROM cart_items
        WHERE cart_id = ?
          AND product_variant_id = ?
        LIMIT 1
        FOR UPDATE
    ");

    $stmt->bind_param('ii', $cartId, $variantId);
    $stmt->execute();

    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $currentQuantity = $existing
        ? (int) $existing['quantity']
        : 0;

    $newQuantity = $currentQuantity + $quantity;

    if ($newQuantity > $stock) {
        $conn->rollback();

        sendCartResponse(
            409,
            false,
            "Only {$stock} available. Your bag already contains {$currentQuantity}."
        );
    }

    if ($existing) {
        $itemId = (int) $existing['id'];

        $stmt = $conn->prepare("
            UPDATE cart_items
            SET quantity = ?
            WHERE id = ?
              AND cart_id = ?
        ");

        $stmt->bind_param(
            'iii',
            $newQuantity,
            $itemId,
            $cartId
        );

        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conn->prepare("
            INSERT INTO cart_items (
                cart_id,
                product_variant_id,
                quantity
            )
            VALUES (?, ?, ?)
        ");

        $stmt->bind_param(
            'iii',
            $cartId,
            $variantId,
            $quantity
        );

        $stmt->execute();
        $stmt->close();
    }

    // Return the updated header bag count.
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(quantity), 0) AS total
        FROM cart_items
        WHERE cart_id = ?
    ");

    $stmt->bind_param('i', $cartId);
    $stmt->execute();

    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $cartCount = (int) ($row['total'] ?? 0);

    $conn->commit();
    $inTransaction = false;

    sendCartResponse(
        200,
        true,
        'Product added to your bag!',
        [
            'cart_count' => $cartCount,
            'product_name' => $variant['product_name'],
            'size' => $variant['size_capacity'],
            'quantity' => $newQuantity
        ]
    );

} catch (Throwable $error) {
    if ($inTransaction) {
        $conn->rollback();
    }

    error_log(
        'PureVia add-to-cart error: ' .
        $error->getMessage()
    );

    sendCartResponse(
        500,
        false,
        'Unable to add product. Please try again.'
    );
}
