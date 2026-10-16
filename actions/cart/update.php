<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function cartReply(bool $success, string $message, int $status = 200): void
{
    http_response_code($status);
    echo json_encode([
        'success' => $success,
        'message' => $message
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    cartReply(false, 'Invalid request method.', 405);
}

$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    cartReply(false, 'Please log in first.', 401);
}

$token = (string) ($_POST['csrf_token'] ?? '');

if (
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $token)
) {
    cartReply(false, 'Invalid security token.', 403);
}

$action = (string) ($_POST['action'] ?? '');
$itemId = (int) ($_POST['item_id'] ?? 0);

try {
    if ($action === 'delete_selected') {
        $ids = $_POST['item_ids'] ?? [];

        if (!is_array($ids)) {
            cartReply(false, 'Invalid items.', 400);
        }

        $ids = array_values(array_unique(array_filter(
            array_map('intval', $ids),
            static fn(int $id): bool => $id > 0
        )));

        if (!$ids || count($ids) > 100) {
            cartReply(false, 'Select valid items.', 400);
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types = 'i' . str_repeat('i', count($ids));
        $params = array_merge([$userId], $ids);

        $stmt = $conn->prepare("
            DELETE ci
            FROM cart_items ci
            INNER JOIN carts ca ON ca.id = ci.cart_id
            WHERE ca.user_id = ?
              AND ci.id IN ($placeholders)
        ");

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $stmt->close();

        cartReply(true, 'Selected products removed.');
    }

    if ($itemId <= 0) {
        cartReply(false, 'Invalid cart item.', 400);
    }

    $stmt = $conn->prepare("
        SELECT
            ci.id,
            ci.quantity,
            ci.product_variant_id,
            pv.product_id
        FROM cart_items ci
        INNER JOIN carts ca ON ca.id = ci.cart_id
        INNER JOIN product_variants pv
            ON pv.id = ci.product_variant_id
        WHERE ci.id = ?
          AND ca.user_id = ?
        LIMIT 1
    ");

    $stmt->bind_param('ii', $itemId, $userId);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$item) {
        cartReply(false, 'Cart item not found.', 404);
    }

    if ($action === 'remove') {
        $stmt = $conn->prepare("
            DELETE FROM cart_items WHERE id = ?
        ");
        $stmt->bind_param('i', $itemId);
        $stmt->execute();
        $stmt->close();

        cartReply(true, 'Product removed from your bag.');
    }

    if ($action === 'quantity') {
        $quantity = (int) ($_POST['quantity'] ?? 0);

        $stmt = $conn->prepare("
            SELECT pv.stock_quantity
            FROM product_variants pv
            INNER JOIN products p ON p.id = pv.product_id
            INNER JOIN categories c ON c.id = p.category_id
            WHERE pv.id = ?
              AND p.status = 'active'
              AND c.status = 'active'
            LIMIT 1
        ");

        $variantId = (int) $item['product_variant_id'];
        $stmt->bind_param('i', $variantId);
        $stmt->execute();
        $variant = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (
            !$variant ||
            $quantity < 1 ||
            $quantity > (int) $variant['stock_quantity']
        ) {
            cartReply(false, 'Requested quantity is unavailable.', 400);
        }

        $stmt = $conn->prepare("
            UPDATE cart_items
            SET quantity = ?
            WHERE id = ?
        ");

        $stmt->bind_param('ii', $quantity, $itemId);
        $stmt->execute();
        $stmt->close();

        cartReply(true, 'Quantity updated.');
    }

    if ($action === 'variant') {
        $newVariantId = (int) ($_POST['variant_id'] ?? 0);

        $stmt = $conn->prepare("
            SELECT pv.id, pv.stock_quantity
            FROM product_variants pv
            INNER JOIN products p ON p.id = pv.product_id
            INNER JOIN categories c ON c.id = p.category_id
            WHERE pv.id = ?
              AND pv.product_id = ?
              AND p.status = 'active'
              AND c.status = 'active'
            LIMIT 1
        ");

        $productId = (int) $item['product_id'];
        $stmt->bind_param('ii', $newVariantId, $productId);
        $stmt->execute();
        $variant = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$variant || (int) $variant['stock_quantity'] <= 0) {
            cartReply(false, 'This variation is unavailable.', 400);
        }

        $quantity = min(
            (int) $item['quantity'],
            (int) $variant['stock_quantity']
        );

        // Merge quantities if the new variation is already in the cart.
        $conn->begin_transaction();

        try {
            $stmt = $conn->prepare("
                SELECT ci.id, ci.quantity
                FROM cart_items ci
                INNER JOIN carts ca ON ca.id = ci.cart_id
                WHERE ca.user_id = ?
                  AND ci.product_variant_id = ?
                  AND ci.id <> ?
                LIMIT 1
                FOR UPDATE
            ");

            $stmt->bind_param(
                'iii',
                $userId,
                $newVariantId,
                $itemId
            );

            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($existing) {
                $mergedQuantity =
                    (int) $existing['quantity'] + $quantity;

                if ($mergedQuantity > (int) $variant['stock_quantity']) {
                    throw new RuntimeException(
                        'Not enough stock to merge these variations.'
                    );
                }

                $existingId = (int) $existing['id'];

                $stmt = $conn->prepare("
                    UPDATE cart_items
                    SET quantity = ?
                    WHERE id = ?
                ");
                $stmt->bind_param(
                    'ii',
                    $mergedQuantity,
                    $existingId
                );
                $stmt->execute();
                $stmt->close();

                $stmt = $conn->prepare("
                    DELETE FROM cart_items WHERE id = ?
                ");
                $stmt->bind_param('i', $itemId);
                $stmt->execute();
                $stmt->close();
            } else {
                $stmt = $conn->prepare("
                    UPDATE cart_items
                    SET product_variant_id = ?, quantity = ?
                    WHERE id = ?
                ");

                $stmt->bind_param(
                    'iii',
                    $newVariantId,
                    $quantity,
                    $itemId
                );

                $stmt->execute();
                $stmt->close();
            }

            $conn->commit();
        } catch (Throwable $e) {
            $conn->rollback();
            throw $e;
        }

        cartReply(true, 'Variation updated.');
    }

    cartReply(false, 'Unknown action.', 400);

} catch (Throwable $e) {
    error_log('Cart update error: ' . $e->getMessage());

    cartReply(
        false,
        $e instanceof RuntimeException
            ? $e->getMessage()
            : 'Unable to update your bag.',
        500
    );
}
