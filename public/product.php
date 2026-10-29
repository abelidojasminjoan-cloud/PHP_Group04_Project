<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';

function pdEsc(?string $value): string
{
    return pvHtml($value ?? '', ENT_QUOTES, 'UTF-8');
}

function pdImage(?string $path): string
{
    $fallback = BASE_URL . '/assets/images/products/default-product.jpg';
    $path = trim((string) $path);

    if ($path === '') {
        return $fallback;
    }

    if (filter_var($path, FILTER_VALIDATE_URL)) {
        $scheme = strtolower((string) parse_url($path, PHP_URL_SCHEME));
        return in_array($scheme, ['http', 'https'], true) ? $path : $fallback;
    }

    $filename = basename(str_replace('\\', '/', $path));

    if ($filename === '.' || $filename === '..') {
        return $fallback;
    }

    return is_file(__DIR__ . '/../uploads/products/' . $filename)
        ? BASE_URL . '/uploads/products/' . rawurlencode($filename)
        : $fallback;
}

function pdRows(mysqli $conn, string $sql, int $productId): array
{
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $productId);
    $stmt->execute();

    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows;
}

$productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$productId || $productId < 1) {
    http_response_code(404);
    exit('Product not found.');
}

/* PRODUCT */

$rows = pdRows($conn, "
    SELECT
        p.id,
        p.product_name,
        p.product_code,
        p.description,
        p.usage_instructions,
        p.price,
        p.image,
        p.stock_quantity,
        c.category_name
    FROM products p
    INNER JOIN categories c ON c.id = p.category_id
    WHERE p.id = ?
      AND p.status = 'active'
      AND c.status = 'active'
    LIMIT 1
", $productId);

$product = $rows[0] ?? null;

if (!$product) {
    http_response_code(404);
    exit('This product is unavailable.');
}

$pageTitle = $product['product_name'] . ' | PureVia';

/* PRODUCT IMAGES */

$imageRows = pdRows($conn, "
    SELECT image_path
    FROM product_images
    WHERE product_id = ?
    ORDER BY sort_order ASC, id ASC
", $productId);

$images = [];

foreach ($imageRows as $row) {
    if (trim((string) $row['image_path']) !== '') {
        $images[] = pdImage($row['image_path']);
    }
}

if (!$images) {
    $images[] = pdImage($product['image']);
}

$images = array_values(array_unique($images));

/* PRODUCT VARIANTS */

$variants = pdRows($conn, "
    SELECT
        id,
        size_capacity,
        variant_label,
        price,
        stock_quantity
    FROM product_variants
    WHERE product_id = ?
    ORDER BY id ASC
", $productId);

if (!$variants) {
    $variants[] = [
        'id' => 0,
        'size_capacity' => 'Standard',
        'variant_label' => '',
        'price' => $product['price'],
        'stock_quantity' => $product['stock_quantity']
    ];
}

$selectedVariant = $variants[0];

foreach ($variants as $variant) {
    if ((int) $variant['stock_quantity'] > 0) {
        $selectedVariant = $variant;
        break;
    }
}

$totalStock = array_sum(array_map(
    static fn(array $variant): int => (int) $variant['stock_quantity'],
    $variants
));

/* SKIN TYPES */

$skinTypes = pdRows($conn, "
    SELECT st.skin_type_name
    FROM product_skin_types pst
    INNER JOIN skin_types st ON st.id = pst.skin_type_id
    WHERE pst.product_id = ?
    ORDER BY st.skin_type_name
", $productId);

/* SKIN CONCERNS */

$skinConcerns = pdRows($conn, "
    SELECT sc.concern_name
    FROM product_concerns pc
    INNER JOIN skin_concerns sc ON sc.id = pc.concern_id
    WHERE pc.product_id = ?
    ORDER BY sc.concern_name
", $productId);

/* INGREDIENTS */

$ingredients = pdRows($conn, "
    SELECT i.ingredient_name
    FROM product_ingredients pi
    INNER JOIN ingredients i ON i.id = pi.ingredient_id
    WHERE pi.product_id = ?
    ORDER BY i.ingredient_name
", $productId);

/* KEY BENEFITS */

$benefits = pdRows($conn, "
    SELECT benefit_text
    FROM product_benefits
    WHERE product_id = ?
    ORDER BY sort_order ASC, id ASC
", $productId);

/* USAGE INSTRUCTIONS */

$usageSteps = preg_split(
    '/\r\n|\r|\n/',
    trim((string) ($product['usage_instructions'] ?? '')),
    -1,
    PREG_SPLIT_NO_EMPTY
) ?: [];

$usageSteps = array_values(array_filter(array_map(
    'trim',
    $usageSteps
)));

/* REVIEW SUMMARY */

$summaryRows = pdRows($conn, "
    SELECT
        COUNT(*) AS review_count,
        COALESCE(AVG(rating), 0) AS average_rating
    FROM product_reviews
    WHERE product_id = ?
      AND status = 'approved'
", $productId);

$reviewCount = (int) ($summaryRows[0]['review_count'] ?? 0);
$averageRating = (float) ($summaryRows[0]['average_rating'] ?? 0);

/* CUSTOMER REVIEWS */

$reviews = pdRows($conn, "
    SELECT
        r.id,
        r.rating,
        r.title,
        r.review_text,
        r.skin_type,
        r.verified_purchase,
        r.helpful_count,
        r.created_at,
        u.first_name,
        u.last_name
    FROM product_reviews r
    INNER JOIN users u ON u.id = r.user_id
    WHERE r.product_id = ?
      AND r.status = 'approved'
    ORDER BY r.created_at DESC, r.id DESC
", $productId);

/* REVIEW IMAGES */

$reviewImages = [];

if ($reviews) {
    $reviewIds = array_map(
        static fn(array $review): int => (int) $review['id'],
        $reviews
    );

    $placeholders = implode(',', array_fill(0, count($reviewIds), '?'));

    $stmt = $conn->prepare("
        SELECT review_id, image_path
        FROM review_images
        WHERE review_id IN ($placeholders)
        ORDER BY id ASC
    ");

    $types = str_repeat('i', count($reviewIds));
    $stmt->bind_param($types, ...$reviewIds);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $reviewImages[(int) $row['review_id']][] =
            str_starts_with(str_replace('\\', '/', (string) $row['image_path']), 'uploads/reviews/')
                ? BASE_URL . '/uploads/reviews/' . rawurlencode(basename(str_replace('\\', '/', (string) $row['image_path'])))
                : pdImage($row['image_path']);
    }

    $stmt->close();
}

/* REVIEW VOTES */

$userId = (int) ($_SESSION['user_id'] ?? 0);
$userVotes = [];

if ($userId > 0) {
    $stmt = $conn->prepare("
        SELECT rv.review_id, rv.vote
        FROM review_votes rv
        INNER JOIN product_reviews r ON r.id = rv.review_id
        WHERE rv.user_id = ?
          AND r.product_id = ?
    ");

    $stmt->bind_param('ii', $userId, $productId);
    $stmt->execute();

    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
        $userVotes[(int) $row['review_id']] = $row['vote'];
    }

    $stmt->close();
}

/* CSRF TOKEN */

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= pdEsc($pageTitle) ?></title>

    <!-- Bootstrap 5 CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet"
          href="<?= BASE_URL ?>/assets/css/header.css">

    <link rel="stylesheet"
          href="<?= BASE_URL ?>/assets/css/footer.css">

    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/product_details.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/product-favorites.css?v=<?= filemtime(__DIR__ . '/../assets/css/product-favorites.css') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/search.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <!-- Responsive adjustments -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/responsive.css">
</head>

<body>

<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="pd-page">

<div class="pd-container">

    <!-- BREADCRUMB -->

    <nav class="pd-breadcrumb">
        <a href="<?= BASE_URL ?>/public/index.php">Home</a>
        <span>/</span>

        <a href="<?= BASE_URL ?>/public/shop.php">Products</a>
        <span>/</span>

        <strong><?= pdEsc($product['product_name']) ?></strong>
    </nav>

    <!-- PRODUCT OVERVIEW -->

    <section class="pd-overview">

        <!-- PRODUCT GALLERY -->

        <div class="pd-gallery">

            <div class="pd-main-image">

                <img
                    id="pdMainImage"
                    src="<?= pdEsc($images[0]) ?>"
                    alt="<?= pdEsc($product['product_name']) ?>"
                >

                <?php if (count($images) > 1): ?>

                    <button
                        type="button"
                        class="pd-gallery-arrow pd-prev"
                        id="pdPreviousImage"
                        aria-label="Previous image"
                    >
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>

                    <button
                        type="button"
                        class="pd-gallery-arrow pd-next"
                        id="pdNextImage"
                        aria-label="Next image"
                    >
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>

                <?php endif; ?>

                <span class="pd-image-counter" id="pdImageCounter">
                    1 / <?= count($images) ?>
                </span>

            </div>

            <?php if (count($images) > 1): ?>

                <div class="pd-thumbnails" id="pdThumbnails">

                    <?php foreach ($images as $index => $image): ?>

                        <button
                            type="button"
                            class="pd-thumbnail <?= $index === 0 ? 'active' : '' ?>"
                            data-image="<?= pdEsc($image) ?>"
                            aria-label="View product image <?= $index + 1 ?>"
                        >
                            <img
                                src="<?= pdEsc($image) ?>"
                                alt="Product image <?= $index + 1 ?>"
                                loading="lazy"
                            >
                        </button>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </div>

        <!-- PRODUCT INFORMATION -->

        <div class="pd-info">

            <p class="pd-category">
                <?= pdEsc($product['category_name']) ?>

                <?php if (!empty($product['product_code'])): ?>
                    <span>·</span>
                    <?= pdEsc($product['product_code']) ?>
                <?php endif; ?>
            </p>

            <div class="pd-title-row">
                <h1 class="pd-title"><?= pdEsc($product['product_name']) ?></h1>
                <?php include __DIR__ . '/../includes/product_favorite_button.php'; ?>
            </div>

            <!-- RATINGS AND STOCK -->

            <div class="pd-meta">

                <?php if ($reviewCount > 0): ?>

                    <span class="pd-stars">
                        ★★★★★
                    </span>

                    <strong>
                        <?= number_format($averageRating, 1) ?>
                    </strong>

                    <a href="#customerReviews">
                        <?= $reviewCount ?> reviews
                    </a>

                <?php else: ?>

                    <a href="#customerReviews">
                        No reviews yet
                    </a>

                <?php endif; ?>

                <span
                    id="pdStock"
                    class="pd-stock <?= (int) $selectedVariant['stock_quantity'] <= 0 ? 'out' : '' ?>"
                >
                    <?= (int) $selectedVariant['stock_quantity'] > 0
                        ? 'In Stock (' . (int) $selectedVariant['stock_quantity'] . ')'
                        : 'Out of Stock' ?>
                </span>

            </div>

            <!-- DESCRIPTION -->

            <p class="pd-description">
                <?= nl2br(pdEsc($product['description'])) ?>
            </p>

            <!-- KEY BENEFITS -->

            <?php if ($benefits): ?>

                <div class="pd-benefits">

                    <p class="pd-field-label">
                        KEY BENEFITS
                    </p>

                    <?php foreach ($benefits as $benefit): ?>

                        <div class="pd-benefit-item">

                            <span class="pd-benefit-check">
                                ✓
                            </span>

                            <span>
                                <?= pdEsc($benefit['benefit_text']) ?>
                            </span>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

            <!-- SIZE SELECTION -->

            <div class="pd-field">

                <p class="pd-field-label">
                    CHOOSE A SIZE
                </p>

                <div class="pd-variants" id="pdVariants">

                    <?php foreach ($variants as $variant): ?>

                        <?php
                        $selected =
                            (int) $selectedVariant['id'] ===
                            (int) $variant['id'];
                        ?>

                        <button
                            type="button"
                            class="pd-variant <?= $selected ? 'active' : '' ?>"
                            data-id="<?= (int) $variant['id'] ?>"
                            data-price="<?= (float) $variant['price'] ?>"
                            data-stock="<?= (int) $variant['stock_quantity'] ?>"
                            aria-pressed="<?= $selected ? 'true' : 'false' ?>"
                            title="<?= pdEsc($variant['variant_label']) ?>"
                            <?= (int) $variant['stock_quantity'] <= 0 ? 'disabled' : '' ?>
                        >
                            <?= pdEsc($variant['size_capacity']) ?>
                        </button>

                    <?php endforeach; ?>

                </div>

            </div>

            <!-- SUITABLE SKIN TYPES -->

            <div class="pd-field">

                <p class="pd-field-label">
                    SUITABLE FOR
                </p>

                <div class="pd-tags">

                    <?php foreach ($skinTypes as $type): ?>

                        <span class="pd-tag pd-tag-skin">
                            <?= pdEsc($type['skin_type_name']) ?>
                        </span>

                    <?php endforeach; ?>

                    <?php if (!$skinTypes): ?>
                        <span class="pd-muted">Not specified</span>
                    <?php endif; ?>

                </div>

            </div>

            <!-- TARGETED SKIN CONCERNS -->

            <div class="pd-field pd-targets-field">

                <p class="pd-field-label">
                    TARGETS
                </p>

                <div class="pd-tags">

                    <?php foreach ($skinConcerns as $concern): ?>

                        <span class="pd-tag pd-tag-peach">
                            <?= pdEsc($concern['concern_name']) ?>
                        </span>

                    <?php endforeach; ?>

                    <?php if (!$skinConcerns): ?>
                        <span class="pd-muted">Not specified</span>
                    <?php endif; ?>

                </div>

            </div>

            <!-- PRICE BELOW TARGETS -->

            <div class="pd-price" id="pdPrice">
                ₱<?= number_format((float) $selectedVariant['price'], 2) ?>
            </div>

            <!-- ADD TO BAG -->

            <form
                class="pd-purchase"
                id="pdCartForm"
                method="POST"
                action="<?= BASE_URL ?>/actions/cart/add.php"
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= pdEsc($csrfToken) ?>"
                >

                <input
                    type="hidden"
                    name="product_id"
                    value="<?= $productId ?>"
                >

                <input
                    type="hidden"
                    name="product_variant_id"
                    id="pdVariantInput"
                    value="<?= (int) $selectedVariant['id'] ?>"
                >

                <div class="pd-quantity">

                    <button
                        type="button"
                        id="pdDecrease"
                        aria-label="Decrease quantity"
                    >
                        −
                    </button>

                    <input
                        type="number"
                        name="quantity"
                        id="pdQuantity"
                        value="1"
                        min="1"
                        max="<?= max(1, (int) $selectedVariant['stock_quantity']) ?>"
                        readonly
                    >

                    <button
                        type="button"
                        id="pdIncrease"
                        aria-label="Increase quantity"
                    >
                        +
                    </button>

                </div>

                <button
                    type="submit"
                    class="pd-add-button"
                    id="pdAddButton"
                    <?= $totalStock <= 0 ? 'disabled' : '' ?>
                >
                    <?= $totalStock > 0
                        ? 'Add to Bag'
                        : 'Out of Stock' ?>
                </button>

            </form>

            <!-- SHIPPING INFORMATION -->

            <div class="pd-shipping">

                <i class="fa-solid fa-truck-fast"></i>

                <span>
                    Shipping fees are calculated at checkout.
                </span>

            </div>

        </div>

    </section>

    <!-- PRODUCT DETAILS TABS -->

    <section class="pd-details">

        <div class="pd-tab-navigation" role="tablist">

            <button
                type="button"
                class="pd-tab active"
                data-tab="ingredients"
                role="tab"
                aria-selected="true"
            >
                Key Ingredients
            </button>

            <button
                type="button"
                class="pd-tab"
                data-tab="usage"
                role="tab"
                aria-selected="false"
            >
                How to Use
            </button>

            <button
                type="button"
                class="pd-tab"
                data-tab="concerns"
                role="tab"
                aria-selected="false"
            >
                Skin Concerns
            </button>

        </div>

        <!-- INGREDIENTS -->

        <div
            class="pd-tab-panel active"
            id="pd-panel-ingredients"
            role="tabpanel"
        >

            <?php if ($ingredients): ?>

                <div class="pd-ingredient-grid">

                    <?php foreach ($ingredients as $ingredient): ?>

                        <div class="pd-ingredient-card">
                            <?= pdEsc($ingredient['ingredient_name']) ?>
                        </div>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <p class="pd-empty">
                    No ingredient information available.
                </p>

            <?php endif; ?>

        </div>

        <!-- HOW TO USE -->

        <div
            class="pd-tab-panel"
            id="pd-panel-usage"
            role="tabpanel"
            hidden
        >

            <?php if ($usageSteps): ?>

                <div class="pd-usage-list">

                    <?php foreach ($usageSteps as $index => $step): ?>

                        <div class="pd-usage-step">

                            <span class="pd-step-number">
                                <?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?>
                            </span>

                            <p><?= pdEsc($step) ?></p>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <p class="pd-empty">
                    Usage instructions are not available.
                </p>

            <?php endif; ?>

        </div>

        <!-- SKIN CONCERNS -->

        <div
            class="pd-tab-panel"
            id="pd-panel-concerns"
            role="tabpanel"
            hidden
        >

            <?php if ($skinConcerns): ?>

                <div class="pd-concern-grid">

                    <?php foreach ($skinConcerns as $concern): ?>

                        <div class="pd-concern-card">

                            <h3>
                                <?= pdEsc($concern['concern_name']) ?>
                            </h3>

                            <span>TARGETED</span>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <p class="pd-empty">
                    No targeted skin concerns available.
                </p>

            <?php endif; ?>

        </div>

    </section>

    <!-- CUSTOMER REVIEWS -->

    <section class="pd-reviews" id="customerReviews">

        <div class="pd-reviews-heading">

            <div>
                <p class="pd-eyebrow">
                    REAL ROUTINES, REAL RESULTS
                </p>

                <h2>Customer reviews</h2>
            </div>

            <div class="pd-rating-summary">

                <?php if ($reviewCount > 0): ?>

                    <strong>
                        <?= number_format($averageRating, 1) ?>
                    </strong>

                    <div>
                        <span class="pd-stars">★★★★★</span>

                        <small>
                            Based on <?= $reviewCount ?> reviews
                        </small>
                    </div>

                <?php else: ?>

                    <div>
                        <strong>—</strong>
                        <small>No ratings yet</small>
                    </div>

                <?php endif; ?>

            </div>

        </div>

        <!-- REVIEW FILTERS -->

        <div class="pd-review-toolbar">

            <div class="pd-review-filter">

                <label for="pdSkinFilter">
                    SKIN TYPE
                </label>

                <select id="pdSkinFilter">
                    <option value="all">All skin types</option>
                    <option value="oily">Oily</option>
                    <option value="dry">Dry</option>
                    <option value="combination">Combination</option>
                    <option value="normal">Normal</option>
                    <option value="sensitive">Sensitive</option>
                </select>

            </div>

            <div class="pd-review-filter">

                <label for="pdReviewSort">
                    SORT
                </label>

                <select id="pdReviewSort">
                    <option value="recent">Most Recent</option>
                    <option value="highest">Highest Rated</option>
                    <option value="lowest">Lowest Rated</option>
                    <option value="photos">With Photos</option>
                    <option value="helpful">Most Helpful</option>
                </select>

            </div>

        </div>

        <!-- REVIEW LIST -->

        <div class="pd-review-list" id="pdReviewList">

            <?php foreach ($reviews as $review): ?>

                <?php
                $reviewId = (int) $review['id'];
                $photos = $reviewImages[$reviewId] ?? [];

                $firstName = trim((string) $review['first_name']);

                $lastInitial = strtoupper(substr(
                    trim((string) $review['last_name']),
                    0,
                    1
                ));

                $displayName = trim(
                    $firstName . ' ' .
                    ($lastInitial ? $lastInitial . '.' : '')
                );

                $initial = strtoupper(substr($firstName, 0, 1));

                $dateValue = strtotime($review['created_at']) ?: 0;
                $reviewDate = date('F j, Y', $dateValue);

                $vote = $userVotes[$reviewId] ?? '';
                ?>

                <article
                    class="pd-review-card"
                    data-skin="<?= pdEsc(strtolower((string) $review['skin_type'])) ?>"
                    data-rating="<?= (int) $review['rating'] ?>"
                    data-date="<?= $dateValue ?>"
                    data-photos="<?= count($photos) ?>"
                    data-helpful="<?= (int) $review['helpful_count'] ?>"
                >

                    <div class="pd-review-author">

                        <div class="pd-review-avatar">
                            <?= pdEsc($initial ?: 'C') ?>
                        </div>

                        <div>

                            <strong>
                                <?= pdEsc($displayName) ?>
                            </strong>

                            <small>
                                <?= (int) $review['verified_purchase'] === 1
                                    ? 'Verified buyer'
                                    : 'Customer' ?>

                                <?php if (!empty($review['skin_type'])): ?>
                                    · <?= pdEsc($review['skin_type']) ?> skin
                                <?php endif; ?>
                            </small>

                        </div>

                    </div>

                    <div class="pd-review-rating-line">

                        <span class="pd-stars">
                            <?= str_repeat('★', (int) $review['rating']) ?>
                            <span class="pd-unfilled-stars"><?= str_repeat('☆', 5 - (int) $review['rating']) ?></span>
                        </span>

                        <time datetime="<?= date('Y-m-d', $dateValue) ?>">
                            <?= pdEsc($reviewDate) ?>
                        </time>

                    </div>

                    <h3>
                        <?= pdEsc($review['title']) ?>
                    </h3>

                    <p class="pd-review-text">
                        <?= nl2br(pdEsc($review['review_text'])) ?>
                    </p>

                    <?php if ($photos): ?>

                        <div class="pd-review-photos">

                            <?php foreach ($photos as $photo): ?>

                                <a
                                    href="<?= pdEsc($photo) ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <img
                                        src="<?= pdEsc($photo) ?>"
                                        alt="Customer review photo"
                                        loading="lazy"
                                    >
                                </a>

                            <?php endforeach; ?>

                        </div>

                    <?php endif; ?>

                    <div class="pd-review-footer">

                        <small>
                            <span class="pd-helpful-count">
                                <?= (int) $review['helpful_count'] ?>
                            </span>
                            people found this helpful
                        </small>

                        <div class="pd-helpful-actions">

                            <span>Was this helpful?</span>

                            <button
                                type="button"
                                class="pd-vote-button <?= $vote === 'up' ? 'active' : '' ?>"
                                data-review="<?= $reviewId ?>"
                                data-vote="up"
                                aria-label="Helpful"
                                aria-pressed="<?= $vote === 'up' ? 'true' : 'false' ?>"
                            >
                                <i class="fa-regular fa-thumbs-up"></i>
                            </button>

                            <button
                                type="button"
                                class="pd-vote-button <?= $vote === 'down' ? 'active' : '' ?>"
                                data-review="<?= $reviewId ?>"
                                data-vote="down"
                                aria-label="Not helpful"
                                aria-pressed="<?= $vote === 'down' ? 'true' : 'false' ?>"
                            >
                                <i class="fa-regular fa-thumbs-down"></i>
                            </button>

                        </div>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

        <p
            class="pd-empty"
            id="pdNoReviews"
            <?= $reviews ? 'hidden' : '' ?>
        >
            No customer reviews match your selection.
        </p>

    </section>

</div>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script nonce="<?= pvScriptNonce() ?>">
window.PUREVIA_PRODUCT = <?= pvJson([
    'voteUrl' => BASE_URL . '/actions/reviews/vote.php',
    'csrfToken' => $csrfToken,
    'loggedIn' => $userId > 0
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>

<script nonce="<?= pvScriptNonce() ?>" src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script nonce="<?= pvScriptNonce() ?>" src="<?= BASE_URL ?>/assets/js/product_details.js"></script>
<script nonce="<?= pvScriptNonce() ?>" src="<?= BASE_URL ?>/assets/js/search.js"></script>
<script nonce="<?= pvScriptNonce() ?>">
window.PUREVIA_BASE_URL = <?= pvJson(BASE_URL) ?>;
</script>
<script nonce="<?= pvScriptNonce() ?>" src="<?= BASE_URL ?>/assets/js/cart_add.js"></script>
<script nonce="<?= pvScriptNonce() ?>" src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
