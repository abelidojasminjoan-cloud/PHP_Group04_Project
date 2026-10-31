<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| PUREVIA - PAYPAL AND CASH ON DELIVERY CHECKOUT
| File: actions/checkout/place_order.php
|--------------------------------------------------------------------------
| No checkout-order.php helper is required.
*/

ini_set('display_errors', '0');
ini_set('log_errors', '1');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function checkoutReply(
    int $status,
    bool $success,
    string $message,
    array $extra = []
): never {
    http_response_code($status);

    echo json_encode(
        array_merge([
            'success' => $success,
            'message' => $message
        ], $extra),
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

/** Return the gateway approval URL directly after the local order is committed. */
function checkoutPayPalReply(mysqli $conn, int $userId, int $orderId, ?PayPalClient $client = null): never
{
    try {
        $row = PayPalCheckout::find($conn, $userId, $orderId);
        if ($row['state'] === 'paid') {
            $_SESSION['confirmed_order_id'] = $orderId;
            checkoutReply(200, true, 'Payment already verified.', ['order_id' => $orderId, 'redirect_url' => BASE_URL . '/order-success.php']);
        }
        $approvalUrl = PayPalCheckout::start($conn, $userId, $orderId, $client);
        checkoutReply(200, true, 'Continue to PayPal.', ['order_id' => $orderId, 'payment_provider' => 'paypal', 'redirect_url' => $approvalUrl]);
    } catch (Throwable $error) {
        error_log('PureVia PayPal handoff: ' . $error->getMessage());
        checkoutReply(502, false, 'PayPal could not be opened. Your order is saved with payment pending. Select Place order again to retry the same order, or manage it from My orders.', ['order_id' => $orderId]);
    }
}
try {
    require_once __DIR__ . '/../../config/app.php';
    require_once __DIR__ . '/../../config/db.php';

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        checkoutReply(405, false, 'Invalid request method.');
    }

    $userId = (int) ($_SESSION['user_id'] ?? 0);

    if ($userId <= 0) {
        checkoutReply(401, false, 'Please log in first.');
    }

    // Security validation
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (
        !is_string($csrfToken) ||
        empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $csrfToken)
    ) {
        checkoutReply(403, false, 'Invalid security token.');
    }

    require_once __DIR__ . '/../../services/PayPalCheckout.php';
    $paymentMethod = $_POST['payment_method'] ?? '';
    if (!in_array($paymentMethod, ['cod', 'paypal'], true)) checkoutReply(422, false, 'Choose Cash on Delivery or PayPal.');
    if ($paymentMethod !== 'cod' && !PayPalClient::configured()) checkoutReply(422, false, 'PayPal is not configured yet. Choose Cash on Delivery.');

    $checkoutToken = $_POST['checkout_token'] ?? '';

    if (!is_string($checkoutToken) || $checkoutToken === '') {
        checkoutReply(409, false, 'Invalid checkout session.');
    }

    // A durable checkout key prevents duplicate reservations across retries and sessions.
    $requestKey = substr(hash('sha256', $userId . ':' . $checkoutToken), 0, 40);
    if ($paymentMethod !== 'cod') {
        $s = $conn->prepare('SELECT pc.order_id FROM paypal_checkouts pc JOIN orders o ON o.id=pc.order_id WHERE pc.request_key=? AND o.user_id=?');
        $s->bind_param('si', $requestKey, $userId); $s->execute();
        if ($existing = $s->get_result()->fetch_assoc()) checkoutPayPalReply($conn, $userId, (int) $existing['order_id']);
    }
    if (!empty($_SESSION['paypal_pending_order_id'])) {
        $pendingId = (int) $_SESSION['paypal_pending_order_id'];
        $s = $conn->prepare("SELECT pc.order_id FROM paypal_checkouts pc JOIN orders o ON o.id=pc.order_id WHERE pc.order_id=? AND o.user_id=? AND pc.state='pending'");
        $s->bind_param('ii', $pendingId, $userId); $s->execute();
        if ($s->get_result()->fetch_assoc()) {
            if ($paymentMethod === 'paypal') checkoutPayPalReply($conn, $userId, $pendingId);
            checkoutReply(409, false, 'Finish or cancel your existing PayPal checkout from My orders before placing another order.');
        }
        unset($_SESSION['paypal_pending_order_id'], $_SESSION['paypal_pending_token']);
    }

    // Handle a repeated request after a successful order.
    if (
        !empty($_SESSION['completed_checkout_token']) &&
        hash_equals(
            $_SESSION['completed_checkout_token'],
            $checkoutToken
        ) &&
        !empty($_SESSION['confirmed_order_id'])
    ) {
        checkoutReply(
            200,
            true,
            'Order already placed.',
            ['redirect_url' => BASE_URL . '/order-success.php']
        );
    }

    if (
        empty($_SESSION['checkout_token']) ||
        !hash_equals($_SESSION['checkout_token'], $checkoutToken)
    ) {
        checkoutReply(
            409,
            false,
            'Checkout expired. Return to your shopping bag.'
        );
    }

    // Delivery information
    $fields = [
        'recipient_name',
        'recipient_phone',
        'shipping_address',
        'address_line2',
        'barangay',
        'city',
        'province',
        'postal_code',
        'country'
    ];

    $delivery = [];

    foreach ($fields as $field) {
        $value = $_POST[$field] ?? '';

        if (!is_string($value)) {
            checkoutReply(422, false, 'Invalid delivery information.');
        }

        $delivery[$field] = trim($value);
    }

    $fullName = $delivery['recipient_name'];
    $phone = $delivery['recipient_phone'];
    $addressLine1 = $delivery['shipping_address'];
    $addressLine2 = $delivery['address_line2'];
    $barangay = $delivery['barangay'];
    $city = $delivery['city'];
    $province = $delivery['province'];
    $postalCode = $delivery['postal_code'];
    $country = $delivery['country'];

    if (
        $fullName === '' ||
        $phone === '' ||
        $addressLine1 === '' ||
        $city === '' ||
        $province === '' ||
        $postalCode === '' ||
        $country === ''
    ) {
        checkoutReply(
            422,
            false,
            'Please complete your delivery information.'
        );
    }

    if (!preg_match('/^[0-9+\s()-]{7,30}$/', $phone)) {
        checkoutReply(
            422,
            false,
            'Please enter a valid phone number.'
        );
    }

    $nameParts = preg_split('/\s+/', $fullName, 2);

    $firstName = trim($nameParts[0] ?? '');
    $lastName = trim($nameParts[1] ?? '');

    if ($lastName === '') {
        checkoutReply(
            422,
            false,
            'Please enter your first and last name.'
        );
    }

    $limits = [
        'firstName' => [$firstName, 100],
        'lastName' => [$lastName, 100],
        'phone' => [$phone, 30],
        'addressLine1' => [$addressLine1, 255],
        'addressLine2' => [$addressLine2, 255],
        'barangay' => [$barangay, 100],
        'city' => [$city, 100],
        'province' => [$province, 100],
        'postalCode' => [$postalCode, 20],
        'country' => [$country, 100]
    ];

    foreach ($limits as [$value, $maxLength]) {
        if (mb_strlen($value) > $maxLength) {
            checkoutReply(
                422,
                false,
                'One or more delivery fields are too long.'
            );
        }
    }

    // Selected cart item IDs are stored in the session.
    $selectedIds = $_SESSION['checkout_selected_items'] ?? [];

    if (!is_array($selectedIds)) {
        checkoutReply(409, false, 'No products selected.');
    }

    $selectedIds = array_values(array_unique(array_filter(
        array_map('intval', $selectedIds),
        static fn(int $id): bool => $id > 0
    )));

    if (count($selectedIds) === 0 || count($selectedIds) > 100) {
        checkoutReply(
            409,
            false,
            'Return to your bag and select your products.'
        );
    }

    $inTransaction = false;

    try {
        $conn->begin_transaction();
        $inTransaction = true;

        // Confirm the account is an active customer.
        $stmt = $conn->prepare("
            SELECT email
            FROM users
            WHERE id = ?
              AND role_id = 3
              AND status = 'active'
            LIMIT 1
        ");

        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $customer = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$customer) {
            throw new DomainException(
                'Your customer account is not active.'
            );
        }

        $email = (string) $customer['email'];

        // Lock selected cart items to avoid concurrent changes.
        $placeholders = implode(
            ',',
            array_fill(0, count($selectedIds), '?')
        );

        $types = 'i' . str_repeat('i', count($selectedIds));
        $params = array_merge([$userId], $selectedIds);

        $stmt = $conn->prepare("
            SELECT
                ci.id AS cart_item_id,
                ci.product_variant_id,
                ci.quantity,
                pv.product_id,
                pv.size_capacity,
                pv.variant_label,
                pv.price,
                pv.stock_quantity,
                p.product_name,
                p.status AS product_status,
                c.status AS category_status
            FROM cart_items ci
            INNER JOIN carts ca
                ON ca.id = ci.cart_id
            INNER JOIN product_variants pv
                ON pv.id = ci.product_variant_id
            INNER JOIN products p
                ON p.id = pv.product_id
            INNER JOIN categories c
                ON c.id = p.category_id
            WHERE ca.user_id = ?
              AND ci.id IN ($placeholders)
            ORDER BY ci.id
            FOR UPDATE
        ");

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        if (count($items) !== count($selectedIds)) {
            throw new DomainException(
                'Some selected items are no longer in your bag.'
            );
        }

        $subtotalCents = 0;
        $productIds = [];

        foreach ($items as $item) {
            $quantity = (int) $item['quantity'];

            if (
                $item['product_status'] !== 'active' ||
                $item['category_status'] !== 'active'
            ) {
                throw new DomainException(
                    'A selected product is no longer available.'
                );
            }

            if ($quantity <= 0) {
                throw new DomainException(
                    'Invalid product quantity.'
                );
            }

            if ($quantity > (int) $item['stock_quantity']) {
                throw new DomainException(
                    $item['product_name'] .
                    ' does not have enough stock.'
                );
            }

            $priceCents = (int) round(
                (float) $item['price'] * 100
            );

            if ($priceCents < 0) {
                throw new DomainException(
                    'A selected product has an invalid price.'
                );
            }

            $subtotalCents += $priceCents * $quantity;
            $productIds[] = (int) $item['product_id'];
        }

        // Lock variants and recheck their stock.
        $variantIds = array_values(array_unique(array_map(
            static fn(array $item): int =>
                (int) $item['product_variant_id'],
            $items
        )));

        sort($variantIds);

        $variantPlaceholders = implode(
            ',',
            array_fill(0, count($variantIds), '?')
        );

        $variantTypes = str_repeat('i', count($variantIds));

        $stmt = $conn->prepare("
            SELECT id, stock_quantity
            FROM product_variants
            WHERE id IN ($variantPlaceholders)
            ORDER BY id
            FOR UPDATE
        ");

        $stmt->bind_param($variantTypes, ...$variantIds);
        $stmt->execute();

        $stockRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $availableStock = [];

        foreach ($stockRows as $row) {
            $availableStock[(int) $row['id']] =
                (int) $row['stock_quantity'];
        }

        foreach ($items as $item) {
            $variantId = (int) $item['product_variant_id'];
            $quantity = (int) $item['quantity'];

            if (
                !isset($availableStock[$variantId]) ||
                $availableStock[$variantId] < $quantity
            ) {
                throw new DomainException(
                    'Stock changed. Please review your shopping bag.'
                );
            }

            $availableStock[$variantId] -= $quantity;
        }

        // Server-side shipping fee.
        $shippingCents = 800;
        $totalCents = $subtotalCents + $shippingCents;

        $subtotal = number_format(
            $subtotalCents / 100,
            2,
            '.',
            ''
        );

        $shipping = number_format(
            $shippingCents / 100,
            2,
            '.',
            ''
        );

        $total = number_format(
            $totalCents / 100,
            2,
            '.',
            ''
        );

        // Generate a unique order number.
        $orderNumber = 'ORD-' .
            date('Y') . '-' .
            strtoupper(bin2hex(random_bytes(5)));

        // Save the order.
        $stmt = $conn->prepare("
            INSERT INTO orders (
                user_id,
                order_number,
                first_name,
                last_name,
                email,
                phone,
                address_line1,
                address_line2,
                barangay,
                city,
                province,
                postal_code,
                country,
                subtotal,
                shipping_fee,
                total_amount,
                payment_status,
                order_status
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, 'pending', 'pending'
            )
        ");

        $stmt->bind_param(
            'isssssssssssssss',
            $userId,
            $orderNumber,
            $firstName,
            $lastName,
            $email,
            $phone,
            $addressLine1,
            $addressLine2,
            $barangay,
            $city,
            $province,
            $postalCode,
            $country,
            $subtotal,
            $shipping,
            $total
        );

        $stmt->execute();
        $orderId = (int) $conn->insert_id;
        $stmt->close();

        // Save each purchased item.
        $itemStmt = $conn->prepare("
            INSERT INTO order_items (
                order_id,
                product_id,
                product_name,
                quantity,
                unit_price,
                subtotal
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stockStmt = $conn->prepare("
            UPDATE product_variants
            SET stock_quantity = stock_quantity - ?
            WHERE id = ?
              AND stock_quantity >= ?
        ");

        $deleteStmt = $conn->prepare("
            DELETE FROM cart_items
            WHERE id = ?
        ");

        foreach ($items as $item) {
            $productId = (int) $item['product_id'];
            $variantId = (int) $item['product_variant_id'];
            $quantity = (int) $item['quantity'];
            $cartItemId = (int) $item['cart_item_id'];

            $unitPrice = number_format(
                (float) $item['price'],
                2,
                '.',
                ''
            );

            $lineSubtotal = number_format(
                (float) $item['price'] * $quantity,
                2,
                '.',
                ''
            );

            // Preserve the purchased size in the order snapshot.
            $productName = $item['product_name'];

            $variantDescription = trim(
                (string) ($item['variant_label'] ?? '')
            );

            $size = trim((string) $item['size_capacity']);

            $variantText = trim(
                $variantDescription . ' ' . $size
            );

            if ($variantText !== '') {
                $productName .= ' (' . $variantText . ')';
            }

            // Existing order_items.product_name limit is 200.
            $productName = mb_substr($productName, 0, 200);

            $itemStmt->bind_param(
                'iisiss',
                $orderId,
                $productId,
                $productName,
                $quantity,
                $unitPrice,
                $lineSubtotal
            );

            $itemStmt->execute();

            // Deduct stock atomically.
            $stockStmt->bind_param(
                'iii',
                $quantity,
                $variantId,
                $quantity
            );

            $stockStmt->execute();

            if ($stockStmt->affected_rows !== 1) {
                throw new DomainException(
                    'Insufficient stock for ' .
                    $item['product_name'] . '.'
                );
            }

            // Remove only purchased cart items.
            $deleteStmt->bind_param('i', $cartItemId);
            if ($paymentMethod === 'cod') $deleteStmt->execute();
        }

        $itemStmt->close();
        $stockStmt->close();
        $deleteStmt->close();

        // Synchronize the parent product stock totals.
        $productIds = array_values(array_unique($productIds));
        sort($productIds);

        $syncStmt = $conn->prepare("
            UPDATE products
            SET stock_quantity = (
                SELECT COALESCE(SUM(pv.stock_quantity), 0)
                FROM product_variants pv
                WHERE pv.product_id = products.id
            )
            WHERE id = ?
        ");

        foreach ($productIds as $productId) {
            $syncStmt->bind_param('i', $productId);
            $syncStmt->execute();
        }

        $syncStmt->close();

        // Record COD as a pending payment.
        // No payment gateway or online charge occurs.
        $storedPaymentMethod = $paymentMethod !== 'cod' ? 'online' : 'cod';

        $stmt = $conn->prepare("
            INSERT INTO payments (
                order_id,
                payment_method,
                amount,
                payment_status
            ) VALUES (?, ?, ?, 'pending')
        ");

        $stmt->bind_param(
            'iss',
            $orderId,
            $storedPaymentMethod,
            $total
        );

        $stmt->execute();
        $stmt->close();

        if ($paymentMethod !== 'cod') {
            $snapshot = json_encode(['payment_preference'=>$paymentMethod,'items'=>array_map(static fn(array $item): array => [
                'cart_item_id'=>(int)$item['cart_item_id'], 'product_variant_id'=>(int)$item['product_variant_id'],
                'product_id'=>(int)$item['product_id'], 'quantity'=>(int)$item['quantity']
            ], $items)], JSON_THROW_ON_ERROR);
            $environment = PayPalClient::mode();
            $s = $conn->prepare('INSERT INTO paypal_checkouts(order_id,request_key,environment,items_snapshot) VALUES(?,?,?,?)');
            $s->bind_param('isss', $orderId, $requestKey, $environment, $snapshot); $s->execute();
        }

        // Commit all changes together.
        $conn->commit();
        $inTransaction = false;

        if ($paymentMethod !== 'cod') {
            $_SESSION['paypal_pending_order_id'] = $orderId;
            $_SESSION['paypal_pending_token'] = $checkoutToken;
            checkoutPayPalReply($conn, $userId, $orderId);
        }

        $_SESSION['confirmed_order_id'] = $orderId;
        $_SESSION['completed_checkout_token'] = $checkoutToken;

        unset(
            $_SESSION['checkout_selected_items'],
            $_SESSION['checkout_token']
        );

        // Persist completion before SMTP, so a checkout retry cannot send a second receipt.
        session_write_close();
        try {
            require_once __DIR__ . '/../../services/OrderReceipt.php';
            if (!OrderReceipt::send($conn, $orderId, $userId)) {
                error_log('PureVia order receipt was not sent for order ' . $orderId . '.');
            }
        } catch (Throwable $mailError) {
            // The order is already committed; an email failure must not undo it.
            error_log('PureVia order receipt failed for order ' . $orderId . ': ' . $mailError->getMessage());
        }

        checkoutReply(
            200,
            true,
            'Your Cash on Delivery order has been placed.',
            [
                'order_number' => $orderNumber,
                'redirect_url' => BASE_URL . '/order-success.php'
            ]
        );

    } catch (Throwable $e) {
        if ($inTransaction) {
            $conn->rollback();
        }

        error_log(
            'PureVia checkout: ' . $e->getMessage()
        );

        checkoutReply(
            $e instanceof DomainException ? 409 : 500,
            false,
            $e instanceof DomainException
                ? $e->getMessage()
                : 'Unable to place your order. Please check the server log.'
        );
    }

} catch (Throwable $e) {
    error_log(
        'PureVia checkout initialization: ' . $e->getMessage()
    );

    checkoutReply(
        500,
        false,
        'Checkout server initialization failed.'
    );
}
