<?php

/* =========================================
   LOAD CONFIGURATION
========================================= */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';

/* =========================================
   LOGIN MODAL MESSAGE
========================================= */

$loginError = $_SESSION['login_error'] ?? '';
$loginEmail = $_SESSION['login_email'] ?? '';
$loginSuccess = $_SESSION['login_success'] ?? '';

$showLoginModal =
    !empty($loginError) ||
    !empty($loginSuccess);


/* =========================================
   REGISTER MODAL MESSAGE
========================================= */

$registerError =
    $_SESSION['register_error'] ?? '';

$registerData =
    $_SESSION['register_data'] ?? [];

$registerFirstName =
    $registerData['first_name'] ?? '';

$registerLastName =
    $registerData['last_name'] ?? '';

$registerEmail =
    $registerData['email'] ?? '';

$showRegisterModal =
    !empty($registerError);


/* =========================================
   CLEAR TEMPORARY SESSION MESSAGES
========================================= */

unset($_SESSION['login_error']);
unset($_SESSION['login_email']);
unset($_SESSION['login_success']);

unset($_SESSION['register_error']);
unset($_SESSION['register_data']);

/* =========================================
   PAGE INFORMATION
========================================= */

$pageTitle = "PureVia Skin Care";


/* =========================================
   GET PRODUCTS FROM DATABASE
========================================= */


$sql = "
    SELECT
        p.id,
        p.product_name,
        p.description,
        p.price,
        p.image,
        p.stock_quantity,
        c.category_name,

        (
            SELECT pi.image_path
            FROM product_images pi
            WHERE pi.product_id = p.id
            ORDER BY pi.sort_order ASC, pi.id ASC
            LIMIT 1
        ) AS primary_image

    FROM products p

    INNER JOIN categories c
        ON p.category_id = c.id

    WHERE p.status = 'active'
      AND c.status = 'active'

    ORDER BY p.created_at DESC, p.id DESC

    LIMIT 8
";

$productResult = $conn->query($sql);

if (!$productResult) {
    error_log(
        'Homepage product query failed: ' . $conn->error
    );
}


/* =========================================
   CART SECURITY TOKEN
========================================= */

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* =========================================
   HOMEPAGE PRODUCT VARIANTS
========================================= */

$homeVariants = [];

if ($productResult && $productResult->num_rows > 0) {
    $productIds = [];

    foreach ($productResult as $row) {
        $productIds[] = (int) $row['id'];
    }

    if ($productIds) {
        $idList = implode(',', $productIds);

        $variantResult = $conn->query("
            SELECT
                id,
                product_id,
                size_capacity,
                variant_label,
                price,
                stock_quantity
            FROM product_variants
            WHERE product_id IN ($idList)
            ORDER BY id ASC
        ");

        if ($variantResult) {
            while ($variant = $variantResult->fetch_assoc()) {
                $homeVariants[(int) $variant['product_id']][] =
                    $variant;
            }
        }
    }

    $productResult->data_seek(0);
}

function renderHomeProductCard(array $product, array $homeVariants): void
{
?>
<article class="product-card">               
                                <?php
                                $imagePath = trim(
                                    (string) (
                                        $product['primary_image']
                                        ?: $product['image']
                                        ?: ''
                                    )
                                );

                                $defaultImage = BASE_URL . '/assets/images/products/default-product.jpg';

                                if ($imagePath === '') {

                                    $productImage = $defaultImage;

                                } elseif (
                                    filter_var($imagePath, FILTER_VALIDATE_URL) &&
                                    in_array(
                                        strtolower((string) parse_url($imagePath, PHP_URL_SCHEME)),
                                        ['http', 'https'],
                                        true
                                    )
                                ) {

                                    // External image URL
                                    $productImage = $imagePath;

                                } else {

                                    // Local image path
                                    $imagePath = str_replace('\\', '/', $imagePath);
                                    $imagePath = ltrim($imagePath, '/');

                                    // Only allow files in the product uploads directory
                                    $filename = basename($imagePath);

                                    $fullPath = __DIR__ . '/../uploads/products/' . $filename;

                                    if (
                                        $filename !== '.' &&
                                        $filename !== '..' &&
                                        is_file($fullPath)
                                    ) {
                                        $productImage =
                                            BASE_URL . '/uploads/products/' . rawurlencode($filename);
                                    } else {
                                        $productImage = $defaultImage;
                                    }
                                }
                                ?>

                                <a
                                    href="<?= BASE_URL ?>/public/product.php?id=<?= (int) $product['id'] ?>"
                                    class="product-image-wrapper"
                                >
                                    <img
                                        src="<?= htmlspecialchars(
                                            $productImage,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                        alt="<?= htmlspecialchars(
                                            $product['product_name'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                        class="product-image"
                                        loading="lazy"
                                        onerror="this.onerror=null;this.src='<?= BASE_URL ?>/assets/images/products/default-product.jpg';"
                                    >
                                </a>


                                    <div class="product-details">

                                        <h3 class="product-name">
                                            <a
                                                href="<?= BASE_URL ?>/public/product.php?id=<?= (int) $product['id'] ?>"
                                            >
                                                <?= htmlspecialchars(
                                                    $product['product_name']
                                                ) ?>
                                            </a>
                                        </h3>

                                        <p class="product-description">
                                            <?= htmlspecialchars(
                                                $product['description'] ?? ''
                                            ) ?>
                                        </p>

                                        
                                        <?php
                                            $productId = (int) $product['id'];
                                            $availableVariants = $homeVariants[$productId] ?? [];

                                            $selectedVariant = null;

                                            foreach ($availableVariants as $variant) {
                                                if ((int) $variant['stock_quantity'] > 0) {
                                                    $selectedVariant = $variant;
                                                    break;
                                                }
                                            }

                                            $selectedPrice = $selectedVariant
                                                ? (float) $selectedVariant['price']
                                                : (float) $product['price'];
                                        ?>

                                        <form
                                            class="pv-add-cart-form pv-home-cart-form"
                                            method="POST"
                                            action="<?= BASE_URL ?>/actions/cart/add.php"
                                        >
                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= htmlspecialchars(
                                                    $_SESSION['csrf_token'],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="product_id"
                                                value="<?= $productId ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="product_variant_id"
                                                class="pv-home-variant-input"
                                                value="<?= $selectedVariant
                                                    ? (int) $selectedVariant['id']
                                                    : 0 ?>"
                                            >

                                            <input type="hidden" name="quantity" value="1">

                                            <div
                                                class="product-size-row"
                                                aria-label="Available sizes"
                                            >
                                                <?php foreach ($availableVariants as $variant): ?>
                                                    <?php
                                                        $isSelected = $selectedVariant &&
                                                            (int) $selectedVariant['id'] ===
                                                            (int) $variant['id'];

                                                        $inStock =
                                                            (int) $variant['stock_quantity'] > 0;
                                                    ?>

                                                    <button
                                                        type="button"
                                                        class="size-option <?= $isSelected ? 'selected' : '' ?>"
                                                        data-variant-id="<?= (int) $variant['id'] ?>"
                                                        data-price="<?= (float) $variant['price'] ?>"
                                                        data-stock="<?= (int) $variant['stock_quantity'] ?>"
                                                        aria-pressed="<?= $isSelected ? 'true' : 'false' ?>"
                                                        <?= !$inStock ? 'disabled' : '' ?>
                                                    >
                                                        <?= htmlspecialchars(
                                                            $variant['size_capacity'],
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>
                                                    </button>
                                                <?php endforeach; ?>

                                                <?php if (!$availableVariants): ?>
                                                    <span>No sizes available</span>
                                                <?php endif; ?>
                                            </div>

                                            <div class="product-meta">
                                                <div class="product-rating">
                                                    <span class="stars">★★★★★</span>
                                                    <span class="review-count">
                                                        <?= htmlspecialchars(
                                                            $product['category_name'],
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>
                                                    </span>
                                                </div>

                                                <button
                                                    type="submit"
                                                    class="add-to-bag-btn"
                                                    <?= !$selectedVariant ? 'disabled' : '' ?>
                                                >
                                                    <span class="pv-home-add-label">
                                                        <?= $selectedVariant
                                                            ? 'Add to Bag'
                                                            : 'Out of Stock' ?>
                                                    </span>

                                                    <span class="product-price">
                                                        ₱<?= number_format($selectedPrice, 2) ?>
                                                    </span>
                                                </button>
                                            </div>
                                        </form>

                                    </div>

                                </article>
<?php
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title><?= htmlspecialchars($pageTitle) ?></title>

    <!-- Main Website CSS -->
    <!-- Bootstrap 5 CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">

    <!-- Header CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/header.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/footer.css">

    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/form1.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/search.css">
    
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/cart_add.css">



    <!-- Font Awesome Icons -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >
    <!-- Responsive adjustments -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/responsive.css">
</head>

<body>

    <?php include __DIR__ . '/../includes/header.php'; ?>


    <!-- =====================================
         MAIN CONTENT
    ====================================== -->

    <main>


        <!-- =================================
             HERO SECTION
        ================================== -->

        <section class="hero">

            <video class="hero-video" autoplay muted loop playsinline
                   preload="metadata" aria-hidden="true" tabindex="-1">
                <source src="<?= BASE_URL ?>/assets/images/website/model.mp4" type="video/mp4">
            </video>

            <!-- DARK OVERLAY -->

            <div class="hero-overlay"></div>


            <!-- HERO CONTENT -->

            <div class="hero-container">


                <!-- =========================
                     TOP LABEL
                ========================== -->

                <p class="hero-eyebrow">
                    CLEAN SKINCARE • CLEAR GUIDANCE
                </p>


                <!-- =========================
                     MAIN HEADING
                ========================== -->

                <h1 class="hero-title">
                    Pure Care. Clear
                    <br>
                    Choices.
                </h1>


                <!-- =========================
                     BOTTOM CONTENT
                ========================== -->

                <div class="hero-bottom">


                    <!-- LEFT SIDE -->

                    <div class="hero-info">

                        <p class="hero-description">
                            Gentle, effective skincare made simple.
                            Review ingredients, skin-type guidance,
                            usage, price, and availability before you buy.
                        </p>


                        <!-- HERO BUTTONS -->

                        <div class="hero-actions">

                            <a
                                href="<?= BASE_URL ?>/skin-match.php"
                                class="hero-primary-btn"
                            >
                                SHOP BY SKIN TYPE
                            </a>


                            <a
                                href="<?= BASE_URL ?>/skin-type-finder.php"
                                class="hero-secondary-btn"
                            >
                                See how it works
                            </a>

                        </div>

                    </div>


                    <!-- RIGHT SIDE / BENEFITS -->

                    <div class="hero-benefits">

                        <div class="hero-benefit">

                            <i class="fa-solid fa-check"></i>

                            <span>
                                CLEAN FORMULAS
                            </span>

                        </div>


                        <div class="hero-benefit">

                            <i class="fa-solid fa-check"></i>

                            <span>
                                SKIN-TYPE GUIDANCE
                            </span>

                        </div>


                        <div class="hero-benefit">

                            <i class="fa-solid fa-check"></i>

                            <span>
                                CLEAR PRICING
                            </span>

                        </div>

                    </div>


                </div>

            </div>

        </section>

        <section class="pv-trust-bar" aria-label="PureVia benefits">
            <div class="pv-trust-container">
                <?php foreach (['Dermatologist Tested', 'Clean Ingredients', 'Traceable botanicals', 'Fast Delivery'] as $benefit): ?>
                    <div class="pv-trust-item">
                        <div class="pv-trust-icon" aria-hidden="true">
                            <svg width="26" height="26" viewBox="0 0 105.296 105.296"
                                 xmlns="http://www.w3.org/2000/svg" fill="currentColor" focusable="false">
                                <g>
                                    <path d="M104.054,25.24c-0.784-0.318-2.036-0.979-3.528-1.377c-1.492-0.421-3.508-1.121-5.564-1.297c-2.077-0.217-4.425-0.281-6.991-0.157c-2.505,0.204-5.048,0.736-7.775,1.441c-2.724,0.703-5.216,1.873-7.878,3.153c-2.598,1.324-4.966,2.976-7.337,4.775c-2.241,1.858-4.367,3.915-6.311,6.102c-1.813,2.247-3.615,4.559-5.003,7.026c-1.431,2.45-2.665,4.973-3.558,7.553c-1.91,5.108-2.671,10.364-2.751,15.045c0.007,2.375,0.147,4.579,0.441,6.604c0.257,2.013,0.761,3.871,1.15,5.373c0.157,0.705,0.378,1.379,0.596,1.975c0.383-0.804,0.799-1.652,1.263-2.58c2.305-4.572,5.665-10.526,9.594-16.102c1.957-2.792,4.104-5.447,6.197-7.847c1.109-1.153,2.181-2.262,3.193-3.314c1.088-0.977,2.115-1.903,3.056-2.755c1.021-0.767,1.951-1.465,2.779-2.087c0.794-0.659,1.625-1.042,2.248-1.438c1.27-0.752,1.995-1.181,1.995-1.181s-0.691,0.488-1.896,1.332c-0.586,0.448-1.373,0.883-2.112,1.598c-0.766,0.676-1.629,1.437-2.573,2.267c-0.865,0.916-1.8,1.899-2.789,2.943c-0.91,1.109-1.872,2.279-2.864,3.491c-1.872,2.522-3.754,5.273-5.448,8.155c-3.411,5.738-6.223,11.807-8.115,16.426c-0.532,1.301-0.98,2.464-1.365,3.491c0.81-0.216,1.839-0.445,2.949-0.855c1.408-0.489,3.226-0.993,4.988-1.712c1.804-0.69,3.727-1.505,5.667-2.433c1.971-0.914,3.891-1.988,5.858-3.118c1.965-1.125,3.826-2.412,5.675-3.736c1.796-1.376,3.579-2.766,5.197-4.305c1.599-1.582,3.214-3.066,4.61-4.781c2.871-3.289,5.317-6.807,7.31-10.52c1.037-1.749,1.899-3.785,2.744-5.625c0.855-1.826,1.581-3.852,2.304-5.71c0.729-1.824,1.403-3.672,1.998-5.472c0.647-1.854,1.212-3.47,1.681-4.798c0.541-1.321,0.879-2.702,1.184-3.515c0.281-0.918,0.425-1.403,0.425-1.403S104.862,25.608,104.054,25.24z"/>
                                    <path d="M40.429,66.34c-2.792-1.958-5.444-4.106-7.845-6.201c-1.15-1.102-2.26-2.177-3.314-3.189c-0.979-1.088-1.899-2.115-2.758-3.056c-0.765-1.017-1.461-1.955-2.086-2.779c-0.659-0.795-1.037-1.625-1.436-2.248c-0.754-1.267-1.182-1.995-1.182-1.995s0.488,0.691,1.336,1.896c0.441,0.585,0.879,1.371,1.598,2.11c0.676,0.769,1.436,1.632,2.267,2.576c0.914,0.862,1.899,1.8,2.943,2.785c1.107,0.91,2.281,1.872,3.491,2.864c2.519,1.865,5.273,3.754,8.153,5.448c1.192,0.712,2.4,1.338,3.604,1.981c0.398-6.44,1.72-12.806,3.699-18.604c-1.134-1.466-2.284-2.909-3.549-4.23c-1.581-1.602-3.059-3.217-4.78-4.611c-3.291-2.871-6.805-5.316-10.517-7.312c-1.749-1.037-3.788-1.899-5.628-2.743c-1.822-0.855-3.846-1.584-5.705-2.303c-1.826-0.726-3.672-1.398-5.474-1.993c-1.853-0.652-3.465-1.217-4.798-1.686c-1.321-0.538-2.705-0.879-3.515-1.184c-0.914-0.277-1.401-0.424-1.401-0.424s-0.271,0.441-0.642,1.248c-0.315,0.782-0.977,2.036-1.377,3.528c-0.419,1.487-1.121,3.504-1.292,5.561c-0.221,2.079-0.284,4.43-0.157,6.995c0.198,2.503,0.729,5.042,1.436,7.773c0.708,2.722,1.875,5.217,3.153,7.881c1.326,2.591,2.976,4.959,4.781,7.334c1.857,2.245,3.914,4.37,6.1,6.317c2.243,1.807,4.56,3.61,7.031,5.003c2.445,1.431,4.969,2.666,7.548,3.556c5.106,1.913,10.362,2.673,15.047,2.755c0.2,0,0.371-0.021,0.561-0.027c-0.501-3.282-0.683-6.632-0.621-9.996C43.545,68.393,41.983,67.435,40.429,66.34z"/>
                                </g>
                            </svg>
                        </div>
                        <span><?= htmlspecialchars($benefit, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="new-arrivals bestsellers" aria-labelledby="newArrivalsTitle">
            <div class="bestsellers-container">
                <div class="new-arrivals-heading">
                    <h2 id="newArrivalsTitle">New Arrivals</h2>
                    <a class="shop-all-link" href="<?= BASE_URL ?>/public/shop.php">View all products</a>
                </div>
                <div class="new-arrivals-carousel">
                    <div class="new-arrivals-track" id="newArrivalsTrack" tabindex="0" aria-label="New arrival products">
                        <?php if ($productResult && $productResult->num_rows > 0): ?>
                            <?php while ($product = $productResult->fetch_assoc()): ?>
                                <?php renderHomeProductCard($product, $homeVariants); ?>
                            <?php endwhile; ?>
                            <?php $productResult->data_seek(0); ?>
                        <?php else: ?>
                            <p class="new-arrivals-empty">New arrivals are coming soon.</p>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="new-arrivals-nav new-arrivals-prev" data-arrivals-direction="-1" aria-label="Previous new arrivals" aria-controls="newArrivalsTrack" hidden>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" focusable="false"><path d="m15 5-7 7 7 7"/></svg>
                    </button>
                    <button type="button" class="new-arrivals-nav new-arrivals-next" data-arrivals-direction="1" aria-label="Next new arrivals" aria-controls="newArrivalsTrack" hidden>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" focusable="false"><path d="m9 5 7 7-7 7"/></svg>
                    </button>
                </div>
            </div>
        </section>

        <section class="bestsellers">

            <div class="bestsellers-container">

                <div class="bestsellers-layout">

                    <div class="bestsellers-intro">

                        <p class="section-eyebrow">
                            SKINCARE LOVED BY PUREVIA<br>
                            CUSTOMERS
                        </p>

                        <h2>
                            BEST SELLERS
                        </h2>

                        <p class="bestsellers-copy">
                            Discover our most-loved formulas,
                            chosen for visible results and trusted by
                            every kind of skin.
                        </p>

                        <p class="bestsellers-copy bestsellers-copy-secondary">
                            From barrier care to brightening, explore
                            the essentials customers return to again and
                            again.
                        </p>

                        <a
                            href="<?= BASE_URL ?>/public/shop.php"
                            class="shop-all-link"
                        >
                            <span>SEE ALL PRODUCTS</span>
                            <i class="fa-solid fa-chevron-right"></i>
                        </a>

                    </div>


                    <div class="product-grid">

                        <?php if (
                            $productResult &&
                            $productResult->num_rows > 0
                        ): ?>

                            <?php $bestSellerCount = 0; ?>
                            <?php while (
                                $bestSellerCount++ < 3 && ($product = $productResult->fetch_assoc())
                            ): ?>

                                <?php renderHomeProductCard($product, $homeVariants); ?>

                            <?php endwhile; ?>


                        <?php else: ?>

                            <p>No products are currently available.</p>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

        </section>


        <section class="pv-our-story" id="our-story" aria-labelledby="ourStoryTitle">
            <div class="pv-story-container">
                <div class="pv-story-collage">
                    <img
                        class="pv-story-landscape"
                        src="<?= BASE_URL ?>/assets/images/website/story-landscape.jpg"
                        alt="Misty mountain peaks above a green valley"
                        width="1536" height="1024" loading="lazy" decoding="async"
                    >
                    <img
                        class="pv-story-portrait"
                        src="<?= BASE_URL ?>/assets/images/website/story-apothecary.jpg"
                        alt="A woman working with botanicals in an apothecary"
                        width="1122" height="1402" loading="lazy" decoding="async"
                    >
                </div>

                <div class="pv-story-content">
                    <p class="pv-story-eyebrow">OUR ORIGIN</p>
                    <h2 id="ourStoryTitle">PureVia</h2>
                    <p>
                        We created PureVia for people who want skincare that is easy to understand,
                        easy to trust, and easy to use. Every formula is built around clear ingredients,
                        recommended skin types, and simple routines so you can shop with confidence
                        and see progress without the guesswork.
                    </p>
                    <blockquote>&ldquo;Your Clear Path to Better Skin.&rdquo;</blockquote>
                    <p>
                        We believe better skin starts with better choices. That is why every product
                        at PureVia is labeled with the information you actually need: what is inside,
                        who it is for, how to use it, and whether it is available now.
                    </p>
                    <details class="pv-story-more">
                        <summary>
                            <span>Read our full story</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" focusable="false"><path d="M4 12h16m-6-6 6 6-6 6"/></svg>
                        </summary>
                        <p>
                            Our approach is simple: help you understand your skincare before you buy.
                            Explore ingredients, skin-type guidance, and clear product information
                            to build a routine that fits your needs, one thoughtful choice at a time.
                        </p>
                    </details>
                </div>
            </div>
        </section>


        <section class="pv-home-reviews" aria-labelledby="homeReviewsTitle">
            <div class="pv-reviews-container">
                <div class="pv-reviews-heading">
                    <div>
                        <p class="pv-reviews-eyebrow">THE SKIN DIARIES</p>
                        <h2 id="homeReviewsTitle">Rituals worth repeating.</h2>
                    </div>
                    <p class="pv-reviews-summary">4.9 average &middot; 2,400+ verified reviews</p>
                </div>

                <div class="pv-reviews-grid">
                    <article class="pv-review-card">
                        <div class="pv-review-stars" role="img" aria-label="5 out of 5 stars">★★★★★</div>
                        <blockquote>&ldquo;Finally, a routine that makes sense.&rdquo;</blockquote>
                        <p>
                            I stopped overbuying because PureVia tells me exactly what I am getting,
                            who it is for, and how to use it. My skin feels calmer and the shopping
                            process feels much simpler.
                        </p>
                        <p class="pv-review-author">ELENA M. &middot; VERIFIED BUYER</p>
                    </article>

                    <article class="pv-review-card">
                        <div class="pv-review-stars" role="img" aria-label="5 out of 5 stars">★★★★★</div>
                        <blockquote>&ldquo;I can actually compare products now.&rdquo;</blockquote>
                        <p>
                            The ingredient labels and usage guidance are clear, the recommended skin
                            types are helpful, and the price is transparent. It feels like a brand
                            built for people who want to make informed decisions.
                        </p>
                        <p class="pv-review-author">NAOMI R. &middot; VERIFIED BUYER</p>
                    </article>

                    <article class="pv-review-card">
                        <div class="pv-review-stars" role="img" aria-label="5 out of 5 stars">★★★★★</div>
                        <blockquote>&ldquo;The information I need is right there.&rdquo;</blockquote>
                        <p>
                            I can see the ingredients, the recommended skin type, the usage guidance,
                            and the price before I buy. That clarity alone makes PureVia feel
                            trustworthy and premium.
                        </p>
                        <p class="pv-review-author">CAMILLE J. &middot; VERIFIED BUYER</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="pv-skin-quiz" aria-labelledby="skinQuizTitle">
            <div class="pv-skin-quiz-banner">
                <div class="pv-skin-quiz-copy">
                    <p class="pv-skin-quiz-eyebrow">FREE SKIN ANALYSIS</p>
                    <h2 id="skinQuizTitle">Not sure where to start?</h2>
                    <p class="pv-skin-quiz-description">
                        Our guided skin quiz takes 2 minutes. We'll identify your skin type,
                        flag ingredient conflicts, and recommend formulas suited to your
                        specific concerns.
                    </p>
                </div>
                <button type="button" class="pv-skin-quiz-button">
                    Start Skin Quiz <span aria-hidden="true">&rarr;</span>
                </button>
            </div>
        </section>

    </main>

    <!-- =========================================
        LOGIN MODAL
    ========================================== -->

    <div
        class="login-modal <?= $showLoginModal ? 'active' : '' ?>"
        id="loginModal"
        aria-hidden="<?= $showLoginModal ? 'false' : 'true' ?>"
    >

        <!-- BACKGROUND OVERLAY -->

        <div
            class="login-modal-overlay"
            id="loginModalOverlay"
        ></div>


        <!-- LOGIN CARD -->

        <section
            class="login-card"
            role="dialog"
            aria-modal="true"
            aria-labelledby="loginModalTitle"
        >


            <!-- CLOSE -->

            <button
                type="button"
                class="close-button"
                id="closeLoginModal"
                aria-label="Close login"
            >

                <i class="fa-solid fa-xmark"></i>

            </button>


            <!-- LEAF -->

            <div class="login-brand">

                <i class="fa-brands fa-pagelines"></i>

            </div>


            <!-- TITLE -->

            <h2 id="loginModalTitle">
                WELCOME BACK!
            </h2>


            <!-- ERROR -->

            <?php if ($loginError !== ''): ?>

                <div class="login-error">

                    <i class="fa-solid fa-circle-exclamation"></i>

                    <span>
                        <?= htmlspecialchars($loginError) ?>
                    </span>

                </div>

            <?php endif; ?>


            <!-- LOGIN FORM -->

            <form
                action="<?= BASE_URL ?>/actions/auth/login.php"
                method="POST"
                class="login-form"
            >
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>"
                >


                <!-- EMAIL -->

                <div class="form-group">

                    <label for="loginEmail">
                        Email
                    </label>

                    <input
                        type="email"
                        id="loginEmail"
                        name="email"
                        value="<?= htmlspecialchars($loginEmail) ?>"
                        autocomplete="email"
                        required
                    >

                </div>


                <!-- PASSWORD -->

                <div class="form-group">

                    <label for="loginPassword">
                        Password
                    </label>


                    <div class="password-wrapper">

                        <input
                            type="password"
                            id="loginPassword"
                            name="password"
                            autocomplete="current-password"
                            required
                        >


                        <button
                            type="button"
                            class="password-toggle"
                            id="passwordToggle"
                            aria-label="Show password"
                        >

                            <i
                                class="fa-regular fa-eye-slash"
                                id="passwordIcon"
                            ></i>

                        </button>

                    </div>


                    <a
                        href="<?= BASE_URL ?>/forgot-password.php"
                        class="forgot-password"
                    >
                        Forgot Password?
                    </a>

                </div>


                <!-- LOGIN -->

                <button
                    type="submit"
                    class="login-button"
                >
                    LOGIN
                </button>

            </form>


            <!-- REGISTER -->

            <p class="register-text">

                Don't have an account?

                <a href="#" id="openRegisterModal">
                    Register here
                </a>

            </p>

        </section>

    </div>

    <!-- =========================================
        REGISTER MODAL
    ========================================== -->

    <div
        class="register-modal <?= $showRegisterModal ? 'active' : '' ?>"
        id="registerModal"
        aria-hidden="<?= $showRegisterModal ? 'false' : 'true' ?>"
    >

        <!-- OVERLAY -->

        <div
            class="register-modal-overlay"
            id="registerModalOverlay"
        ></div>


        <!-- REGISTER CARD -->

        <section
            class="register-modal-card"
            role="dialog"
            aria-modal="true"
            aria-labelledby="registerModalTitle"
        >

            <!-- CLOSE BUTTON -->

            <button
                type="button"
                class="register-close-button"
                id="closeRegisterModal"
                aria-label="Close registration"
            >
                <i class="fa-solid fa-xmark"></i>
            </button>


            <!-- TOP TEXT -->

            <p class="register-eyebrow">
                WELCOME TO PUREVIA
            </p>


            <h2 id="registerModalTitle">
                CREATE ACCOUNT
            </h2>


            <p class="register-subtitle">
                A few details and your personal skincare space is ready.
            </p>


            <!-- ERROR -->

            <?php if ($registerError !== ''): ?>

                <div class="register-error">

                    <i class="fa-solid fa-circle-exclamation"></i>

                    <span>
                        <?= htmlspecialchars($registerError) ?>
                    </span>

                </div>

            <?php endif; ?>


            <!-- REGISTER FORM -->

            <form
                action="<?= BASE_URL ?>/actions/auth/register.php"
                method="POST"
                class="register-form"
            >
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>"
                >

                <!-- NAME ROW -->

                <div class="register-name-row">

                    <div class="register-form-group">

                        <label for="registerFirstName">
                            First Name
                        </label>

                        <input
                            type="text"
                            id="registerFirstName"
                            name="first_name"
                            value="<?= htmlspecialchars($registerFirstName) ?>"
                            maxlength="100"
                            autocomplete="given-name"
                            required
                        >

                    </div>


                    <div class="register-form-group">

                        <label for="registerLastName">
                            Last Name
                        </label>

                        <input
                            type="text"
                            id="registerLastName"
                            name="last_name"
                            value="<?= htmlspecialchars($registerLastName) ?>"
                            maxlength="100"
                            autocomplete="family-name"
                            required
                        >

                    </div>

                </div>


                <!-- EMAIL -->

                <div class="register-form-group">

                    <label for="registerEmail">
                        Email
                    </label>

                    <input
                        type="email"
                        id="registerEmail"
                        name="email"
                        value="<?= htmlspecialchars($registerEmail) ?>"
                        autocomplete="email"
                        required
                    >

                </div>


                <!-- PASSWORD -->

                <div class="register-form-group">

                    <label for="registerPassword">
                        Password
                    </label>

                    <div class="register-password-wrapper">

                        <input
                            type="password"
                            id="registerPassword"
                            name="password"
                            minlength="12"
                            autocomplete="new-password"
                            required
                        >

                        <button
                            type="button"
                            class="register-password-toggle"
                            data-password-target="registerPassword"
                            aria-label="Show password"
                        >
                            <i class="fa-regular fa-eye-slash"></i>
                        </button>

                    </div>

                </div>


                <!-- CONFIRM PASSWORD -->

                <div class="register-form-group">

                    <label for="registerConfirmPassword">
                        Confirm Password
                    </label>

                    <div class="register-password-wrapper">

                        <input
                            type="password"
                            id="registerConfirmPassword"
                            name="confirm_password"
                            minlength="12"
                            autocomplete="new-password"
                            required
                        >

                        <button
                            type="button"
                            class="register-password-toggle"
                            data-password-target="registerConfirmPassword"
                            aria-label="Show password"
                        >
                            <i class="fa-regular fa-eye-slash"></i>
                        </button>

                    </div>

                </div>


                <!-- TERMS -->

                <label class="register-terms">

                    <input
                        type="checkbox"
                        name="terms"
                        value="1"
                        required
                    >

                    <span>
                        I agree to the
                        <a href="<?= BASE_URL ?>/terms.php">
                            Terms of Service
                        </a>
                        and
                        <a href="<?= BASE_URL ?>/privacy-policy.php">
                            Privacy Policy
                        </a>
                    </span>

                </label>


                <!-- REGISTER BUTTON -->

                <button
                    type="submit"
                    class="register-submit-button"
                >
                    Register
                </button>

            </form>


            <!-- LOGIN -->

            <p class="register-login-text">

                Already have an account?

                <a href="#" id="backToLoginModal">
                    Login here
                </a>

            </p>

        </section>

    </div>

    <!-- =====================================
         FOOTER
    ====================================== -->

    <?php include __DIR__ . '/../includes/footer.php'; ?>


    <!-- =====================================
         JAVASCRIPT
    ====================================== -->
    <script src="<?= BASE_URL ?>/assets/js/main.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/search.js"></script>
    <script>
        window.PUREVIA_BASE_URL = <?= json_encode(BASE_URL) ?>;
    </script>
    <script src="<?= BASE_URL ?>/assets/js/cart_add.js"></script>
    

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
