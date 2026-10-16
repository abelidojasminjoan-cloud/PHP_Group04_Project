<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/product_data.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!productAdminAllowed($conn)) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$pageTitle = 'Products';

function productEscape(?string $value): string
{
    return htmlspecialchars(
        $value ?? '',
        ENT_QUOTES,
        'UTF-8'
    );
}

if (empty($_SESSION['csrf_product'])) {
    $_SESSION['csrf_product'] = bin2hex(random_bytes(32));
}

/* CATEGORIES */

$categories = [];

$result = $conn->query("
    SELECT id, category_name
    FROM categories
    WHERE status = 'active'
    ORDER BY category_name
");

while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

/* SKIN TYPES, CONCERNS, INGREDIENTS */

function loadProductOptions(
    mysqli $conn,
    string $table,
    string $column
): array {
    $allowed = [
        'skin_types' => 'skin_type_name',
        'skin_concerns' => 'concern_name',
        'ingredients' => 'ingredient_name'
    ];

    if (($allowed[$table] ?? null) !== $column) {
        throw new InvalidArgumentException('Invalid option source.');
    }

    $items = [];

    $result = $conn->query("
        SELECT id, {$column} AS label
        FROM {$table}
        WHERE status = 'active'
        ORDER BY {$column}
    ");

    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }

    return $items;
}

$skinTypes = loadProductOptions(
    $conn,
    'skin_types',
    'skin_type_name'
);

$skinConcerns = loadProductOptions(
    $conn,
    'skin_concerns',
    'concern_name'
);

$ingredients = loadProductOptions(
    $conn,
    'ingredients',
    'ingredient_name'
);

/* PRODUCTS */

$products = [];

$result = $conn->query("
    SELECT
        p.id,
        p.product_name,
        p.product_code,
        p.price,
        p.stock_quantity,
        p.image,
        p.status,
        c.category_name,

        GROUP_CONCAT(
            CONCAT(
                pv.size_capacity,
                '::',
                COALESCE(pv.variant_label, '')
            )
            ORDER BY pv.id
            SEPARATOR '||'
        ) AS variants

    FROM products p

    INNER JOIN categories c
        ON c.id = p.category_id

    LEFT JOIN product_variants pv
        ON pv.product_id = p.id

    GROUP BY
        p.id,
        p.product_name,
        p.product_code,
        p.price,
        p.stock_quantity,
        p.image,
        p.status,
        c.category_name

    ORDER BY p.id DESC
");

while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

$success = $_SESSION['product_success'] ?? '';
$error = $_SESSION['product_error'] ?? '';

unset(
    $_SESSION['product_success'],
    $_SESSION['product_error']
);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Products | PureVia Admin</title>

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
        href="<?= BASE_URL ?>/assets/css/product.css"
    >

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/assets/css/form2.css"
    >
    <!-- Responsive adjustments -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/responsive.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin_delete.css">
</head>

<body>

<?php
include __DIR__ . '/../../includes/sidemenu.php';
?>

<div class="admin-layout">

    <header class="admin-topbar">
        <p class="admin-topbar-title">Products</p>
    </header>

    <main class="products-main">

        <section class="products-page-header">

            <div class="products-heading">
                <h1>Products</h1>

                <p>
                    <span id="productCount">
                        <?= count($products) ?>
                    </span>
                    records
                </p>
            </div>

            <button
                type="button"
                class="add-product-button"
                id="openAddProductModal"
            >
                <i class="fa-solid fa-plus"></i>
                Add Product
            </button>

        </section>

        <?php if ($success !== ''): ?>
            <div class="form-alert form-alert-success">
                <?= productEscape($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="form-alert form-alert-error">
                <?= productEscape($error) ?>
            </div>
        <?php endif; ?>

        <section class="products-card">

            <div class="products-toolbar">

                <div class="products-search">
                    <i class="fa-solid fa-magnifying-glass"></i>

                    <input
                        type="search"
                        id="productSearch"
                        placeholder="Search products..."
                    >
                </div>

                <div class="product-filter-dropdown">

                    <i class="fa-solid fa-filter product-filter-icon"></i>

                    <select
                        id="categoryFilter"
                        class="product-filter-select"
                    >
                        <option value="all">
                            All Categories
                        </option>

                        <?php foreach ($categories as $category): ?>
                            <option value="<?= productEscape(strtolower($category['category_name'])) ?>">
                                <?= productEscape($category['category_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <i class="fa-solid fa-chevron-down product-filter-arrow"></i>

                </div>

                <div class="product-filter-dropdown">

                    <select
                        id="statusFilter"
                        class="product-filter-select status-filter-select"
                    >
                        <option value="all">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="out_of_stock">Out of Stock</option>
                    </select>

                    <i class="fa-solid fa-chevron-down product-filter-arrow"></i>

                </div>

            </div>

            <div class="products-table-wrapper">

                <table class="products-table">

                    <thead>
                        <tr>
                            <th>PRODUCT</th>
                            <th>SKU</th>
                            <th>CATEGORY</th>
                            <th>SIZES</th>
                            <th>STOCK</th>
                            <th>STATUS</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($products as $product): ?>

                        <?php
                        $searchData = strtolower(
                            $product['product_name'] . ' ' .
                            $product['product_code'] . ' ' .
                            $product['category_name']
                        );

                        $variantItems = $product['variants']
                            ? explode('||', $product['variants'])
                            : [];

                        $image = $product['image'] ?? '';

                        if (
                            $image !== '' &&
                            !preg_match('~^https?://~i', $image)
                        ) {
                            $image = BASE_URL . '/' . ltrim($image, '/');
                        }
                        ?>

                        <tr
                            class="product-row"
                            data-search="<?= productEscape($searchData) ?>"
                            data-category="<?= productEscape(strtolower($product['category_name'])) ?>"
                            data-status="<?= productEscape(strtolower($product['status'])) ?>"
                        >

                            <td>
                                <div class="product-information">

                                    <div class="product-image">

                                        <?php if ($image !== ''): ?>

                                            <img
                                                src="<?= productEscape($image) ?>"
                                                alt="<?= productEscape($product['product_name']) ?>"
                                                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                            >

                                            <div class="product-image-fallback" style="display:none;">
                                                <i class="fa-regular fa-image"></i>
                                            </div>

                                        <?php else: ?>

                                            <div class="product-image-fallback">
                                                <i class="fa-regular fa-image"></i>
                                            </div>

                                        <?php endif; ?>

                                    </div>

                                    <p class="product-name">
                                        <?= productEscape($product['product_name']) ?>
                                    </p>

                                </div>
                            </td>

                            <td class="product-sku">
                                <?= productEscape($product['product_code']) ?>
                            </td>

                            <td class="product-category">
                                <?= productEscape($product['category_name']) ?>
                            </td>

                            <td>
                                <div class="product-sizes">

                                    <?php foreach ($variantItems as $item): ?>

                                        <?php
                                        [$size, $label] = array_pad(
                                            explode('::', $item, 2),
                                            2,
                                            ''
                                        );
                                        ?>

                                        <span class="size-badge">
                                            <?= productEscape($size) ?>
                                        </span>

                                        <?php if ($label !== ''): ?>
                                            <span class="size-badge variant-label-badge">
                                                <?= productEscape($label) ?>
                                            </span>
                                        <?php endif; ?>

                                    <?php endforeach; ?>

                                </div>
                            </td>

                            <td>
                                <span class="product-stock <?= (int)$product['stock_quantity'] <= 10 ? 'low-stock' : '' ?>">
                                    <?= (int)$product['stock_quantity'] ?>
                                </span>
                            </td>

                            <td>
                                <span class="product-status status-<?= productEscape($product['status']) ?>">
                                    <?= productEscape(strtoupper(str_replace('_', ' ', $product['status']))) ?>
                                </span>
                            </td>

                            <td>
                                <div class="product-actions">

                                    <button
                                        type="button"
                                        data-product-id="<?= (int)$product['id'] ?>"
                                        class="product-action-button edit-product-button"
                                        title="Edit product"
                                        aria-label="Edit <?= productEscape($product['product_name']) ?>"
                                    >
                                        <i class="fa-regular fa-pen-to-square"></i>
                                    </button>

                                    <button
                                        type="button"
                                        class="product-action-button delete-product-button"
                                        data-product-id="<?= (int)$product['id'] ?>"
                                        data-product-name="<?= productEscape($product['product_name']) ?>"
                                        title="Delete product"
                                        aria-label="Delete <?= productEscape($product['product_name']) ?>"
                                    >
                                        <i class="fa-regular fa-trash-can"></i>
                                    </button>

                                </div>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                    <tr
                        id="noProductsFound"
                        class="no-products-row"
                        <?= count($products) ? 'style="display:none"' : '' ?>
                    >
                        <td colspan="7">
                            <div class="no-products-message">
                                <i class="fa-solid fa-box-open"></i>
                                <p>No products found.</p>
                            </div>
                        </td>
                    </tr>

                    </tbody>

                </table>

            </div>

        </section>

    </main>

</div>


<!-- ADD PRODUCT MODAL -->

<div
    class="form-modal"
    id="addProductModal"
    aria-hidden="true"
>

    <div
        class="form-modal-backdrop"
        data-close-form-modal
    ></div>

    <div
        class="form-modal-dialog form-modal-large"
        role="dialog"
        aria-modal="true"
        aria-labelledby="addProductTitle"
    >

        <form
            action="./add_product.php"
            method="POST"
            enctype="multipart/form-data"
            id="addProductForm"
            class="purevia-form"
            data-add-action="<?= BASE_URL ?>/admin/products/add_product.php"
            data-edit-action="<?= BASE_URL ?>/admin/products/edit_product.php"
            data-delete-action="<?= BASE_URL ?>/admin/products/delete_product.php"
            data-base-url="<?= BASE_URL ?>"
        >

            <input type="hidden" name="product_id" value="">
            <input type="hidden" name="product_revision" value="">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= productEscape($_SESSION['csrf_product']) ?>"
            >

            <!-- MODAL HEADER -->

            <div class="form-modal-header">

                <div>
                    <h2 id="addProductTitle">
                        Add New Product
                    </h2>

                    <p>
                        Fill in the details and add all available sizes
                    </p>
                </div>

                <button
                    type="button"
                    class="form-modal-close"
                    data-close-form-modal
                    aria-label="Close"
                >
                    <i class="fa-solid fa-xmark"></i>
                </button>

            </div>

            <!-- SCROLLABLE BODY -->

            <div class="form-modal-body">

                <!-- PRODUCT NAME AND SKU -->

                <div class="form-grid form-grid-2">

                    <div class="form-group">
                        <label for="productName">
                            Product Name
                            <span class="form-required">*</span>
                        </label>

                        <input
                            type="text"
                            id="productName"
                            name="product_name"
                            class="form-control"
                            placeholder="e.g. Brightening Essence"
                            maxlength="200"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="productCode">
                            SKU
                            <span class="form-required">*</span>
                        </label>

                        <input
                            type="text"
                            id="productCode"
                            name="product_code"
                            class="form-control"
                            placeholder="e.g. SRM-005"
                            maxlength="100"
                            required
                        >
                    </div>

                </div>

                <!-- CATEGORY AND STATUS -->

                <div class="form-grid form-grid-2">

                    <div class="form-group">
                        <label for="productCategory">
                            Category
                            <span class="form-required">*</span>
                        </label>

                        <div class="form-select-wrapper">

                            <select
                                id="productCategory"
                                name="category_id"
                                class="form-control form-select"
                                required
                            >
                                <option value="">
                                    Select category
                                </option>

                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= (int)$category['id'] ?>">
                                        <?= productEscape($category['category_name']) ?>
                                    </option>
                                <?php endforeach; ?>

                            </select>

                            <i class="fa-solid fa-chevron-down"></i>

                        </div>
                    </div>

                    <div class="form-group">
                        <label for="productStatus">
                            Status
                        </label>

                        <div class="form-select-wrapper">

                            <select
                                id="productStatus"
                                name="status"
                                class="form-control form-select"
                            >
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="out_of_stock">Out of stock</option>
                            </select>

                            <i class="fa-solid fa-chevron-down"></i>

                        </div>
                    </div>

                </div>

                <!-- DESCRIPTION -->

                <div class="form-group">

                    <label for="productDescription">
                        Description
                    </label>

                    <textarea
                        id="productDescription"
                        name="description"
                        class="form-control form-textarea"
                        placeholder="Product description..."
                        rows="3"
                    ></textarea>

                </div>

                <!-- PRODUCT IMAGES -->
                <section class="product-form-section">

                    <h3>Product images</h3>

                    <div class="product-images-layout">

                        <div class="product-images-inputs">

                            <label for="productImageUrl">
                                Image URL
                            </label>

                            <div class="image-url-row">

                                <input
                                    type="url"
                                    id="productImageUrl"
                                    class="form-control"
                                    placeholder="https://..."
                                >

                                <button
                                    type="button"
                                    id="addImageUrl"
                                    class="product-image-add"
                                >
                                    Add
                                </button>

                            </div>

                            <button
                                type="button"
                                id="uploadImagesButton"
                                class="product-upload-button"
                            >
                                <i class="fa-solid fa-arrow-up-from-bracket"></i>
                                Upload images
                            </button>

                            <input
                                type="file"
                                id="productImageFiles"
                                name="product_images[]"
                                accept="image/jpeg,image/png,image/webp"
                                multiple
                                hidden
                            >

                            <!-- IMAGE THUMBNAILS -->

                            <div
                                id="productImageList"
                                class="product-image-list"
                            ></div>

                            <p class="product-help">
                                Add image URLs or upload multiple images.
                                The first image will be the primary product image.
                                Maximum 10 images, 5 MB each.
                            </p>

                        </div>

                    </div>

                    <div id="productImageUrlFields"></div>

                </section>

                <!-- SKIN MATCHING -->

                <section class="product-form-section">

                    <h3>Skin matching &amp; formulation</h3>

                    <!-- SKIN TYPES -->

                    <div class="form-group">

                        <label>Skin Types</label>

                        <div class="skin-type-options">

                            <?php foreach ($skinTypes as $item): ?>

                                <label class="product-checkbox">

                                    <input
                                        type="checkbox"
                                        name="skin_type_ids[]"
                                        value="<?= (int)$item['id'] ?>"
                                    >

                                    <span>
                                        <?= productEscape($item['label']) ?>
                                    </span>

                                </label>

                            <?php endforeach; ?>

                        </div>

                    </div>

                    <!-- SKIN CONCERNS -->

                    <div class="form-group">

                        <label for="concernSearch">
                            Skin Concerns
                        </label>

                        <input
                            type="search"
                            id="concernSearch"
                            class="form-control checkbox-search"
                            placeholder="Search skin concerns..."
                        >

                        <div
                            class="checkbox-list"
                            id="concernOptions"
                        >

                            <?php foreach ($skinConcerns as $item): ?>

                                <label class="product-checkbox">

                                    <input
                                        type="checkbox"
                                        name="concern_ids[]"
                                        value="<?= (int)$item['id'] ?>"
                                    >

                                    <span>
                                        <?= productEscape($item['label']) ?>
                                    </span>

                                </label>

                            <?php endforeach; ?>

                        </div>

                    </div>

                    <!-- INGREDIENTS -->

                    <div class="form-group">

                        <label for="ingredientSearch">
                            Ingredients
                        </label>

                        <input
                            type="search"
                            id="ingredientSearch"
                            class="form-control checkbox-search"
                            placeholder="Search ingredients..."
                        >

                        <div
                            class="checkbox-list"
                            id="ingredientOptions"
                        >

                            <?php foreach ($ingredients as $item): ?>

                                <label class="product-checkbox">

                                    <input
                                        type="checkbox"
                                        name="ingredient_ids[]"
                                        value="<?= (int)$item['id'] ?>"
                                    >

                                    <span>
                                        <?= productEscape($item['label']) ?>
                                    </span>

                                </label>

                            <?php endforeach; ?>

                        </div>

                    </div>

                    <!-- KEY BENEFITS -->

                    <div class="form-group">

                        <label for="keyBenefits">
                            Key Benefits
                        </label>

                        <textarea
                            id="keyBenefits"
                            name="key_benefits"
                            class="form-control form-textarea"
                            rows="3"
                            placeholder="One benefit per line&#10;e.g. Supports the skin barrier&#10;Provides lasting hydration"
                        ></textarea>

                        <p class="product-help">
                            Enter one customer-facing benefit per line.
                        </p>

                    </div>

                </section>

                <!-- SIZES AND OPTIONS -->

                <section class="product-form-section variant-section">

                    <div class="form-section-header">

                        <div>
                            <h3>Sizes &amp; Options</h3>

                            <p>
                                Price and stock are set per size.
                                Base price is derived from the first size.
                            </p>
                        </div>

                        <button
                            type="button"
                            class="form-outline-button"
                            id="addSizeButton"
                        >
                            + Add Size
                        </button>

                    </div>

                    <div class="variant-scroll">

                        <div class="variant-header">
                            <span>SIZE / CAPACITY</span>
                            <span>LABEL (OPTIONAL)</span>
                            <span>PRICE (₱)</span>
                            <span>STOCK</span>
                            <span>THRESHOLD</span>
                            <span></span>
                        </div>

                        <div id="variantRows">

                            <div class="variant-row">

                                <input type="hidden" name="variant_id[]" value="">

                                <input
                                    type="text"
                                    name="size_capacity[]"
                                    class="form-control"
                                    placeholder="e.g. 30ml, 1 sheet"
                                    maxlength="100"
                                    required
                                >

                                <input
                                    type="text"
                                    name="variant_label[]"
                                    class="form-control"
                                    placeholder="e.g. Regular, Trial"
                                    maxlength="100"
                                >

                                <div class="price-input">

                                    <span>₱</span>

                                    <input
                                        type="number"
                                        name="variant_price[]"
                                        class="form-control"
                                        placeholder="0.00"
                                        min="0"
                                        step="0.01"
                                        required
                                    >

                                </div>

                                <input
                                    type="number"
                                    name="variant_stock[]"
                                    class="form-control"
                                    value="0"
                                    min="0"
                                    step="1"
                                    required
                                >

                                <input
                                    type="number"
                                    name="variant_threshold[]"
                                    class="form-control"
                                    value="15"
                                    min="0"
                                    step="1"
                                    required
                                >

                                <button
                                    type="button"
                                    class="variant-remove"
                                    aria-label="Remove size"
                                    disabled
                                >
                                    &times;
                                </button>

                            </div>

                        </div>

                    </div>

                    <p class="form-helper">
                        ↑ First size sets the base price shown in the catalog
                    </p>

                </section>

            </div>

            <!-- FIXED FOOTER -->

            <footer class="form-modal-footer">

                <button
                    type="button"
                    class="form-cancel-button"
                    data-close-form-modal
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="form-submit-button"
                    id="submitProductButton"
                >
                    Add Product
                </button>

            </footer>

        </form>

    </div>

</div>

<script src="<?= BASE_URL ?>/assets/js/sidemenu.js"></script>
<script src="<?= BASE_URL ?>/assets/js/products.js"></script>
<script src="<?= BASE_URL ?>/assets/js/product_form.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php include __DIR__ . '/../../includes/admin_delete_modal.php'; ?>
</body>
</html>
