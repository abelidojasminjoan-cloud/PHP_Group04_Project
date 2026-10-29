<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';

$pageTitle = 'Inventory';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../includes/role-check.php';
requirePureViaAdmin($conn);
$_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
function inventoryEscape(?string $value): string
{
    return pvHtml($value ?? '', ENT_QUOTES, 'UTF-8');
}

/* LOAD INVENTORY PER PRODUCT VARIANT */

$sql = "
    SELECT
        pv.id AS variant_id,
        p.id AS product_id,
        p.product_name AS product,
        p.product_code AS sku,
        c.category_name AS category,
        pv.size_capacity,
        pv.variant_label,
        pv.price,
        pv.stock_quantity AS stock,
        pv.low_stock_threshold AS threshold,
        pv.updated_at AS last_updated
    FROM product_variants AS pv
    INNER JOIN products AS p
        ON pv.product_id = p.id
    INNER JOIN categories AS c
        ON p.category_id = c.id
    ORDER BY
        p.product_name ASC,
        pv.id ASC
";


$result = $conn->query($sql);
$inventory = $result->fetch_all(MYSQLI_ASSOC);
$result->free();

$totalInventory = count($inventory);


/* CATEGORY FILTER OPTIONS */

$categories = [];

foreach ($inventory as $item) {
    if (!in_array($item['category'], $categories, true)) {
        $categories[] = $item['category'];
    }
}

sort($categories);

/*
 * Default low-stock threshold.
 * Change to 15 or another number as needed.
 */
$defaultThreshold = 15;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Inventory | PureVia</title>

    <!-- Bootstrap 5 CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/assets/css/sidemenu.css"
    >

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/assets/css/inventory.css"
    >
    <!-- Responsive adjustments -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/responsive.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/form2.css?v=<?= filemtime(__DIR__ . '/../../assets/css/form2.css') ?>">
</head>

<body>

<?php require __DIR__ . '/../../includes/sidemenu.php'; ?>

<div class="admin-layout">

    <header class="admin-topbar">
        <p class="admin-topbar-title">Inventory</p>
    </header>

    <main class="inventory-main">

        <section class="inventory-page-header">

            <div class="inventory-heading">
                <h1>Inventory</h1>

                <p>
                    <?= number_format($totalInventory) ?>
                    records
                </p>
            </div>

        </section>

        <section class="inventory-card">

            <!-- SEARCH AND FILTERS -->

            <div class="inventory-toolbar">

                <div class="inventory-search">
                    <i class="fa-solid fa-magnifying-glass"></i>

                    <input
                        type="search"
                        id="inventorySearch"
                        placeholder="Search inventory..."
                        autocomplete="off"
                    >
                </div>

                <div class="inventory-filter-dropdown">

                    <i class="fa-solid fa-filter inventory-filter-icon"></i>

                    <select
                        id="inventoryCategoryFilter"
                        class="inventory-filter-select"
                        aria-label="Filter by category"
                    >
                        <option value="all">All Categories</option>

                        <?php foreach ($categories as $category): ?>
                            <option
                                value="<?= inventoryEscape(strtolower($category)) ?>"
                            >
                                <?= inventoryEscape($category) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <i class="fa-solid fa-chevron-down inventory-filter-arrow"></i>
                </div>

                <div class="inventory-filter-dropdown">

                    <select
                        id="inventoryStockFilter"
                        class="inventory-filter-select inventory-filter-no-icon"
                        aria-label="Filter by stock"
                    >
                        <option value="all">All Stock</option>
                        <option value="in-stock">In Stock</option>
                        <option value="low-stock">Low Stock</option>
                        <option value="out-of-stock">Out of Stock</option>
                    </select>

                    <i class="fa-solid fa-chevron-down inventory-filter-arrow"></i>
                </div>

            </div>

            <!-- INVENTORY TABLE -->

            <div class="inventory-table-wrapper">

                <table class="inventory-table">

                    <thead>
                        <tr>
                            <th>PRODUCT / SIZE</th>
                            <th>SKU</th>
                            <th>CATEGORY</th>
                            <th>STOCK</th>
                            <th>THRESHOLD</th>
                            <th>STATUS</th>
                            <th>LAST UPDATED</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($inventory as $item): ?>

                            <?php
                            $stock = (int) $item['stock'];
                            $threshold = (int) $item['threshold'];

                            if ($stock === 0) {
                                $stockLevel = 'out-of-stock';
                                $stockStatusText = 'OUT OF STOCK';
                            } elseif ($stock <= $threshold) {
                                $stockLevel = 'low-stock';
                                $stockStatusText = 'LOW STOCK';
                            } else {
                                $stockLevel = 'in-stock';
                                $stockStatusText = 'IN STOCK';
                            }

                            $searchData = strtolower(
                                $item['product'] . ' ' .
                                $item['sku'] . ' ' .
                                $item['category'] . ' ' .
                                $item['size_capacity'] . ' ' .
                                ($item['variant_label'] ?? '')
                            );

                            $lastUpdated = date(
                                'Y-m-d',
                                strtotime($item['last_updated'])
                            );
                            ?>

                            <tr
                                class="inventory-row"
                                data-search="<?= inventoryEscape($searchData) ?>"
                                data-category="<?= inventoryEscape(strtolower($item['category'])) ?>"
                                data-stock-level="<?= inventoryEscape($stockLevel) ?>"
                            >

                                <!-- PRODUCT / SIZE -->
                                <td>
                                    <div class="inventory-product-info">

                                        <span class="inventory-product-name">
                                            <?= inventoryEscape($item['product']) ?>
                                        </span>

                                        <div class="inventory-variant-details">

                                            <span class="inventory-size-badge">
                                                <?= inventoryEscape($item['size_capacity']) ?>
                                            </span>

                                            <?php if (!empty($item['variant_label'])): ?>
                                                <span class="inventory-variant-label">
                                                    <?= inventoryEscape($item['variant_label']) ?>
                                                </span>
                                            <?php endif; ?>

                                        </div>

                                    </div>
                                </td>

                                <!-- SKU -->
                                <td>
                                    <span class="inventory-sku">
                                        <?= inventoryEscape($item['sku']) ?>
                                    </span>
                                </td>

                                <!-- CATEGORY -->
                                <td>
                                    <span class="inventory-category">
                                        <?= inventoryEscape($item['category']) ?>
                                    </span>
                                </td>

                                <!-- STOCK -->
                                <td>
                                    <span class="inventory-stock <?= $stockLevel ?>">
                                        <?= number_format($stock) ?>
                                    </span>
                                </td>

                                <!-- THRESHOLD -->
                                <td>
                                    <span class="inventory-threshold">
                                        <?= number_format($threshold) ?>
                                    </span>
                                </td>

                                <!-- STATUS -->
                                <td>
                                    <span class="inventory-stock-status <?= $stockLevel ?>">
                                        <?= inventoryEscape($stockStatusText) ?>
                                    </span>
                                </td>

                                <!-- LAST UPDATED -->
                                <td>
                                    <span class="inventory-date">
                                        <?= inventoryEscape($lastUpdated) ?>
                                    </span>
                                </td>

                                <!-- ACTIONS -->
                                <td>
                                    <div class="inventory-actions">

                                        <button type="button" class="inventory-edit-button" data-manage-inventory="<?= (int)$item['variant_id'] ?>" title="Manage inventory" aria-label="Manage inventory for <?= inventoryEscape($item['product'] . ' ' . $item['size_capacity']) ?>"><i class="fa-regular fa-pen-to-square" aria-hidden="true"></i></button>

                                    </div>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                        <!-- NO RESULTS -->
                        <tr
                            id="noInventoryFound"
                            class="no-inventory-row"
                            style="display:none;"
                        >
                            <td colspan="8">
                                <div class="no-inventory-message">
                                    <i class="fa-solid fa-box-open"></i>
                                    <p>No inventory records found.</p>
                                </div>
                            </td>
                        </tr>

                    </tbody>

                </table>

            </div>

        </section>

    </main>

</div>

<script nonce="<?= pvScriptNonce() ?>" src="<?= BASE_URL ?>/assets/js/sidemenu.js"></script>
<script nonce="<?= pvScriptNonce() ?>" src="<?= BASE_URL ?>/assets/js/inventory.js"></script>

<script nonce="<?= pvScriptNonce() ?>" src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php require __DIR__ . '/../../includes/inventory-dialog.php'; ?>
<script nonce="<?= pvScriptNonce() ?>" src="<?= BASE_URL ?>/assets/js/inventory-manage.js?v=<?= filemtime(__DIR__ . '/../../assets/js/inventory-manage.js') ?>"></script>
</body>
</html>
