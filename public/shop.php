
<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';

$pageTitle = 'Shop All Products | PureVia';


/* =========================================
   HELPERS - FIXED
========================================= */

/**
 * Escape values safely for HTML output.
 */
function shopEscape(mixed $value): string
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

/**
 * Resolve product image paths safely.
 */
function shopImage(?string $path): string
{
    $fallback = BASE_URL .
        '/assets/images/products/default-product.jpg';

    $path = trim($path ?? '');

    if ($path === '') {
        return $fallback;
    }

    // Allow only valid HTTP or HTTPS URLs.
    if (filter_var($path, FILTER_VALIDATE_URL)) {

        $scheme = strtolower(
            (string) parse_url($path, PHP_URL_SCHEME)
        );

        return in_array(
            $scheme,
            ['http', 'https'],
            true
        ) ? $path : $fallback;
    }

    // Extract filename from stored path.
    $filename = basename(
        str_replace('\\', '/', $path)
    );

    if (
        $filename === '.' ||
        $filename === '..' ||
        $filename === ''
    ) {
        return $fallback;
    }

    // Verify local product image exists.
    $fullPath = __DIR__ .
        '/../uploads/products/' .
        $filename;

    if (is_file($fullPath)) {
        return BASE_URL .
            '/uploads/products/' .
            rawurlencode($filename);
    }

    return $fallback;
}

/**
 * Fetch database query results.
 */
function shopFetchAll(
    mysqli $conn,
    string $sql
): array {
    $result = $conn->query($sql);

    if (!$result) {
        throw new RuntimeException(
            'Unable to load shop data.'
        );
    }

    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Normalize comma-separated numeric IDs.
 */
function shopIds(?string $value): string
{
    if (empty($value)) {
        return '';
    }

    $ids = array_map(
        'trim',
        explode(',', $value)
    );

    $ids = array_filter(
        $ids,
        static fn(string $id): bool =>
            ctype_digit($id)
    );

    return implode(',', $ids);
}


/* =========================================
   CSRF TOKEN
========================================= */

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];

/* =========================================
   CURRENT CUSTOMER SKIN PROFILE
========================================= */

$userId = (int) ($_SESSION['user_id'] ?? 0);

$customerSkinTypeId = 0;
$customerConcernIds = [];

if ($userId > 0) {

    $stmt = $conn->prepare("
        SELECT skin_type_id
        FROM skin_profiles
        WHERE user_id = ?
        LIMIT 1
    ");

    $stmt->bind_param('i', $userId);
    $stmt->execute();

    $profile = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($profile) {
        $customerSkinTypeId = (int) ($profile['skin_type_id'] ?? 0);
    }

    $stmt = $conn->prepare("
        SELECT spc.concern_id
        FROM skin_profile_concerns spc
        INNER JOIN skin_profiles sp
            ON sp.id = spc.skin_profile_id
        WHERE sp.user_id = ?
    ");

    $stmt->bind_param('i', $userId);
    $stmt->execute();

    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
        $customerConcernIds[] = (int) $row['concern_id'];
    }

    $stmt->close();
}

/* =========================================
   CATEGORIES
========================================= */

$categories = shopFetchAll($conn, "
    SELECT id, category_name
    FROM categories
    WHERE status = 'active'
    ORDER BY category_name ASC
");

/* =========================================
   SKIN TYPES
========================================= */

$skinTypes = shopFetchAll($conn, "
    SELECT id, skin_type_name
    FROM skin_types
    ORDER BY skin_type_name ASC
");

/* =========================================
   SKIN CONCERNS
========================================= */

$skinConcerns = shopFetchAll($conn, "
    SELECT id, concern_name
    FROM skin_concerns
    ORDER BY concern_name ASC
");

/* =========================================
   PRODUCTS
========================================= */

$products = shopFetchAll($conn, "
    SELECT
        p.id,
        p.category_id,
        p.product_name,
        p.product_code,
        p.description,
        p.price,
        p.image,
        p.stock_quantity,
        p.created_at,

        c.category_name,

        COALESCE((
            SELECT SUM(pv.stock_quantity)
            FROM product_variants pv
            WHERE pv.product_id = p.id
        ), p.stock_quantity) AS display_stock,

        (
            SELECT GROUP_CONCAT(DISTINCT pst.skin_type_id)
            FROM product_skin_types pst
            WHERE pst.product_id = p.id
        ) AS skin_type_ids,

        (
            SELECT GROUP_CONCAT(DISTINCT pc.concern_id)
            FROM product_concerns pc
            WHERE pc.product_id = p.id
        ) AS concern_ids,

        (
            SELECT COUNT(*)
            FROM product_reviews pr
            WHERE pr.product_id = p.id
              AND pr.status = 'approved'
        ) AS review_count,

        (
            SELECT COALESCE(AVG(pr.rating), 0)
            FROM product_reviews pr
            WHERE pr.product_id = p.id
              AND pr.status = 'approved'
        ) AS average_rating,

        (
            SELECT COUNT(*)
            FROM order_items oi
            INNER JOIN orders o
                ON o.id = oi.order_id
            WHERE oi.product_id = p.id
              AND o.order_status IN ('delivered', 'completed')
        ) AS sales_count

    FROM products p
    INNER JOIN categories c
        ON c.id = p.category_id

    WHERE p.status = 'active'
      AND c.status = 'active'

    ORDER BY p.created_at DESC, p.id DESC
");


/* =========================================
   SEARCH PRODUCTS BY NAME / CATEGORY
========================================= */

$searchQuery = trim((string) ($_GET['q'] ?? ''));

if ($searchQuery !== '') {

    $products = array_values(
        array_filter(
            $products,
            static function (array $product) use ($searchQuery): bool {

                $searchableText = implode(' ', [
                    (string) ($product['product_name'] ?? ''),
                    (string) ($product['category_name'] ?? ''),
                    (string) ($product['description'] ?? '')
                ]);

                return mb_stripos(
                    $searchableText,
                    $searchQuery
                ) !== false;
            }
        )
    );
}




/* =========================================
   PRODUCT VARIANTS
========================================= */

$productVariants = [];

$variantRows = shopFetchAll($conn, "
    SELECT
        pv.id,
        pv.product_id,
        pv.size_capacity,
        pv.variant_label,
        pv.price,
        pv.stock_quantity
    FROM product_variants pv
    INNER JOIN products p
        ON p.id = pv.product_id
    WHERE p.status = 'active'
    ORDER BY pv.product_id ASC, pv.id ASC
");

foreach ($variantRows as $variant) {
    $productVariants[(int) $variant['product_id']][] = $variant;
}

/* =========================================
   PRICE LIMIT
========================================= */

$maxPrice = 0;

foreach ($products as $product) {

    $productId = (int) $product['id'];
    $variants = $productVariants[$productId] ?? [];

    if ($variants) {
        foreach ($variants as $variant) {
            $maxPrice = max(
                $maxPrice,
                (float) $variant['price']
            );
        }
    } else {
        $maxPrice = max(
            $maxPrice,
            (float) $product['price']
        );
    }
}

$maxPrice = max(1, (int) ceil($maxPrice));

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title><?= shopEscape($pageTitle) ?></title>

    <!-- Bootstrap 5 CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet"
          href="<?= BASE_URL ?>/assets/css/header.css">

    <link rel="stylesheet"
          href="<?= BASE_URL ?>/assets/css/footer.css">

    <link rel="stylesheet"
          href="<?= BASE_URL ?>/assets/css/shop.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/search.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/cart_add.css">
    

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <!-- Responsive adjustments -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/responsive.css">
</head>

<body>

<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="shop-page">

<div class="shop-container">

    <!-- PAGE HEADING -->

    <div class="shop-heading">
        <h1>Shop All Products</h1>

        <p>
            Discover skincare made for your unique skin.
        </p>
    </div>

    <!-- MAIN LAYOUT -->

    <div class="shop-layout">

        <!-- =====================================
             FILTER SIDEBAR
        ====================================== -->

        <aside class="shop-sidebar" id="shopSidebar">

            <div class="shop-sidebar-heading">

                <h2>Filters</h2>

                <button type="button" id="clearFilters">
                    Clear all
                </button>

            </div>

            <!-- SHOW MY MATCHES -->

            <label class="shop-match-filter">

                <input
                    type="checkbox"
                    id="shopMyMatches"
                    <?= $customerSkinTypeId === 0 ? 'disabled' : '' ?>
                >

                <span>
                    <strong>Show my matches</strong>

                    <small>
                        <?= $customerSkinTypeId > 0
                            ? 'Products suited to your skin'
                            : 'Set up your skin profile first' ?>
                    </small>
                </span>

            </label>

            <!-- STOCK STATUS -->

            <div class="shop-filter-group shop-accordion is-open">

                <button
                    type="button"
                    class="shop-filter-toggle"
                    aria-expanded="true"
                    aria-controls="shopStockPanel"
                >
                    <span>Stock Status</span>
                    <span class="shop-filter-symbol">−</span>
                </button>

                <div class="shop-filter-content" id="shopStockPanel">

                    <label class="shop-checkbox">
                        <input
                            type="checkbox"
                            name="stock_status"
                            value="in"
                        >
                        In stock
                    </label>

                    <label class="shop-checkbox">
                        <input
                            type="checkbox"
                            name="stock_status"
                            value="out"
                        >
                        Out of stock
                    </label>

                </div>

            </div>

            <!-- CATEGORY -->

            <div class="shop-filter-group shop-accordion is-open">

                <button
                    type="button"
                    class="shop-filter-toggle"
                    aria-expanded="true"
                    aria-controls="shopCategoryPanel"
                >
                    <span>Category</span>
                    <span class="shop-filter-symbol">−</span>
                </button>

                <div class="shop-filter-content" id="shopCategoryPanel">

                    <label class="shop-checkbox">
                        <input
                            type="checkbox"
                            name="category"
                            value="all"
                            checked
                        >
                        All
                    </label>

                    <?php foreach ($categories as $category): ?>

                        <label class="shop-checkbox">

                            <input
                                type="checkbox"
                                name="category"
                                value="<?= (int) $category['id'] ?>"
                            >

                            <?= shopEscape($category['category_name']) ?>

                        </label>

                    <?php endforeach; ?>

                </div>

            </div>

            <!-- SKIN TYPE -->

            <div class="shop-filter-group shop-accordion">

                <button
                    type="button"
                    class="shop-filter-toggle"
                    aria-expanded="false"
                    aria-controls="shopSkinPanel"
                >
                    <span>Skin Type</span>
                    <span class="shop-filter-symbol">+</span>
                </button>

                <div
                    class="shop-filter-content"
                    id="shopSkinPanel"
                    hidden
                >

                    <?php foreach ($skinTypes as $type): ?>

                        <label class="shop-checkbox">

                            <input
                                type="checkbox"
                                name="skin_type"
                                value="<?= (int) $type['id'] ?>"
                            >

                            <?= shopEscape($type['skin_type_name']) ?>

                        </label>

                    <?php endforeach; ?>

                </div>

            </div>

            <!-- SKIN CONCERN -->

            <div class="shop-filter-group shop-accordion">

                <button
                    type="button"
                    class="shop-filter-toggle"
                    aria-expanded="false"
                    aria-controls="shopConcernPanel"
                >
                    <span>Skin Concern</span>
                    <span class="shop-filter-symbol">+</span>
                </button>

                <div
                    class="shop-filter-content"
                    id="shopConcernPanel"
                    hidden
                >

                    <?php foreach ($skinConcerns as $concern): ?>

                        <label class="shop-checkbox">

                            <input
                                type="checkbox"
                                name="concern"
                                value="<?= (int) $concern['id'] ?>"
                            >

                            <?= shopEscape($concern['concern_name']) ?>

                        </label>

                    <?php endforeach; ?>

                </div>

            </div>

            <!-- PRICE RANGE -->

            <div class="shop-filter-group shop-accordion is-open">

                <button
                    type="button"
                    class="shop-filter-toggle"
                    aria-expanded="true"
                    aria-controls="shopPricePanel"
                >
                    <span>Price</span>
                    <span class="shop-filter-symbol">−</span>
                </button>

                <div class="shop-filter-content" id="shopPricePanel">

                    <div
                        class="shop-price-range"
                        id="shopPriceRange"
                        style="--range-min:0%; --range-max:100%;"
                    >

                        <div class="shop-price-track"></div>
                        <div class="shop-price-progress"></div>

                        <input
                            type="range"
                            class="shop-range-input"
                            id="shopMinRange"
                            min="0"
                            max="<?= $maxPrice ?>"
                            value="0"
                            step="1"
                            aria-label="Minimum price"
                        >

                        <input
                            type="range"
                            class="shop-range-input"
                            id="shopMaxRange"
                            min="0"
                            max="<?= $maxPrice ?>"
                            value="<?= $maxPrice ?>"
                            step="1"
                            aria-label="Maximum price"
                        >

                    </div>

                    <div class="shop-price-inputs">

                        <label class="shop-price-box">
                            <span>₱</span>

                            <input
                                type="number"
                                id="shopMinPrice"
                                min="0"
                                max="<?= $maxPrice ?>"
                                value="0"
                                step="1"
                                aria-label="Minimum price in pesos"
                            >
                        </label>

                        <label class="shop-price-box">
                            <span>₱</span>

                            <input
                                type="number"
                                id="shopMaxPrice"
                                min="0"
                                max="<?= $maxPrice ?>"
                                value="<?= $maxPrice ?>"
                                step="1"
                                aria-label="Maximum price in pesos"
                            >
                        </label>

                    </div>

                </div>

            </div>

        </aside>

        <!-- =====================================
             PRODUCTS AREA
        ====================================== -->

        <section class="shop-products">

            <!-- SORT TOOLBAR -->

            <div class="shop-toolbar">

                <div class="shop-sort" id="shopSortContainer">

                    <button
                        type="button"
                        class="shop-sort-trigger"
                        id="shopSortTrigger"
                        aria-expanded="false"
                        aria-controls="shopSortMenu"
                    >
                        Sort by

                        <i class="fa-solid fa-chevron-down"></i>
                    </button>

                    <div
                        class="shop-sort-menu"
                        id="shopSortMenu"
                        hidden
                    >

                        <?php
                        $sortOptions = [
                            'featured' => 'Featured',
                            'relevant' => 'Most relevant',
                            'best-selling' => 'Best selling',
                            'name-asc' => 'Alphabetically, A-Z',
                            'name-desc' => 'Alphabetically, Z-A',
                            'price-low' => 'Price, low to high',
                            'price-high' => 'Price, high to low',
                            'date-old' => 'Date, old to new',
                            'date-new' => 'Date, new to old'
                        ];
                        ?>

                        <?php foreach ($sortOptions as $value => $label): ?>

                            <label class="shop-sort-option">

                                <input
                                    type="radio"
                                    name="shop_sort"
                                    value="<?= shopEscape($value) ?>"
                                    <?= $value === 'relevant' ? 'checked' : '' ?>
                                >

                                <span><?= shopEscape($label) ?></span>

                            </label>

                        <?php endforeach; ?>

                    </div>

                </div>

                <span
                    class="shop-toolbar-count"
                    id="shopResultCount"
                >
                    <?= count($products) ?>
                    <?= count($products) === 1 ? 'product' : 'products' ?>
                </span>

            </div>

            <!-- PRODUCT GRID -->

            <div
                class="shop-product-grid"
                id="shopProductGrid"
            >

                <?php foreach ($products as $index => $product): ?>

                    <?php

                    $productId = (int) $product['id'];

                    $variants = $productVariants[$productId] ?? [];

                    if (!$variants) {
                        $variants = [[
                            'id' => 0,
                            'product_id' => $productId,
                            'size_capacity' => 'Standard',
                            'variant_label' => '',
                            'price' => $product['price'],
                            'stock_quantity' => $product['stock_quantity']
                        ]];
                    }

                    $selectedVariant = $variants[0];

                    foreach ($variants as $variant) {
                        if ((int) $variant['stock_quantity'] > 0) {
                            $selectedVariant = $variant;
                            break;
                        }
                    }

                    $selectedPrice = (float) $selectedVariant['price'];

                    $totalStock = (int) $product['display_stock'];

                    $skinIds = shopIds($product['skin_type_ids']);
                    $concernIds = shopIds($product['concern_ids']);

                    $productSkinIds = $skinIds !== ''
                        ? array_map('intval', explode(',', $skinIds))
                        : [];

                    $productConcernIds = $concernIds !== ''
                        ? array_map('intval', explode(',', $concernIds))
                        : [];

                    $skinMatch = $customerSkinTypeId > 0 &&
                        in_array($customerSkinTypeId, $productSkinIds, true);

                    $concernMatches = count(
                        array_intersect(
                            $customerConcernIds,
                            $productConcernIds
                        )
                    );

                    $isMatch = $skinMatch;

                    $rating = (float) $product['average_rating'];
                    $reviewCount = (int) $product['review_count'];

                    $fullStars = min(5, max(0, (int) round($rating)));

                    $productUrl = BASE_URL . '/public/product.php?id=' . $productId;

                    ?>

                    <article
                        class="shop-product-card"
                        data-index="<?= $index ?>"
                        data-id="<?= $productId ?>"
                        data-name="<?= shopEscape(strtolower($product['product_name'])) ?>"
                        data-category="<?= (int) $product['category_id'] ?>"
                        data-price="<?= $selectedPrice ?>"
                        data-stock="<?= $totalStock ?>"
                        data-types="<?= shopEscape($skinIds) ?>"
                        data-concerns="<?= shopEscape($concernIds) ?>"
                        data-match="<?= $isMatch ? 1 : 0 ?>"
                        data-concern-match="<?= $concernMatches ?>"
                        data-sales="<?= (int) $product['sales_count'] ?>"
                        data-date="<?= shopEscape($product['created_at']) ?>"
                    >

                        <!-- PRODUCT IMAGE -->

                        <a
                            href="<?= shopEscape($productUrl) ?>"
                            class="shop-product-image"
                        >

                            <img
                                src="<?= shopEscape(shopImage($product['image'])) ?>"
                                alt="<?= shopEscape($product['product_name']) ?>"
                                loading="lazy"
                            >

                            <?php if ($isMatch): ?>
                                <span class="shop-match-badge">
                                    SKIN MATCH
                                </span>
                            <?php endif; ?>

                            <?php if ($totalStock <= 0): ?>
                                <span class="shop-stock-badge">
                                    OUT OF STOCK
                                </span>
                            <?php endif; ?>

                        </a>

                        <!-- PRODUCT INFO -->

                        <div class="shop-product-info">

                            <h3 class="shop-product-name">

                                <a href="<?= shopEscape($productUrl) ?>">
                                    <?= shopEscape($product['product_name']) ?>
                                </a>

                            </h3>

                            <p class="shop-card-description">
                                <?= shopEscape($product['description']) ?>
                            </p>
 
                            <!-- SIZE SELECTION AND ADD TO BAG -->

                            <form
                                class="shop-card-form pv-add-cart-form"
                                action="<?= BASE_URL ?>/actions/cart/add.php"
                                method="POST"
                            >
                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= shopEscape($csrfToken) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="product_id"
                                    value="<?= $productId ?>"
                                >

                                <input
                                    type="hidden"
                                    name="product_variant_id"
                                    class="shop-card-variant-input"
                                    value="<?= (int) $selectedVariant['id'] ?>"
                                >

                                <input
                                    type="hidden"
                                    name="quantity"
                                    value="1"
                                >

                                <div class="shop-card-sizes">

                                    <?php foreach ($variants as $variant): ?>

                                        <?php
                                            $isSelected =
                                                (int) $selectedVariant['id'] ===
                                                (int) $variant['id'];

                                            $variantStock =
                                                (int) $variant['stock_quantity'];
                                        ?>

                                        <button
                                            type="button"
                                            class="shop-card-size <?= $isSelected ? 'active' : '' ?>"
                                            data-variant-id="<?= (int) $variant['id'] ?>"
                                            data-price="<?= (float) $variant['price'] ?>"
                                            data-stock="<?= $variantStock ?>"
                                            aria-pressed="<?= $isSelected ? 'true' : 'false' ?>"
                                            <?= $variantStock <= 0 ? 'disabled' : '' ?>
                                        >
                                            <?= shopEscape($variant['size_capacity']) ?>
                                        </button>

                                    <?php endforeach; ?>

                                </div>

                                <!-- RATINGS -->

                                <div class="shop-card-rating">

                                    <span class="shop-card-stars">
                                        <?= str_repeat('★', $fullStars) ?>

                                        <span class="unfilled">
                                            <?= str_repeat('☆', 5 - $fullStars) ?>
                                        </span>
                                    </span>

                                    <a
                                        class="shop-card-review-count"
                                        href="<?= shopEscape($productUrl) ?>#customerReviews"
                                    >
                                        <?= $reviewCount ?>
                                        <?= $reviewCount === 1 ? 'Review' : 'Reviews' ?>
                                    </a>

                                </div>

                                <!-- ADD TO BAG BUTTON -->

                                <button
                                    type="submit"
                                    class="shop-card-add"
                                    <?= (
                                        (int) $selectedVariant['id'] <= 0 ||
                                        (int) $selectedVariant['stock_quantity'] <= 0
                                    ) ? 'disabled' : '' ?>
                                >
                                    <span class="shop-card-add-label">
                                        <?= (
                                            (int) $selectedVariant['id'] > 0 &&
                                            (int) $selectedVariant['stock_quantity'] > 0
                                        ) ? 'Add to Bag' : 'Out of Stock' ?>
                                    </span>

                                    <span class="shop-card-price">
                                        ₱<?= number_format($selectedPrice, 2) ?>
                                    </span>
                                </button>

                            </form>


                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

            <!-- EMPTY RESULTS -->

            <div
                class="shop-empty"
                id="shopEmpty"
                <?= $products ? 'hidden' : '' ?>
            >

                <i class="fa-solid fa-box-open"></i>

                <h3>No products found</h3>

                <p>
                    Try adjusting your filters to see more products.
                </p>

                <button type="button" id="shopResetEmpty">
                    Clear Filters
                </button>

            </div>

        </section>

    </div>

</div>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script src="<?= BASE_URL ?>/assets/js/shop.js"></script>
<script src="<?= BASE_URL ?>/assets/js/search.js"></script>
<script>
    window.PUREVIA_BASE_URL = <?= json_encode(BASE_URL) ?>;
</script>

<script src="<?= BASE_URL ?>/assets/js/cart_add.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
