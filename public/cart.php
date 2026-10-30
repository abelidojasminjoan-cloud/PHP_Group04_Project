<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';

function cartEsc(?string $value): string
{
    return pvHtml($value ?? '', ENT_QUOTES, 'UTF-8');
}

function cartImage(?string $path): string
{
    $fallback = BASE_URL . '/assets/images/products/default-product.jpg';
    $path = trim((string) $path);

    if ($path === '') {
        return $fallback;
    }

    if (filter_var($path, FILTER_VALIDATE_URL)) {
        $scheme = strtolower((string) parse_url($path, PHP_URL_SCHEME));
        return in_array($scheme, ['http', 'https'], true)
            ? $path : $fallback;
    }

    $filename = basename(str_replace('\\', '/', $path));

    if ($filename === '.' || $filename === '..') {
        return $fallback;
    }

    return is_file(__DIR__ . '/../uploads/products/' . $filename)
        ? BASE_URL . '/uploads/products/' . rawurlencode($filename)
        : $fallback;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];
$userId = (int) ($_SESSION['user_id'] ?? 0);
$items = [];
$variantsByProduct = [];
$error = '';

if ($userId > 0) {
    try {
        $stmt = $conn->prepare("
            SELECT
                ci.id,
                ci.quantity,
                ci.product_variant_id,
                p.id AS product_id,
                p.product_name,
                p.image,
                p.status AS product_status,
                c.category_name,
                c.status AS category_status,
                pv.size_capacity,
                pv.price,
                pv.stock_quantity,
                (
                    SELECT pi.image_path
                    FROM product_images pi
                    WHERE pi.product_id = p.id
                    ORDER BY pi.sort_order ASC, pi.id ASC
                    LIMIT 1
                ) AS primary_image
            FROM cart_items ci
            INNER JOIN carts ca ON ca.id = ci.cart_id
            INNER JOIN product_variants pv
                ON pv.id = ci.product_variant_id
            INNER JOIN products p ON p.id = pv.product_id
            INNER JOIN categories c ON c.id = p.category_id
            WHERE ca.user_id = ?
            ORDER BY ci.id DESC
        ");

        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $productIds = array_values(array_unique(array_map(
            static fn(array $item): int => (int) $item['product_id'],
            $items
        )));

        if ($productIds) {
            $placeholders = implode(',', array_fill(
                0,
                count($productIds),
                '?'
            ));

            $types = str_repeat('i', count($productIds));

            $stmt = $conn->prepare("
                SELECT
                    id,
                    product_id,
                    size_capacity,
                    price,
                    stock_quantity
                FROM product_variants
                WHERE product_id IN ($placeholders)
                ORDER BY id ASC
            ");

            $stmt->bind_param($types, ...$productIds);
            $stmt->execute();

            foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $variant) {
                $variantsByProduct[(int) $variant['product_id']][] = $variant;
            }

            $stmt->close();
        }
    } catch (Throwable $e) {
        error_log('Cart loading error: ' . $e->getMessage());
        $error = 'Unable to load your shopping bag.';
    }
}

$pageTitle = 'Shopping Bag | PureVia';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= cartEsc($pageTitle) ?></title>

    <!-- Bootstrap 5 CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet"
          href="<?= BASE_URL ?>/assets/css/header.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/search.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/cart.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/footer.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <!-- Responsive adjustments -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/responsive.css">
</head>

<body>

<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="pv-cart-page">

    <div class="pv-cart-container">

        <div class="pv-cart-heading">
            <div>
                <p class="pv-cart-eyebrow">YOUR PUREVIA ROUTINE</p>
                <h1>Shopping Bag</h1>
            </div>

            <span class="pv-cart-item-count" id="cartItemCount">
                <?= count($items) ?>
                <?= count($items) === 1 ? 'item' : 'items' ?>
            </span>
        </div>

        <?php if ($userId <= 0): ?>

            <section class="pv-cart-empty">
                <i class="fa-solid fa-bag-shopping"></i>
                <h2>Sign in to view your shopping bag</h2>
                <p>Your saved skincare products will appear here.</p>
                <a href="<?= BASE_URL ?>/index.php">
                    Return to PureVia
                </a>
            </section>

        <?php elseif ($error !== ''): ?>

            <p class="pv-cart-error"><?= cartEsc($error) ?></p>

        <?php else: ?>

            <div class="pv-cart-layout">

                <section class="pv-cart-left">

                    <div class="pv-cart-toolbar">

                        <label class="pv-cart-select-all">
                            <input
                                type="checkbox"
                                id="cartSelectAll"
                                <?= $items ? 'checked' : 'disabled' ?>
                            >
                            <span>
                                Select All
                                (<span id="cartSelectCount"><?= count($items) ?></span>)
                            </span>
                        </label>

                        <button
                            type="button"
                            id="cartDeleteSelected"
                            class="pv-cart-toolbar-button"
                        >
                            Delete
                        </button>

                        <button
                            type="button"
                            id="cartRemoveInactive"
                            class="pv-cart-toolbar-button pv-cart-muted"
                        >
                            Remove inactive products
                        </button>

                        <button
                            type="button"
                            id="cartMoveLikes"
                            class="pv-cart-likes"
                            title="Favorites integration required"
                            disabled
                        >
                            Move to My Likes
                        </button>

                    </div>

                    <div class="pv-cart-message" id="cartMessage"
                         role="status" hidden></div>

                    <div class="pv-cart-items" id="cartItems">

                        <?php foreach ($items as $item): ?>

                            <?php
                                $itemId = (int) $item['id'];
                                $productId = (int) $item['product_id'];
                                $stock = (int) $item['stock_quantity'];
                                $quantity = (int) $item['quantity'];

                                $active =
                                    $item['product_status'] === 'active' &&
                                    $item['category_status'] === 'active' &&
                                    $stock > 0;

                                $image = cartImage(
                                    $item['primary_image'] ?: $item['image']
                                );
                            ?>

                            <article
                                class="pv-cart-item <?= !$active ? 'is-inactive' : '' ?>"
                                data-item-id="<?= $itemId ?>"
                                data-price="<?= (float) $item['price'] ?>"
                                data-stock="<?= $stock ?>"
                                data-active="<?= $active ? '1' : '0' ?>"
                            >

                                <label class="pv-cart-check">
                                    <input
                                        type="checkbox"
                                        class="cart-item-check"
                                        <?= $active ? 'checked' : 'disabled' ?>
                                        aria-label="Select <?= cartEsc($item['product_name']) ?>"
                                    >
                                </label>

                                <a
                                    class="pv-cart-image"
                                    href="<?= BASE_URL ?>/product.php?id=<?= $productId ?>"
                                >
                                    <img
                                        src="<?= cartEsc($image) ?>"
                                        alt="<?= cartEsc($item['product_name']) ?>"
                                        loading="lazy"
                                        data-image-fallback="<?= BASE_URL ?>/assets/images/products/default-product.jpg"
                                    >
                                </a>

                                <div class="pv-cart-product-info">

                                    <p class="pv-cart-category">
                                        <?= cartEsc(strtoupper($item['category_name'])) ?>
                                    </p>

                                    <a
                                        class="pv-cart-product-name"
                                        href="<?= BASE_URL ?>/product.php?id=<?= $productId ?>"
                                    >
                                        <?= cartEsc($item['product_name']) ?>
                                    </a>

                                    <div class="pv-cart-variation">
                                        <span>VARIATION</span>

                                        <select
                                            class="cart-variant-select"
                                            aria-label="Product variation"
                                            <?= !$active ? 'disabled' : '' ?>
                                        >
                                            <?php foreach (
                                                $variantsByProduct[$productId] ?? []
                                                as $variant
                                            ): ?>
                                                <option
                                                    value="<?= (int) $variant['id'] ?>"
                                                    <?= (int) $variant['id'] === (int) $item['product_variant_id']
                                                        ? 'selected' : '' ?>
                                                    <?= (int) $variant['stock_quantity'] <= 0
                                                        ? 'disabled' : '' ?>
                                                >
                                                    <?= cartEsc($variant['size_capacity']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="pv-cart-bottom">

                                        <div class="pv-cart-quantity">
                                            <button
                                                type="button"
                                                class="cart-qty-minus"
                                                aria-label="Decrease quantity"
                                                <?= !$active ? 'disabled' : '' ?>
                                            >−</button>

                                            <span class="cart-qty-value">
                                                <?= $quantity ?>
                                            </span>

                                            <button
                                                type="button"
                                                class="cart-qty-plus"
                                                aria-label="Increase quantity"
                                                <?= !$active ? 'disabled' : '' ?>
                                            >+</button>
                                        </div>

                                        <?php if (!$active): ?>
                                            <span class="pv-cart-stock-warning">
                                                Currently unavailable
                                            </span>
                                        <?php endif; ?>

                                    </div>

                                </div>

                                <div class="pv-cart-item-right">

                                    <button
                                        type="button"
                                        class="cart-remove-item"
                                        aria-label="Remove item"
                                    >
                                        <i class="fa-regular fa-trash-can"></i>
                                    </button>

                                    <div class="pv-cart-item-pricing">
                                        <small>
                                            ₱<?= number_format((float) $item['price'], 2) ?> each
                                        </small>

                                        <strong class="cart-line-total">
                                            ₱<?= number_format(
                                                (float) $item['price'] * $quantity,
                                                2
                                            ) ?>
                                        </strong>
                                    </div>

                                </div>

                            </article>

                        <?php endforeach; ?>

                    </div>

                    <div class="pv-cart-empty" id="cartEmpty"
                         <?= $items ? 'hidden' : '' ?>>
                        <i class="fa-solid fa-bag-shopping"></i>
                        <h2>Your shopping bag is empty</h2>
                        <p>Explore skincare essentials made for your routine.</p>
                        <a href="<?= BASE_URL ?>/shop.php">
                            Shop Products
                        </a>
                    </div>

                    <a
                        href="<?= BASE_URL ?>/shop.php"
                        class="pv-cart-continue"
                    >
                        <i class="fa-solid fa-chevron-left"></i>
                        Continue shopping
                    </a>

                </section>

                <aside class="pv-cart-summary">

                    <h2>Order Summary</h2>

                    <p class="pv-cart-selected-count" id="cartSelectedText">
                        0 items selected
                    </p>

                    <div class="pv-cart-summary-details">

                        <div class="pv-cart-summary-row">
                            <span>Subtotal</span>
                            <strong id="cartSubtotal">₱0.00</strong>
                        </div>

                        <div class="pv-cart-summary-row">
                            <span>Shipping</span>
                            <strong id="cartShipping">₱0.00</strong>
                        </div>

                    </div>

                    <div class="pv-cart-summary-total">
                        <span>Total</span>
                        <strong id="cartTotal">₱0.00</strong>
                    </div>

                    <p class="pv-cart-summary-note">
                        Shipping shown is an estimate. Final shipping
                        fees and taxes are confirmed at checkout.
                    </p>

                    <button
                        type="button"
                        class="pv-cart-checkout"
                        id="cartCheckout"
                        disabled
                    >
                        <span>Proceed to Checkout</span>
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>

                    <div class="pv-cart-secure">
                        <i class="fa-solid fa-lock"></i>
                        <span>Secure checkout</span>
                    </div>

                </aside>

            </div>

        <?php endif; ?>

    </div>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script nonce="<?= pvScriptNonce() ?>">
window.PUREVIA_CART = <?= pvJson([
    'baseUrl' => BASE_URL,
    'csrfToken' => $csrfToken,
    'shippingFee' => 8.00
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>

<script nonce="<?= pvScriptNonce() ?>" src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script nonce="<?= pvScriptNonce() ?>" src="<?= BASE_URL ?>/assets/js/search.js?v=public-root-1"></script>
<script nonce="<?= pvScriptNonce() ?>" src="<?= BASE_URL ?>/assets/js/cart.js?v=public-root-1"></script>

<script nonce="<?= pvScriptNonce() ?>" src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
