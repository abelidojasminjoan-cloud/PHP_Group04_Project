
<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';

function checkoutEsc(string|int|float|bool|null $value): string {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function checkoutImage(?string $path): string {
    $fallback = BASE_URL . '/assets/images/products/default-product.svg';
    $path = trim((string) $path);

    if ($path === '') return $fallback;

    if (filter_var($path, FILTER_VALIDATE_URL)) {
        $scheme = strtolower((string) parse_url($path, PHP_URL_SCHEME));
        return in_array($scheme, ['http', 'https'], true)
            ? $path : $fallback;
    }

    $filename = basename(str_replace('\\', '/', $path));

    return is_file(__DIR__ . '/../uploads/products/' . $filename)
        ? BASE_URL . '/uploads/products/' . rawurlencode($filename)
        : $fallback;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    header('Location: ' . BASE_URL . '/public/index.php');
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
        header('Location: ' . BASE_URL . '/public/cart.php');
        exit;
    }

    $_SESSION['checkout_selected_items'] = $ids;
    $_SESSION['checkout_token'] = bin2hex(random_bytes(32));

    header('Location: ' . BASE_URL . '/public/checkout.php', true, 303);
    exit;
}

$selectedIds = $_SESSION['checkout_selected_items'] ?? [];
if (empty($_SESSION['checkout_token'])) {
    $_SESSION['checkout_token'] = bin2hex(random_bytes(32));
}

if (!$selectedIds || !is_array($selectedIds)) {
    header('Location: ' . BASE_URL . '/public/cart.php');
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
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&amp;display=swap" rel="stylesheet">
</head>

<body>

<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="pv-checkout">

    <div class="pv-checkout-container">

        <header class="pv-checkout-heading">
            <div>
                <p class="pv-checkout-eyebrow">SECURE CHECKOUT</p>
                <h1>Complete your order</h1>
            </div>

            <a href="<?= BASE_URL ?>/public/cart.php"
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
                                        onerror="this.onerror=null;this.src='<?= BASE_URL ?>/assets/images/products/default-product.svg';"
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

                <!-- DELIVERY ADDRESS -->
                <section class="pv-checkout-card">
                    <div class="pv-checkout-card-heading">
                        <div><p class="pv-checkout-label">DELIVERY</p><h2>Delivery Address</h2></div>
                    </div>
                    <div class="pv-address-editor" id="pvAddressEditor">
                        <div class="pv-delivery-intro"><strong>Delivery details for this order</strong><p>Enter the address where you want this order delivered.</p></div>
                        <input type="hidden" name="recipient_name" id="pvRecipientName" value="">
                        <input type="hidden" name="address_line2" id="pvDelivery_address_line2" value="">
                        <div class="pv-delivery-grid">
                            <label>First Name<input type="text" id="pvRecipientFirstName" value="" maxlength="100" autocomplete="given-name" required></label>
                            <label>Last Name<input type="text" id="pvRecipientLastName" value="" maxlength="100" autocomplete="family-name" required></label>
                            <label>Phone Number<input type="tel" name="recipient_phone" id="pvRecipientPhone" value="" maxlength="30" placeholder="+63 9XX XXX XXXX" autocomplete="tel" required></label>
                            <label>Postal Code<input type="text" name="postal_code" id="pvDelivery_postal_code" value="" maxlength="20" autocomplete="postal-code" required></label>
                            <label class="pv-delivery-full">Country<select name="country" id="pvDelivery_country" autocomplete="country-name" required><option value="Philippines">Philippines</option><?php if (!empty($deliveryAddress['country']) && $deliveryAddress['country'] !== 'Philippines'): ?><option value="<?= checkoutEsc($deliveryAddress['country']) ?>"><?= checkoutEsc($deliveryAddress['country']) ?></option><?php endif; ?></select></label>
                            <label>Region<input type="text" id="pvDelivery_region" placeholder="Select region" list="pvCheckoutRegions" autocomplete="off" maxlength="100"><datalist id="pvCheckoutRegions"><option value="Metro Manila"><option value="Mindanao"><option value="North Luzon"><option value="South Luzon"><option value="Visayas"></datalist></label>
                            <?php foreach (['province' => ['Province', 'Select province'], 'city' => ['City / Municipality', 'Select city'], 'barangay' => ['Barangay', 'Select barangay']] as $field => [$label, $placeholder]): ?>
                                <label><?= checkoutEsc($label) ?><input type="text" name="<?= $field ?>" id="pvDelivery_<?= $field ?>" value="" placeholder="<?= checkoutEsc($placeholder) ?>" maxlength="100" list="pvCheckoutList_<?= $field ?>" autocomplete="<?= $field === 'province' ? 'address-level1' : ($field === 'city' ? 'address-level2' : 'address-level3') ?>" <?= $field !== 'barangay' ? 'required' : '' ?>>
                                <datalist id="pvCheckoutList_<?= $field ?>"><?php if (!empty($deliveryAddress[$field])): ?><option value="<?= checkoutEsc($deliveryAddress[$field]) ?>"><?php endif; ?></datalist></label>
                            <?php endforeach; ?>
                            <label class="pv-delivery-full pv-street-field">Street Address<textarea name="shipping_address" id="pvShippingAddress" rows="3" maxlength="255" placeholder="House or unit number, building, and street" autocomplete="address-line1" required></textarea></label>
                        </div>
                        <div class="pv-checkout-pin">
                            <span class="pv-checkout-pin-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M20 10c0 6-8 11-8 11S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg></span>
                            <div><strong>Add a Google Maps pin</strong><p>Help the courier find your exact delivery location.</p><a id="pvCheckoutPinLink" target="_blank" rel="noopener noreferrer" hidden>View pin on Google Maps</a></div>
                            <button type="button" id="pvCheckoutLocation">Use Current Location</button>
                        </div>
                        <p class="pv-checkout-location-error" id="pvCheckoutLocationError" role="alert" hidden></p>
                        <?php if ($deliveryAddress): ?>
                            <label class="pv-use-saved-address"><input type="checkbox" id="pvUseSavedAddress">Use my saved account address</label>
                        <?php endif; ?>
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

                    <div class="pv-payment-notice"
                         id="pvPaymentNotice"
                         hidden></div>

                    <p class="pv-checkout-secure">
                        <i class="fa-solid fa-lock"></i>
                        Secure payment. PureVia never stores your PIN or banking password.
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

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
window.PUREVIA_CHECKOUT = <?= json_encode([
    'baseUrl' => BASE_URL,
    'total' => $total,
    'canCheckout' => $canCheckout,
    'savedAddress' => $deliveryAddress ? array_intersect_key($deliveryAddress, array_flip([
        'recipient_first_name', 'recipient_last_name', 'phone', 'address_line1',
        'address_line2', 'province', 'city', 'barangay', 'postal_code', 'country'
    ])) : null
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>

<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script src="<?= BASE_URL ?>/assets/js/search.js"></script>
<script src="<?= BASE_URL ?>/assets/js/checkout.js?v=<?= filemtime(__DIR__ . '/../assets/js/checkout.js') ?>"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
