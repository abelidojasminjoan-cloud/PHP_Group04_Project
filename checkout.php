
<?php

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/db.php';

function checkoutEsc(string|int|float|bool|null $value): string {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function checkoutImage(?string $path): string {
    $fallback = BASE_URL . '/assets/images/products/default-product.jpg';
    $path = trim((string) $path);

    if ($path === '') return $fallback;

    if (filter_var($path, FILTER_VALIDATE_URL)) {
        $scheme = strtolower((string) parse_url($path, PHP_URL_SCHEME));
        return in_array($scheme, ['http', 'https'], true)
            ? $path : $fallback;
    }

    $filename = basename(str_replace('\\', '/', $path));

    return is_file(__DIR__ . '/uploads/products/' . $filename)
        ? BASE_URL . '/uploads/products/' . rawurlencode($filename)
        : $fallback;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// Accept selection only from the Shopping Bag POST.
// A refresh uses the server-side saved selection.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string) ($_POST['csrf_token'] ?? '');

    if (!hash_equals((string) $_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        exit('Invalid security token.');
    }

    $posted = $_POST['selected_items'] ?? [];

    if (!is_array($posted)) {
        http_response_code(400);
        exit('Invalid cart selection.');
    }

    $ids = array_values(array_unique(array_filter(
        array_map('intval', $posted),
        static fn(int $id): bool => $id > 0
    )));

    if (!$ids || count($ids) > 100) {
        header('Location: ' . BASE_URL . '/cart.php');
        exit;
    }

    $_SESSION['checkout_selected_items'] = $ids;
    $_SESSION['checkout_token'] = bin2hex(random_bytes(32));

    header('Location: ' . BASE_URL . '/checkout.php', true, 303);
    exit;
}

$selectedIds = $_SESSION['checkout_selected_items'] ?? [];
if (empty($_SESSION['checkout_token'])) {
    $_SESSION['checkout_token'] = bin2hex(random_bytes(32));
}

if (!$selectedIds || !is_array($selectedIds)) {
    header('Location: ' . BASE_URL . '/cart.php');
    exit;
}

$items = [];
$customer = null;
$deliveryAddress = null;
$error = '';

try {
    $stmt = $conn->prepare("
        SELECT CONCAT_WS(' ', first_name, last_name) AS name, phone
        FROM users
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $customer = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$customer) {
        throw new RuntimeException('Customer account not found.');
    }

    $stmt = $conn->prepare("
        SELECT * FROM user_addresses
        WHERE user_id = ?
        ORDER BY is_default DESC, created_at ASC, id ASC
        LIMIT 1
    ");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $deliveryAddress = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $placeholders = implode(',', array_fill(
        0, count($selectedIds), '?'
    ));

    $types = 'i' . str_repeat('i', count($selectedIds));
    $params = array_merge([$userId], $selectedIds);

    $stmt = $conn->prepare("
        SELECT
            ci.id,
            ci.quantity,
            ci.product_variant_id,
            p.id AS product_id,
            p.product_name,
            p.image,
            pv.size_capacity,
            pv.variant_label,
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
          AND ci.id IN ($placeholders)
          AND p.status = 'active'
          AND c.status = 'active'
          AND pv.stock_quantity >= ci.quantity
        ORDER BY ci.id DESC
    ");

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // All selected items must remain available.
    if (count($items) !== count($selectedIds)) {
        $error = 'Some selected products are unavailable or have insufficient stock. Return to your bag and review them.';
    }

} catch (Throwable $e) {
    error_log('Checkout loading error: ' . $e->getMessage());
    $error = 'Unable to load checkout details.';
}

$subtotal = 0.0;
$totalQuantity = 0;

foreach ($items as $item) {
    $subtotal += (float) $item['price'] * (int) $item['quantity'];
    $totalQuantity += (int) $item['quantity'];
}

$shipping = $items ? 8.00 : 0.00;
$total = $subtotal + $shipping;
$canCheckout = $error === '' && count($items) > 0;

$customerName = $deliveryAddress
    ? trim($deliveryAddress['recipient_first_name'] . ' ' . $deliveryAddress['recipient_last_name'])
    : trim((string) ($customer['name'] ?? ''));
$customerPhone = trim((string) ($deliveryAddress['phone'] ?? $customer['phone'] ?? ''));
$customerAddress = $deliveryAddress
    ? implode(', ', array_filter(array_map('trim', [
        $deliveryAddress['address_line1'],
        $deliveryAddress['address_line2'] ?? '',
        $deliveryAddress['barangay'] ?? '',
        $deliveryAddress['city'],
        $deliveryAddress['province'],
        $deliveryAddress['postal_code'],
        $deliveryAddress['country']
    ]), static fn(string $part): bool => $part !== ''))
    : '';
$hasAddress = $customerAddress !== '';

function peso(float $amount): string {
    return '₱' . number_format($amount, 2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Secure Checkout | PureVia</title>

    <!-- Bootstrap 5 CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet"
          href="<?= BASE_URL ?>/assets/css/header.css">
    <link rel="stylesheet"
          href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet"
          href="<?= BASE_URL ?>/assets/css/search.css">
    <link rel="stylesheet"
          href="<?= BASE_URL ?>/assets/css/checkout.css">
    <link rel="stylesheet"
          href="<?= BASE_URL ?>/assets/css/footer.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <!-- Responsive adjustments -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/responsive.css">
</head>

<body>

<?php include __DIR__ . '/includes/header.php'; ?>

<main class="pv-checkout">

    <div class="pv-checkout-container">

        <header class="pv-checkout-heading">
            <div>
                <p class="pv-checkout-eyebrow">SECURE CHECKOUT</p>
                <h1>Complete your order</h1>
            </div>

            <a href="<?= BASE_URL ?>/cart.php"
               class="pv-checkout-back">
                <i class="fa-solid fa-chevron-left"></i>
                Back to bag
            </a>
        </header>

        <?php if ($error !== ''): ?>
            <div class="pv-checkout-error">
                <?= checkoutEsc($error) ?>
            </div>
        <?php endif; ?>

        <form id="pvCheckoutForm"
              action="<?= BASE_URL ?>/actions/checkout/place_order.php"
              method="POST"
              novalidate
              class="pv-checkout-layout">

            <input type="hidden"
                   name="csrf_token"
                   value="<?= checkoutEsc($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="checkout_token" value="<?= checkoutEsc($_SESSION['checkout_token']) ?>">

            <div class="pv-checkout-left">

                <!-- DELIVERY ADDRESS -->
                <section class="pv-checkout-card">

                    <div class="pv-checkout-card-heading">
                        <div>
                            <p class="pv-checkout-label">DELIVERY</p>
                            <h2>Delivery Address</h2>
                        </div>

                        <button type="button"
                                class="pv-checkout-text-button"
                                id="pvChangeAddress"
                                <?= !$hasAddress ? 'hidden' : '' ?>>
                            Change
                        </button>
                    </div>

                    <div id="pvNoAddress" <?= $hasAddress ? 'hidden' : '' ?>>
                        <button type="button" class="pv-save-address" id="pvAddAddress">
                            Add New Address
                        </button>
                        <p class="pv-checkout-muted">Add a delivery address to complete your order.</p>
                    </div>

                    <div class="pv-checkout-address"
                         id="pvAddressPreview"
                         <?= !$hasAddress ? 'hidden' : '' ?>>

                        <div class="pv-address-icon">
                            <i class="fa-solid fa-location-dot"></i>
                        </div>

                        <div class="pv-address-content">
                            <div class="pv-address-top">
                                <strong>Delivery address</strong>
                                <span class="pv-default-tag">
                                    DELIVERY ADDRESS
                                </span>
                            </div>

                            <strong id="pvAddressName">
                                <?= checkoutEsc($customerName) ?>
                            </strong>

                            <span id="pvAddressPhone">
                                <?= checkoutEsc($customerPhone) ?>
                            </span>

                            <p id="pvAddressText">
                                <?= checkoutEsc($customerAddress) ?>
                            </p>
                        </div>
                    </div>

                    <div class="pv-address-editor"
                         id="pvAddressEditor"
                         hidden>

                        <label>
                            Full Name
                            <input type="text"
                                   name="recipient_name"
                                   id="pvRecipientName"
                                   value="<?= checkoutEsc($customerName) ?>"
                                   maxlength="150"
                                   required>
                        </label>

                        <label>
                            Phone Number
                            <input type="tel"
                                   name="recipient_phone"
                                   id="pvRecipientPhone"
                                   value="<?= checkoutEsc($customerPhone) ?>"
                                   maxlength="30"
                                   required>
                        </label>

                        <label>
                            Complete Delivery Address
                            <textarea name="shipping_address"
                                      id="pvShippingAddress"
                                      rows="3"
                                      maxlength="255"
                                      required><?= checkoutEsc($deliveryAddress['address_line1'] ?? '') ?></textarea>
                        </label>

                        <?php foreach (['address_line2' => 'Apartment / Unit (optional)', 'barangay' => 'Barangay (optional)', 'city' => 'City / Municipality', 'province' => 'Province', 'postal_code' => 'Postal Code', 'country' => 'Country'] as $field => $label): ?>
                            <label>
                                <?= checkoutEsc($label) ?>
                                <input type="text" name="<?= $field ?>" id="pvDelivery_<?= $field ?>"
                                       maxlength="<?= $field === 'postal_code' ? 20 : ($field === 'address_line2' ? 255 : 100) ?>"
                                       value="<?= checkoutEsc($deliveryAddress[$field] ?? ($field === 'country' ? 'Philippines' : '')) ?>"
                                       <?= in_array($field, ['city', 'province', 'postal_code', 'country'], true) ? 'required' : '' ?>>
                            </label>
                        <?php endforeach; ?>

                        <button type="button"
                                class="pv-save-address"
                                id="pvSaveAddress">
                            Save Address
                        </button>
                    </div>

                </section>

                <!-- PRODUCTS ORDERED -->
                <section class="pv-checkout-card">

                    <div class="pv-checkout-card-heading">
                        <div>
                            <p class="pv-checkout-label">YOUR ORDER</p>
                            <h2>Products Ordered</h2>
                        </div>

                        <span class="pv-checkout-muted">
                            <?= $totalQuantity ?>
                            <?= $totalQuantity === 1 ? 'item' : 'items' ?>
                        </span>
                    </div>

                    <div class="pv-checkout-products-table">
                    <?php if ($items): ?>
                        <div class="pv-checkout-product-columns" aria-hidden="true">
                            <span></span>
                            <span></span>
                            <span>Unit Price</span>
                            <span>Quantity</span>
                            <span>Item Subtotal</span>
                        </div>
                    <?php endif; ?>
                    <div class="pv-checkout-products" tabindex="0" aria-label="Products ordered">

                        <?php foreach ($items as $item): ?>
                            <?php
                                $quantity = (int) $item['quantity'];
                                $lineTotal = (float) $item['price'] * $quantity;
                                $image = checkoutImage(
                                    $item['primary_image'] ?: $item['image']
                                );
                            ?>

                            <article class="pv-checkout-product">

                                <div class="pv-checkout-product-image">
                                    <img
                                        src="<?= checkoutEsc($image) ?>"
                                        alt="<?= checkoutEsc($item['product_name']) ?>"
                                        loading="lazy"
                                        onerror="this.onerror=null;this.src='<?= BASE_URL ?>/assets/images/products/default-product.jpg';"
                                    >

                                </div>

                                <div class="pv-checkout-product-info">
                                    <span class="pv-product-brand">
                                        PUREVIA
                                    </span>

                                    <strong>
                                        <?= checkoutEsc($item['product_name']) ?>
                                    </strong>

                                    <small>
                                        <?php if (trim((string) ($item['variant_label'] ?? '')) !== ''): ?>
                                            <?= checkoutEsc($item['variant_label']) ?> &middot;
                                        <?php endif; ?>
                                        <?= checkoutEsc($item['size_capacity']) ?>
                                    </small>
                                </div>

                                <span class="pv-checkout-unit-price" aria-label="Unit price">
                                    <?= peso((float) $item['price']) ?>
                                </span>

                                <span class="pv-checkout-product-quantity" aria-label="Quantity">
                                    <?= $quantity ?>
                                </span>

                                <strong class="pv-checkout-product-price" aria-label="Item subtotal">
                                    <?= peso($lineTotal) ?>
                                </strong>

                            </article>

                        <?php endforeach; ?>

                        <?php if (!$items): ?>
                            <p class="pv-checkout-muted">
                                No available products selected.
                            </p>
                        <?php endif; ?>

                    </div>
                    </div>

                </section>

                <!-- PAYMENT METHOD -->
                <section class="pv-checkout-card">

                    <div class="pv-checkout-card-heading">
                        <div>
                            <p class="pv-checkout-label">PAYMENT</p>
                            <h2>Payment Method</h2>
                        </div>
                    </div>

                    <div class="pv-payment-options">

                        <label class="pv-payment-option">
                            <input type="radio"
                                   name="payment_method"
                                   value="card">

                            <span class="pv-payment-icon">CARD</span>

                            <span class="pv-payment-copy">
                                <strong>Credit or Debit Card</strong>
                                <small>Visa, Mastercard, or JCB</small>
                            </span>

                            <span class="pv-payment-indicator"></span>
                        </label>

                        <label class="pv-payment-option">
                            <input type="radio"
                                   name="payment_method"
                                   value="gcash">

                            <span class="pv-payment-icon">G</span>

                            <span class="pv-payment-copy">
                                <strong>GCash</strong>
                                <small>Pay securely with your GCash wallet</small>
                            </span>

                            <span class="pv-payment-indicator"></span>
                        </label>

                        <label class="pv-payment-option selected">
                            <input type="radio"
                                   name="payment_method"
                                   value="cod"
                                   checked>

                            <span class="pv-payment-icon">COD</span>

                            <span class="pv-payment-copy">
                                <strong>Cash on Delivery</strong>
                                <small>Pay in cash when your order arrives</small>
                            </span>

                            <span class="pv-payment-indicator"></span>
                        </label>

                    </div>

                    <div class="pv-cod-message" id="pvCodMessage">
                        <i class="fa-solid fa-circle-check"></i>

                        <div>
                            <strong>Cash on Delivery</strong>
                            <p>
                                No online payment is required. Pay in cash when
                                your PureVia order arrives.
                            </p>
                        </div>
                    </div>

                    <div class="pv-payment-notice"
                         id="pvPaymentNotice"
                         hidden></div>

                    <p class="pv-checkout-secure">
                        <i class="fa-solid fa-lock"></i>
                        Secure checkout. PureVia never asks for your PIN or banking password.
                    </p>

                </section>

            </div>

            <!-- ORDER SUMMARY -->
            <aside class="pv-checkout-summary">

                <p class="pv-checkout-label">PAYMENT TOTAL</p>
                <h2>Order Summary</h2>

                <div class="pv-checkout-summary-count">
                    <span>Total items</span>
                    <strong><?= $totalQuantity ?></strong>
                </div>

                <div class="pv-checkout-summary-rows">
                    <div>
                        <span>Subtotal</span>
                        <strong><?= peso($subtotal) ?></strong>
                    </div>

                    <div>
                        <span>Shipping</span>
                        <strong><?= peso($shipping) ?></strong>
                    </div>
                </div>

                <div class="pv-checkout-summary-total">
                    <span>Total</span>
                    <strong><?= peso($total) ?></strong>
                </div>

                <button type="submit"
                        class="pv-place-order"
                        id="pvPlaceOrder"
                        <?= !$canCheckout ? 'disabled' : '' ?>>

                    <span id="pvPlaceOrderText">
                        Place order · <?= peso($total) ?>
                    </span>

                    <i class="fa-solid fa-chevron-right"></i>
                </button>

                <p class="pv-checkout-form-error"
                   id="pvCheckoutError"
                   role="alert"
                   hidden></p>

            </aside>

        </form>

    </div>

</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
window.PUREVIA_CHECKOUT = <?= json_encode([
    'baseUrl' => BASE_URL,
    'total' => $total,
    'canCheckout' => $canCheckout
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>

<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script src="<?= BASE_URL ?>/assets/js/search.js"></script>
<script src="<?= BASE_URL ?>/assets/js/checkout.js?v=<?= filemtime(__DIR__ . '/assets/js/checkout.js') ?>"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
