
<?php
/*
================================================
PUREVIA - SALES REPORT BACKEND
Read-only database reporting
================================================
*/


require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';


/*
The existing config/db.php is expected to
provide a MySQLi connection named $conn.
*/

if (!isset($conn) || !($conn instanceof mysqli)) {
    throw new RuntimeException(
        'MySQLi database connection unavailable.'
    );
}

/* ================================================
   SUMMARY
================================================ */

$summary = [
    'total_revenue' => 0.00,
    'total_orders' => 0,
    'average_order_value' => 0.00,
    'delivery_rate' => 0.00
];

/*
Paid sales only:
- Exclude cancelled orders.
- Count one order only once.
- Use total_amount, including shipping.
*/

$sql = "
    SELECT
        COUNT(*) AS total_orders,

        COALESCE(SUM(
            CASE
                WHEN payment_status = 'paid'
                     AND order_status <> 'cancelled'
                THEN total_amount
                ELSE 0
            END
        ), 0) AS total_revenue,

        SUM(
            CASE
                WHEN payment_status = 'paid'
                     AND order_status <> 'cancelled'
                THEN 1
                ELSE 0
            END
        ) AS paid_orders,

        SUM(
            CASE
                WHEN order_status IN (
                    'delivered',
                    'completed'
                )
                THEN 1
                ELSE 0
            END
        ) AS delivered_orders

    FROM orders
";

$result = $conn->query($sql);

if (!$result) {
    throw new RuntimeException(
        'Unable to load sales summary.'
    );
}

$data = $result->fetch_assoc();

$totalOrders = (int) $data['total_orders'];
$paidOrders = (int) $data['paid_orders'];
$deliveredOrders = (int) $data['delivered_orders'];
$totalRevenue = (float) $data['total_revenue'];

$summary['total_orders'] = $totalOrders;
$summary['total_revenue'] = $totalRevenue;

$summary['average_order_value'] =
    $paidOrders > 0
    ? $totalRevenue / $paidOrders
    : 0;

$summary['delivery_rate'] =
    $totalOrders > 0
    ? ($deliveredOrders / $totalOrders) * 100
    : 0;


/* ================================================
   TOP PRODUCTS BY REVENUE
================================================ */

$topProducts = [];

$sql = "
    SELECT
        oi.product_id,
        oi.product_name AS name,

        SUM(oi.quantity) AS units,
        SUM(oi.subtotal) AS revenue,

        MAX(p.image) AS image_path

    FROM order_items oi

    INNER JOIN orders o
        ON oi.order_id = o.id

    LEFT JOIN products p
        ON oi.product_id = p.id

    WHERE o.payment_status = 'paid'
      AND o.order_status <> 'cancelled'

    GROUP BY
        oi.product_id,
        oi.product_name

    ORDER BY
        revenue DESC,
        units DESC,
        name ASC

    LIMIT 5
";

$result = $conn->query($sql);

if (!$result) {
    throw new RuntimeException(
        'Unable to load top products.'
    );
}

$rank = 1;

while ($row = $result->fetch_assoc()) {

    /*
    Only accept local product image paths.
    No invented sample image filenames.
    */

    $imageUrl = '';

    $path = ltrim(
        str_replace(
            '\\',
            '/',
            (string) ($row['image_path'] ?? '')
        ),
        '/'
    );

    if (
        preg_match(
            '~^uploads/products/[a-zA-Z0-9_-]+\.(jpg|jpeg|png|webp)$~i',
            $path
        ) &&
        is_file(dirname(__DIR__, 2) . '/' . $path)
    ) {
        $imageUrl = BASE_URL . '/' . $path;
    }

    $topProducts[] = [
        'rank' => $rank++,
        'name' => $row['name'],
        'units' => (int) $row['units'],
        'revenue' => (float) $row['revenue'],
        'image' => $imageUrl
    ];
}


/* ================================================
   REVENUE BY CATEGORY
================================================ */

$categoryRevenue = [];

/*
LEFT JOIN includes categories with no sales.

Product subtotals exclude shipping costs.
*/

$sql = "
    SELECT
        c.category_name AS category,

        COALESCE(
            SUM(s.revenue),
            0
        ) AS amount

    FROM categories c

    LEFT JOIN (
        SELECT
            p.category_id,
            oi.subtotal AS revenue

        FROM order_items oi

        INNER JOIN orders o
            ON oi.order_id = o.id

        INNER JOIN products p
            ON oi.product_id = p.id

        WHERE o.payment_status = 'paid'
          AND o.order_status <> 'cancelled'
    ) s
        ON s.category_id = c.id

    GROUP BY
        c.id,
        c.category_name

    ORDER BY
        amount DESC,
        c.category_name ASC
";

$result = $conn->query($sql);

if (!$result) {
    throw new RuntimeException(
        'Unable to load category revenue.'
    );
}

while ($row = $result->fetch_assoc()) {

    $categoryRevenue[] = [
        'category' => $row['category'],
        'amount' => (float) $row['amount']
    ];
}

$maxCategoryRevenue = 0.00;

foreach ($categoryRevenue as $category) {
    $maxCategoryRevenue = max(
        $maxCategoryRevenue,
        $category['amount']
    );
}


/* ================================================
   ORDER STATUS BREAKDOWN
================================================ */

/*
Your actual database uses:

pending
confirmed
preparing
shipping
delivered
completed
cancelled

Map these to the five UI categories.
*/

$statusCounts = [
    'Pending' => 0,
    'Processing' => 0,
    'Shipped' => 0,
    'Delivered' => 0,
    'Cancelled' => 0
];

$sql = "
    SELECT
        order_status,
        COUNT(*) AS total

    FROM orders

    GROUP BY order_status
";

$result = $conn->query($sql);

if (!$result) {
    throw new RuntimeException(
        'Unable to load order statuses.'
    );
}

while ($row = $result->fetch_assoc()) {

    $count = (int) $row['total'];

    switch ($row['order_status']) {

        case 'pending':
            $statusCounts['Pending'] += $count;
            break;

        case 'confirmed':
        case 'preparing':
            $statusCounts['Processing'] += $count;
            break;

        case 'shipping':
            $statusCounts['Shipped'] += $count;
            break;

        case 'delivered':
        case 'completed':
            $statusCounts['Delivered'] += $count;
            break;

        case 'cancelled':
            $statusCounts['Cancelled'] += $count;
            break;
    }
}

$statusBreakdown = [];

foreach ($statusCounts as $label => $count) {

    $percentage = $totalOrders > 0
        ? ($count / $totalOrders) * 100
        : 0;

    $statusBreakdown[] = [
        'label' => $label,
        'count' => $count,
        'percent' => round($percentage)
    ];
}


/* ================================================
   USER BREAKDOWN
================================================ */

/*
Role IDs:
1 - Admin
2 - Staff
3 - Customer
*/

$userBreakdown = [
    'admins' => 0,
    'staffs' => 0,
    'customers' => 0
];

$sql = "
    SELECT
        role_id,
        COUNT(*) AS total

    FROM users

    WHERE role_id IN (1, 2, 3)

    GROUP BY role_id
";

$result = $conn->query($sql);

if (!$result) {
    throw new RuntimeException(
        'Unable to load user breakdown.'
    );
}

while ($row = $result->fetch_assoc()) {

    $count = (int) $row['total'];

    switch ((int) $row['role_id']) {

        case 1:
            $userBreakdown['admins'] = $count;
            break;

        case 2:
            $userBreakdown['staffs'] = $count;
            break;

        case 3:
            $userBreakdown['customers'] = $count;
            break;
    }
}
