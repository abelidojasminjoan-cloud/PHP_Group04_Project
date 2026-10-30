<?php
declare(strict_types=1);

/*
 * PUREVIA DETAILED SALES CSV
 * services/SalesDetailedCsv.php
 *
 * Called only from the authenticated
 * Admin Sales Report page.
 */

/* ==========================================
   DATABASE SCHEMA HELPERS
========================================== */

function pvCsvColumns(mysqli $db, string $table): array
{
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
        throw new RuntimeException('Invalid table name.');
    }

    $stmt = $db->prepare("
        SELECT COLUMN_NAME
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
    ");

    $stmt->bind_param('s', $table);
    $stmt->execute();

    $names = array_column(
        $stmt->get_result()->fetch_all(MYSQLI_ASSOC),
        'COLUMN_NAME'
    );

    $stmt->close();

    return array_fill_keys($names, true);
}

function pvCsvField(
    string $alias,
    array $columns,
    array $candidates
): string {

    foreach ($candidates as $column) {
        if (isset($columns[$column])) {
            return "`$alias`.`$column`";
        }
    }

    return 'NULL';
}


function pvCsvFullAddress(array $record): string
{
    $full = trim((string) ($record['address_full'] ?? ''));

    if ($full !== '') {
        return $full;
    }

    $parts = [];

    foreach ([
        'address_line1',
        'address_line2',
        'address_barangay',
        'address_city',
        'address_province',
        'address_postal'
    ] as $key) {

        $value = trim((string) ($record[$key] ?? ''));

        if ($value !== '' && !in_array($value, $parts, true)) {
            $parts[] = $value;
        }
    }

    return implode(', ', $parts);
}

/* ==========================================
   WRITE DETAILED PURCHASE DATA
========================================== */

function pureviaWriteDetailedSalesRows(
    mysqli $db,
    string $from,
    string $end,
    string $orderStatus,
    bool $includeAssessment,
    bool $hasRefunds,
    callable $writeCsv
): void {

    $ordersCols = pvCsvColumns($db, 'orders');
    $itemCols = pvCsvColumns($db, 'order_items');
    $userCols = pvCsvColumns($db, 'users');
    $paymentCols = pvCsvColumns($db, 'payments');
    $productCols = pvCsvColumns($db, 'products');
    $categoryCols = pvCsvColumns($db, 'categories');

    $required = [
        'orders' => [
            'id', 'user_id', 'created_at',
            'order_status', 'payment_status'
        ],
        'order_items' => [
            'id', 'order_id', 'product_name',
            'quantity', 'unit_price', 'subtotal'
        ],
        'users' => ['id']
    ];

    foreach ($required as $table => $fields) {
        $available = match ($table) {
            'orders' => $ordersCols,
            'order_items' => $itemCols,
            'users' => $userCols
        };

        foreach ($fields as $field) {
            if (!isset($available[$field])) {
                throw new RuntimeException(
                    "Missing required database column: $table.$field"
                );
            }
        }
    }

    $o = static fn(array $names): string =>
        pvCsvField('o', $ordersCols, $names);

    $oi = static fn(array $names): string =>
        pvCsvField('oi', $itemCols, $names);

    $u = static fn(array $names): string =>
        pvCsvField('u', $userCols, $names);

    /* ======================================
       PRODUCT AND CATEGORY INFORMATION
    ====================================== */

    $productJoin = '';
    $sku = 'NULL';
    $category = 'NULL';
    $variant = $oi([
        'variant_name',
        'variant_label',
        'size'
    ]);

    if (
        isset($itemCols['product_id']) &&
        isset($productCols['id'])
    ) {
        $productJoin = "
            LEFT JOIN products pr
                ON pr.id = oi.product_id
        ";

        $sku = pvCsvField('pr', $productCols, [
            'sku', 'product_code', 'code'
        ]);

        if (
            isset($productCols['category_id']) &&
            isset($categoryCols['id'])
        ) {
            $productJoin .= "
                LEFT JOIN categories ca
                    ON ca.id = pr.category_id
            ";

            $category = pvCsvField('ca', $categoryCols, [
                'category_name', 'name'
            ]);
        }
    }

    $variantCols = pvCsvColumns($db, 'product_variants');

    if (
        isset($itemCols['variant_id']) &&
        isset($variantCols['id'])
    ) {
        $productJoin .= "
            LEFT JOIN product_variants pv
                ON pv.id = oi.variant_id
        ";

        $variant = pvCsvField('pv', $variantCols, [
            'variant_name',
            'variant_label',
            'size_capacity',
            'size'
        ]);
    }

    /* ======================================
       FULL DELIVERY ADDRESS
       Prefer the address saved with order.
    ====================================== */

    $addressJoin = '';
    $addressAlias = 'o';
    $addressCols = $ordersCols;

    foreach ([
        'order_shipping_addresses',
        'order_addresses',
        'shipping_addresses'
    ] as $table) {

        $cols = pvCsvColumns($db, $table);

        if (
            isset($cols['id'], $cols['order_id'])
        ) {
            $addressJoin = "
                LEFT JOIN `$table` addr
                    ON addr.id = (
                        SELECT MAX(a2.id)
                        FROM `$table` a2
                        WHERE a2.order_id = o.id
                    )
            ";

            $addressAlias = 'addr';
            $addressCols = $cols;
            break;
        }
    }

    $address = static function (array $names) use (
        $addressAlias,
        $addressCols,
        $ordersCols
    ): string {

        $primary = pvCsvField(
            $addressAlias,
            $addressCols,
            $names
        );

        $fallback = pvCsvField(
            'o',
            $ordersCols,
            $names
        );

        if ($primary === $fallback) {
            return $primary;
        }

        if ($primary === 'NULL') {
            return $fallback;
        }

        if ($fallback === 'NULL') {
            return $primary;
        }

        return "COALESCE($primary, $fallback)";
    };

    /* ======================================
       PAYMENT DETAILS
       Use one payment summary per order.
    ====================================== */

    $paymentJoin = '';
    $paymentMethod = $o(['payment_method']);
    $paymentReference = 'NULL';
    $paymentVerifiedAt = 'NULL';
    $grossPaid = '0';

    if (
        isset(
            $paymentCols['order_id'],
            $paymentCols['amount'],
            $paymentCols['payment_status']
        )
    ) {

        $methodCol = pvCsvField('p', $paymentCols, [
            'payment_method', 'method'
        ]);

        $referenceCol = pvCsvField('p', $paymentCols, [
            'transaction_reference',
            'gateway_reference',
            'reference_number',
            'transaction_id'
        ]);

        $verifiedCol = pvCsvField('p', $paymentCols, [
            'verified_at',
            'paid_at'
        ]);

        $methodSummary = $methodCol === 'NULL'
            ? 'NULL'
            : "GROUP_CONCAT(
                DISTINCT $methodCol
                ORDER BY $methodCol
                SEPARATOR ' / '
              )";

        $referenceSummary = $referenceCol === 'NULL'
            ? 'NULL'
            : "MAX($referenceCol)";

        $verifiedSummary = $verifiedCol === 'NULL'
            ? 'NULL'
            : "MAX($verifiedCol)";

        $paymentJoin = "
            LEFT JOIN (
                SELECT
                    p.order_id,
                    $methodSummary AS methods,
                    $referenceSummary AS reference_no,
                    $verifiedSummary AS verified_date,
                    SUM(
                        CASE
                            WHEN p.payment_status IN
                                ('paid', 'refunded')
                            THEN p.amount
                            ELSE 0
                        END
                    ) AS paid_total
                FROM payments p
                GROUP BY p.order_id
            ) pay ON pay.order_id = o.id
        ";

        $paymentMethod = $paymentMethod === 'NULL'
            ? 'pay.methods'
            : "COALESCE(pay.methods, $paymentMethod)";

        $paymentReference = 'pay.reference_no';
        $paymentVerifiedAt = 'pay.verified_date';
        $grossPaid = 'COALESCE(pay.paid_total, 0)';
    }

    /* ======================================
       REFUNDS
    ====================================== */

    $refundJoin = '';
    $refundAmount = '0';

    $refundCols = $hasRefunds
        ? pvCsvColumns($db, 'refunds')
        : [];

    if (
        isset(
            $refundCols['order_id'],
            $refundCols['amount']
        )
    ) {
        $refundJoin = "
            LEFT JOIN (
                SELECT
                    order_id,
                    SUM(amount) AS refund_total
                FROM refunds
                GROUP BY order_id
            ) rf ON rf.order_id = o.id
        ";

        $refundAmount = 'COALESCE(rf.refund_total, 0)';
    }

    /* ======================================
       OPTIONAL SKIN ASSESSMENT
       Latest assessment by customer.
    ====================================== */

    $assessmentJoin = '';
    $assessmentDate = 'NULL';
    $assessmentMethod = 'NULL';
    $skinTypeResult = 'NULL';
    $skinConcerns = 'NULL';

    if ($includeAssessment) {

        foreach ([
            'skin_assessments',
            'skin_type_assessments',
            'user_skin_assessments'
        ] as $table) {

            $cols = pvCsvColumns($db, $table);

            if (
                !isset($cols['id'], $cols['user_id'])
            ) {
                continue;
            }

            $assessmentJoin = "
                LEFT JOIN `$table` sa
                    ON sa.id = (
                        SELECT MAX(sa2.id)
                        FROM `$table` sa2
                        WHERE sa2.user_id = o.user_id
                    )
            ";

            $assessmentDate = pvCsvField('sa', $cols, [
                'created_at', 'assessed_at'
            ]);

            $assessmentMethod = pvCsvField('sa', $cols, [
                'assessment_method', 'method'
            ]);

            $skinTypeResult = pvCsvField('sa', $cols, [
                'skin_type_name',
                'skin_type_label',
                'skin_type',
                'result_skin_type'
            ]);

            $skinConcerns = pvCsvField('sa', $cols, [
                'skin_concerns',
                'concerns',
                'concern_summary'
            ]);

            $typeCols = pvCsvColumns($db, 'skin_types');

            if (
                $skinTypeResult === 'NULL' &&
                isset(
                    $cols['skin_type_id'],
                    $typeCols['id']
                )
            ) {

                $assessmentJoin .= "
                    LEFT JOIN skin_types st
                        ON st.id = sa.skin_type_id
                ";

                $skinTypeResult = pvCsvField('st', $typeCols, [
                    'skin_type_name', 'name'
                ]);
            }

            break;
        }
    }

    /* ======================================
       DETAILED DATA QUERY
    ====================================== */

    $statusSql = $orderStatus === 'all'
        ? ''
        : 'AND o.order_status = ?';

    $sql = "
        SELECT
            o.id AS order_id,
            {$o(['order_number'])} AS order_number,
            o.created_at AS order_date,
            o.order_status,
            o.payment_status,

            {$o(['first_name'])} AS order_first_name,
            {$o(['last_name'])} AS order_last_name,

            {$u(['first_name'])} AS first_name,
            {$u(['last_name'])} AS last_name,
            {$u(['full_name', 'name'])} AS full_name,
            {$u(['email'])} AS email,
            {$u(['phone', 'phone_number'])} AS customer_phone,

            {$address(['recipient_name', 'shipping_name', 'full_name'])}
                AS recipient_name,

            {$address(['delivery_phone', 'shipping_phone', 'phone', 'contact_number'])}
                AS delivery_phone,

            {$address(['full_address', 'delivery_address', 'shipping_address'])}
                AS address_full,

            {$address(['address_line1', 'street_address', 'address_line', 'street'])}
                AS address_line1,

            {$address(['address_line2', 'unit', 'landmark'])}
                AS address_line2,

            {$address(['barangay', 'shipping_barangay'])}
                AS address_barangay,

            {$address(['city', 'municipality', 'shipping_city'])}
                AS address_city,

            {$address(['province', 'state', 'shipping_province'])}
                AS address_province,

            {$address(['postal_code', 'zip_code', 'zip'])}
                AS address_postal,

            oi.product_name,
            $sku AS product_sku,
            $category AS product_category,
            $variant AS variant_size,

            oi.quantity,
            oi.unit_price,
            oi.subtotal AS item_subtotal,

            {$o(['total_amount'])} AS order_total,

            $paymentMethod AS payment_method,
            $paymentReference AS payment_reference,
            $paymentVerifiedAt AS payment_verified_at,
            $grossPaid AS gross_paid,
            $refundAmount AS refunded,

            $assessmentDate AS assessment_date,
            $assessmentMethod AS assessment_method,
            $skinTypeResult AS skin_type_result,
            $skinConcerns AS skin_concerns

        FROM orders o

        INNER JOIN order_items oi
            ON oi.order_id = o.id

        LEFT JOIN users u
            ON u.id = o.user_id

        $productJoin
        $addressJoin
        $paymentJoin
        $refundJoin
        $assessmentJoin

        WHERE o.created_at >= ?
          AND o.created_at < ?
          $statusSql

        ORDER BY
            o.created_at DESC,
            o.id DESC,
            oi.id ASC
    ";

    $stmt = $db->prepare($sql);

    if ($orderStatus === 'all') {
        $stmt->bind_param('ss', $from, $end);
    } else {
        $stmt->bind_param(
            'sss',
            $from,
            $end,
            $orderStatus
        );
    }

    $stmt->execute();
    $result = $stmt->get_result();

    /* ======================================
       CSV TABLE HEADERS
    ====================================== */

    $headers = [
        'Order Number',
        'Order Date (Asia/Manila)',
        'Customer Name',
        'Customer Email',
        'Customer Phone',
        'Recipient Name',
        'Delivery Phone',
        'Full Delivery Address',
        'Product Purchased',
        'Product SKU',
        'Product Category',
        'Variant / Size',
        'Quantity',
        'Unit Price (PHP)',
        'Item Subtotal (PHP)',
        'Order Status (Current)',
        'Payment Method(s)',
        'Payment Status (Current)',
        'Payment Reference',
        'Payment Verified At',
        'Order Total (PHP - repeated)',
        'Gross Paid (PHP - repeated)',
        'Refunded (PHP - repeated)',
        'Net Paid (PHP - repeated)'
    ];

    if ($includeAssessment) {
        array_push(
            $headers,
            'Latest Assessment Date',
            'Assessment Method',
            'Skin Type Result',
            'Skin Concerns'
        );
    }

    $writeCsv($headers);

    /* ======================================
       CSV ITEM ROWS
    ====================================== */

    while ($record = $result->fetch_assoc()) {

        $orderName = trim(
            (string) ($record['order_first_name'] ?? '') .
            ' ' .
            (string) ($record['order_last_name'] ?? '')
        );

        $userName = trim(
            (string) ($record['first_name'] ?? '') .
            ' ' .
            (string) ($record['last_name'] ?? '')
        );

        $customerName = $orderName !== ''
            ? $orderName
            : (
                $userName !== ''
                    ? $userName
                    : (string) ($record['full_name'] ?? '')
            );

        $fullAddress = pvCsvFullAddress($record);

        $gross = (float) ($record['gross_paid'] ?? 0);
        $refunded = (float) ($record['refunded'] ?? 0);

        $data = [
            $record['order_number'] ?? $record['order_id'],
            $record['order_date'],
            $customerName,
            $record['email'],
            $record['customer_phone'],

            $record['recipient_name'],
            $record['delivery_phone'],
            $fullAddress,

            $record['product_name'],
            $record['product_sku'],
            $record['product_category'],
            $record['variant_size'],
            $record['quantity'],
            $record['unit_price'],
            $record['item_subtotal'],

            $record['order_status'],
            $record['payment_method'],
            $record['payment_status'],
            $record['payment_reference'],
            $record['payment_verified_at'],

            $record['order_total'],
            $gross,
            $refunded,
            $gross - $refunded
        ];

        if ($includeAssessment) {
            array_push(
                $data,
                $record['assessment_date'],
                $record['assessment_method'],
                $record['skin_type_result'],
                $record['skin_concerns']
            );
        }

        $writeCsv($data);
    }

    $stmt->close();
}

