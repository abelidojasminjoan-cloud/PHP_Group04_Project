<?php

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/product_data.php';
require_once __DIR__ . '/../../services/SecureUpload.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function productJsonRequest(): bool
{
    return str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
}

function failProduct(string $message, int $status = 400): never
{
    if (productJsonRequest()) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode(['success' => false, 'message' => $message]);
        exit;
    }
    $_SESSION['product_error'] = $message;

    header('Location: ./products.php');
    exit;
}

/* POST ONLY */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ./products.php');
    exit;
}

/* ADMIN ONLY */

try {
    $adminAllowed = productAdminAllowed($conn);
} catch (Throwable $error) {
    error_log('Product authorization failed: ' . $error->getMessage());
    failProduct('Unable to verify admin access. Please try again.', 503);
}
if (!$adminAllowed) {
    failProduct('Admin access is required. Please sign in again.', 403);
}

/* CSRF */

$token = $_POST['csrf_token'] ?? '';

if (
    !is_string($token) ||
    !is_string($_SESSION['csrf_product'] ?? null) ||
    $_SESSION['csrf_product'] === '' ||
    !hash_equals($_SESSION['csrf_product'], $token)
) {
    failProduct('Invalid security token. Refresh the page.', 403);
}

/* VALIDATE SELECTED DATABASE IDS */

function validProductIds(
    mysqli $conn,
    string $table,
    array $ids,
    array $existingIds = []
): array {

    $allowedTables = [
        'skin_types',
        'skin_concerns',
        'ingredients',
        'suitability_tags'
    ];

    if (!in_array($table, $allowedTables, true)) {
        throw new RuntimeException('Invalid selection table.');
    }

    if (count($ids) > 200) {
        throw new RuntimeException('Too many selections.');
    }

    foreach ($ids as $value) {
        if (
            !is_scalar($value) ||
            filter_var(
                $value,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            ) === false
        ) {
            throw new RuntimeException('Invalid selected item.');
        }
    }

    $ids = array_values(array_unique(array_map('intval', $ids)));

    if (!$ids) {
        return [];
    }

    $placeholders = implode(
        ',',
        array_fill(0, count($ids), '?')
    );

    $stmt = $conn->prepare("
        SELECT id, status
        FROM {$table}
        WHERE id IN ({$placeholders})
    ");

    $types = str_repeat('i', count($ids));

    $stmt->bind_param($types, ...$ids);
    $stmt->execute();

    $found = [];
    foreach ($stmt->get_result() as $row) {
        if ($row['status'] === 'active' || in_array((int) $row['id'], $existingIds, true)) {
            $found[] = (int) $row['id'];
        }
    }

    $stmt->close();

    if (count($found) !== count($ids)) {
        throw new RuntimeException(
            'Some selected options are invalid.'
        );
    }

    return $found;
}

/* MAIN PROCESS */

$savedFiles = [];
$transactionStarted = false;
$saveErrorStatus = 400;

try {

    /* PRODUCT INFORMATION */

    foreach (['product_name', 'product_code', 'status', 'description', 'key_benefits', 'product_id', 'product_revision'] as $field) {
        if (isset($_POST[$field]) && !is_string($_POST[$field])) {
            throw new RuntimeException('Invalid product details.');
        }
    }
    $rawProductId = $_POST['product_id'] ?? '';
    $isEditing = $rawProductId !== '';
    if (($requireProductEdit ?? false) && !$isEditing) {
        throw new RuntimeException('Select a product to edit.');
    }
    $productId = $isEditing ? filter_var($rawProductId, FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]) : 0;
    if ($productId === false) throw new RuntimeException('Invalid product.');

    $productName = trim(
        (string) ($_POST['product_name'] ?? '')
    );

    $productCode = strtoupper(trim(
        (string) ($_POST['product_code'] ?? '')
    ));

    $categoryId = filter_var(
        $_POST['category_id'] ?? null,
        FILTER_VALIDATE_INT
    );

    $status = (string) ($_POST['status'] ?? 'active');

    $description = trim(
        (string) ($_POST['description'] ?? '')
    );

    $benefitsText = trim(
        (string) ($_POST['key_benefits'] ?? '')
    );

    if (
        $productName === '' ||
        mb_strlen($productName) > 200 ||
        $productCode === '' ||
        strlen($productCode) > 100 ||
        !$categoryId || $categoryId < 1 ||
        !in_array($status, ['active', 'inactive', 'out_of_stock'], true) ||
        mb_strlen($description) > 10000 ||
        mb_strlen($benefitsText) > 5000
    ) {
        throw new RuntimeException(
            'Please check the product details.'
        );
    }

    // Scan uploads before acquiring product/database locks.
    $validatedUploads = SecureUpload::images($_FILES['product_images'] ?? null);

    $conn->begin_transaction();
    $transactionStarted = true;
    $existingProduct = $isEditing ? productEditData($conn, $productId, true) : null;
    if ($isEditing) {
        if ($existingProduct === null) {
            $saveErrorStatus = 404;
            throw new RuntimeException('This product no longer exists.');
        }
        $revision = $_POST['product_revision'] ?? '';
        if (!hash_equals($existingProduct['revision'], $revision)) {
            $saveErrorStatus = 409;
            throw new RuntimeException('This product changed after you opened the form. Close and reopen Edit to load the latest details before saving.');
        }
    }

    /* VALIDATE CATEGORY */

    $stmt = $conn->prepare("
        SELECT id, status
        FROM categories
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->bind_param('i', $categoryId);
    $stmt->execute();

    $category = $stmt->get_result()->fetch_assoc();
    $categoryExists = $category && ($category['status'] === 'active'
        || ($existingProduct && (int) $existingProduct['category_id'] === $categoryId));

    $stmt->close();

    if (!$categoryExists) {
        throw new RuntimeException('Invalid category.');
    }

    /* CHECK DUPLICATE SKU */

    $stmt = $conn->prepare("
        SELECT id
        FROM products
        WHERE product_code = ?
        AND id <> ?
        LIMIT 1
    ");

    $stmt->bind_param('si', $productCode, $productId);
    $stmt->execute();

    $skuExists = $stmt->get_result()->num_rows > 0;

    $stmt->close();

    if ($skuExists) {
        throw new RuntimeException('Product SKU already exists.');
    }

    /* VARIANTS */

    $sizes = $_POST['size_capacity'] ?? [];
    $labels = $_POST['variant_label'] ?? [];
    $prices = $_POST['variant_price'] ?? [];
    $stocks = $_POST['variant_stock'] ?? [];
    $thresholds = $_POST['variant_threshold'] ?? [];
    $variantIds = $_POST['variant_id'] ?? [];

    if (
        !is_array($sizes) ||
        count($sizes) < 1 ||
        count($sizes) > 50 ||
        !is_array($labels) ||
        !is_array($prices) ||
        !is_array($stocks) ||
        !is_array($thresholds) || !is_array($variantIds) ||
        array_keys($sizes) !== array_keys($labels) ||
        array_keys($sizes) !== array_keys($prices) ||
        array_keys($sizes) !== array_keys($stocks) ||
        array_keys($sizes) !== array_keys($thresholds) ||
        ($isEditing && array_keys($sizes) !== array_keys($variantIds))
    ) {
        throw new RuntimeException(
            'At least one valid product size is required.'
        );
    }

    $variants = [];
    $variantKeys = [];
    $totalStock = 0;
    $existingVariantIds = array_map('intval', array_column($existingProduct['variants'] ?? [], 'id'));
    $retainedVariantIds = [];

    foreach ($sizes as $index => $rawSize) {

        $rawVariantId = $variantIds[$index] ?? '';
        $variantId = $rawVariantId === '' ? 0 : filter_var($rawVariantId,
            FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($variantId === false || ($variantId &&
            (!in_array($variantId, $existingVariantIds, true) || in_array($variantId, $retainedVariantIds, true)))) {
            throw new RuntimeException('Invalid product size. Reload the product and try again.');
        }
        if ($variantId) $retainedVariantIds[] = $variantId;

        if (!is_string($rawSize)) {
            throw new RuntimeException('Invalid size.');
        }

        $size = trim($rawSize);

        $rawLabel = $labels[$index] ?? '';

        if (!is_string($rawLabel)) {
            throw new RuntimeException('Invalid variant label.');
        }

        $label = trim($rawLabel);

        $price = $prices[$index] ?? null;
        $stock = $stocks[$index] ?? null;
        $threshold = $thresholds[$index] ?? null;

        if (
            $size === '' ||
            mb_strlen($size) > 100 ||
            mb_strlen($label) > 100 ||
            !is_scalar($price) ||
            !preg_match(
                '/^\d{1,8}(\.\d{1,2})?$/',
                (string) $price
            ) ||
            !is_scalar($stock) ||
            !ctype_digit((string) $stock) ||
            !is_scalar($threshold) ||
            !ctype_digit((string) $threshold) ||
            (float) $price > 99999999.99 ||
            (float) $price < 0 ||
            (int) $stock > 100000000 ||
            (int) $threshold > 100000000
        ) {
            throw new RuntimeException(
                'Invalid size, price, stock, or threshold.'
            );
        }

        $key = mb_strtolower($size . '|' . $label);

        if (isset($variantKeys[$key])) {
            throw new RuntimeException(
                'Duplicate size and label combination.'
            );
        }

        $variantKeys[$key] = true;

        $variants[] = [
            'id' => $variantId,
            'size' => $size,
            'label' => $label === '' ? null : $label,
            'price' => (float) $price,
            'stock' => (int) $stock,
            'threshold' => (int) $threshold
        ];

        $totalStock += (int) $stock;
    }

    if ($totalStock > 4294967295) {
        throw new RuntimeException('Total stock exceeds the allowed limit.');
    }

    $basePrice = $variants[0]['price'];

    $finalStatus = $status === 'inactive' ? 'inactive'
        : ($totalStock === 0 ? 'out_of_stock' : $status);

    /* SKIN MATCHING */

    foreach (['skin_type_ids', 'concern_ids', 'ingredient_ids', 'suitability_tag_ids'] as $field) {
        if (isset($_POST[$field]) && !is_array($_POST[$field])) {
            throw new RuntimeException('Invalid product selections.');
        }
    }

    $skinTypeIds = validProductIds(
        $conn,
        'skin_types',
        is_array($_POST['skin_type_ids'] ?? null)
            ? $_POST['skin_type_ids']
            : [],
        array_map('intval', array_column($existingProduct['skin_types'] ?? [], 'id'))
    );

    $concernIds = validProductIds(
        $conn,
        'skin_concerns',
        is_array($_POST['concern_ids'] ?? null)
            ? $_POST['concern_ids']
            : [],
        array_map('intval', array_column($existingProduct['concerns'] ?? [], 'id'))
    );

    $ingredientIds = validProductIds(
        $conn,
        'ingredients',
        is_array($_POST['ingredient_ids'] ?? null)
            ? $_POST['ingredient_ids']
            : [],
        array_map('intval', array_column($existingProduct['ingredients'] ?? [], 'id'))
    );

    if (isset($_POST['suitability_tags_present']) && $_POST['suitability_tags_present'] !== '1') throw new RuntimeException('Invalid suitability selections.');
    $existingSuitabilityIds = array_map('intval', array_column($existingProduct['suitability_tags'] ?? [], 'id'));
    $suitabilityTagIds = validProductIds($conn, 'suitability_tags', isset($_POST['suitability_tags_present']) ? ($_POST['suitability_tag_ids'] ?? []) : $existingSuitabilityIds, $existingSuitabilityIds);
    $routineSettings = ProductSuitability::fromForm($_POST, $ingredientIds);

    /* IMAGE URLS */

    $retainedImages = $_POST['existing_image_ids'] ?? [];
    if (!is_array($retainedImages) || count($retainedImages) > 10) {
        throw new RuntimeException('Invalid existing image list.');
    }
    $resolvedByKey = [];
    $existingImages = array_column($existingProduct['images'] ?? [], 'image_path', 'id');
    foreach ($retainedImages as $rawImageId) {
        $imageId = filter_var($rawImageId, FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]);
        if ($imageId === false || !isset($existingImages[$imageId])
            || isset($resolvedByKey['existing:' . $imageId])) {
            throw new RuntimeException('Invalid existing product image.');
        }
        $resolvedByKey['existing:' . $imageId] = $existingImages[$imageId];
    }
    if (isset($_POST['keep_primary_image'])) {
        if (!$existingProduct || $existingProduct['images'] || !$existingProduct['image']) {
            throw new RuntimeException('Invalid primary product image.');
        }
        $resolvedByKey['legacy'] = $existingProduct['image'];
    }

    $imageUrls = $_POST['image_urls'] ?? [];

    if (!is_array($imageUrls) || count($imageUrls) > 10) {
        throw new RuntimeException('Invalid image list.');
    }

    $images = [];

    foreach ($imageUrls as $urlIndex => $url) {

        if (!is_string($url)) {
            throw new RuntimeException('Invalid image URL.');
        }

        $url = trim($url);

        if (
            strlen($url) > 2048 ||
            !filter_var($url, FILTER_VALIDATE_URL) ||
            !in_array(
                strtolower((string) parse_url($url, PHP_URL_SCHEME)),
                ['http', 'https'],
                true
            )
        ) {
            throw new RuntimeException('Invalid image URL.');
        }

        $images[] = [
            'type' => 'url',
            'key' => 'url:' . $urlIndex,
            'value' => $url
        ];
    }

    /* UPLOADED IMAGES: validated and scanned before the transaction. */

    foreach ($validatedUploads as $index => $uploadedImage) {
        $images[] = ['type' => 'file', 'key' => 'file:' . $index, 'value' => $index];
    }
    if (count($images) + count($resolvedByKey) > SecureUpload::MAX_IMAGES) {
        throw new RuntimeException('A maximum of 10 product images is allowed.');
    }
    foreach ($images as $image) {
        if ($image['type'] === 'url') {
            $resolvedByKey[$image['key']] = $image['value'];
            continue;
        }
        $stored = SecureUpload::store($validatedUploads[$image['value']], 'products');
        $savedFiles[] = $stored['path'];
        $resolvedByKey[$image['key']] = $stored['relative_path'];
    }

    // Keep the thumbnail order (including mixed existing images, URLs, and uploads).
    $imageOrder = $_POST['image_order'] ?? array_keys($resolvedByKey);
    if (!is_array($imageOrder) || count($imageOrder) !== count($resolvedByKey)) {
        throw new RuntimeException('Invalid product image order.');
    }
    $resolvedImages = [];
    foreach ($imageOrder as $key) {
        if (!is_string($key) || !array_key_exists($key, $resolvedByKey)) {
            throw new RuntimeException('Invalid product image order.');
        }
        $resolvedImages[] = $resolvedByKey[$key];
        unset($resolvedByKey[$key]);
    }

    // The legacy products.image column can hold at most 255 characters.
    if (isset($resolvedImages[0]) && mb_strlen($resolvedImages[0]) > 255) {
        throw new RuntimeException('The primary image URL must be 255 characters or less.');
    }

    /* SAVE PRODUCT */

    $mainImage = $resolvedImages[0] ?? null;

    if ($isEditing) {
        $stmt = $conn->prepare("
            UPDATE products SET category_id = ?, product_name = ?, product_code = ?,
                description = ?, price = ?, stock_quantity = ?, image = ?, status = ?
            WHERE id = ?
        ");
        $stmt->bind_param('isssdissi', $categoryId, $productName, $productCode,
            $description, $basePrice, $totalStock, $mainImage, $finalStatus, $productId);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conn->prepare("
            INSERT INTO products (
                category_id,
                product_name,
                product_code,
                description,
                price,
                stock_quantity,
                image,
                status
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            'isssdiss',
            $categoryId,
            $productName,
            $productCode,
            $description,
            $basePrice,
            $totalStock,
            $mainImage,
            $finalStatus
        );

        $stmt->execute();

        $productId = (int) $conn->insert_id;

        $stmt->close();
    }

    if ($routineSettings !== null) ProductSuitability::save($conn, $productId, $routineSettings);

    /* SAVE VARIANTS WITHOUT REPLACING EXISTING IDS */

    $updateVariant = $conn->prepare("
        UPDATE product_variants SET size_capacity = ?, variant_label = ?, price = ?,
            stock_quantity = ?, low_stock_threshold = ?
        WHERE id = ? AND product_id = ?
    ");

    $stmt = $conn->prepare("
        INSERT INTO product_variants (
            product_id,
            size_capacity,
            variant_label,
            price,
            stock_quantity,
            low_stock_threshold
        )
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    foreach ($variants as $variant) {

        $size = $variant['size'];
        $label = $variant['label'];
        $price = $variant['price'];
        $stock = $variant['stock'];
        $threshold = $variant['threshold'];

        if ($variant['id']) {
            $variantId = $variant['id'];
            $updateVariant->bind_param('ssdiiii', $size, $label, $price, $stock,
                $threshold, $variantId, $productId);
            $updateVariant->execute();
            continue;
        }

        $stmt->bind_param(
            'issdii',
            $productId,
            $size,
            $label,
            $price,
            $stock,
            $threshold
        );

        $stmt->execute();
    }

    $stmt->close();
    $updateVariant->close();

    // Only sizes explicitly removed from the form are deleted.
    $deleteVariant = $conn->prepare('DELETE FROM product_variants WHERE id = ? AND product_id = ?');
    foreach (array_diff($existingVariantIds, $retainedVariantIds) as $variantId) {
        $deleteVariant->bind_param('ii', $variantId, $productId);
        $deleteVariant->execute();
    }
    $deleteVariant->close();

    if ($isEditing) {
        // Keep unchanged associations in place; only remove selections unchecked in the form.
        $relations = [
            'product_skin_types' => ['skin_type_id', $skinTypeIds, $existingProduct['skin_types']],
            'product_concerns' => ['concern_id', $concernIds, $existingProduct['concerns']],
            'product_ingredients' => ['ingredient_id', $ingredientIds, $existingProduct['ingredients']],
            'product_suitability_tags' => ['suitability_tag_id', $suitabilityTagIds, $existingProduct['suitability_tags']]
        ];
        foreach ($relations as $table => [$column, $selectedIds, $oldItems]) {
            $oldIds = array_map('intval', array_column($oldItems, 'id'));
            $delete = $conn->prepare("DELETE FROM {$table} WHERE product_id = ? AND {$column} = ?");
            foreach (array_diff($oldIds, $selectedIds) as $removedId) {
                $delete->bind_param('ii', $productId, $removedId);
                $delete->execute();
            }
            $delete->close();
        }
        foreach (['product_images', 'product_benefits'] as $table) {
            $delete = $conn->prepare("DELETE FROM {$table} WHERE product_id = ?");
            $delete->bind_param('i', $productId);
            $delete->execute();
            $delete->close();
        }
    }

    /* SAVE SKIN TYPES */

    $stmt = $conn->prepare("
        INSERT INTO product_skin_types (
            product_id,
            skin_type_id
        )
        VALUES (?, ?)
    ");

    foreach ($skinTypeIds as $skinTypeId) {
        if (in_array($skinTypeId, array_map('intval', array_column($existingProduct['skin_types'] ?? [], 'id')), true)) continue;
        $stmt->bind_param(
            'ii',
            $productId,
            $skinTypeId
        );

        $stmt->execute();
    }

    $stmt->close();

    /* SAVE SKIN CONCERNS */

    $stmt = $conn->prepare("
        INSERT INTO product_concerns (
            product_id,
            concern_id
        )
        VALUES (?, ?)
    ");

    foreach ($concernIds as $concernId) {
        if (in_array($concernId, array_map('intval', array_column($existingProduct['concerns'] ?? [], 'id')), true)) continue;
        $stmt->bind_param(
            'ii',
            $productId,
            $concernId
        );

        $stmt->execute();
    }

    $stmt->close();

    /* SAVE INGREDIENTS */

    $stmt = $conn->prepare("
        INSERT INTO product_ingredients (
            product_id,
            ingredient_id
        )
        VALUES (?, ?)
    ");

    foreach ($ingredientIds as $ingredientId) {
        if (in_array($ingredientId, array_map('intval', array_column($existingProduct['ingredients'] ?? [], 'id')), true)) continue;
        $stmt->bind_param(
            'ii',
            $productId,
            $ingredientId
        );

        $stmt->execute();
    }

    $stmt->close();

    $stmt = $conn->prepare('INSERT INTO product_suitability_tags(product_id,suitability_tag_id) VALUES(?,?)');
    foreach ($suitabilityTagIds as $suitabilityTagId) {
        if (in_array($suitabilityTagId, $existingSuitabilityIds, true)) continue;
        $stmt->bind_param('ii', $productId, $suitabilityTagId);
        $stmt->execute();
    }
    $stmt->close();

    /* SAVE IMAGES */

    $stmt = $conn->prepare("
        INSERT INTO product_images (
            product_id,
            image_path,
            sort_order
        )
        VALUES (?, ?, ?)
    ");

    foreach ($resolvedImages as $index => $imagePath) {

        $sortOrder = (int) $index;

        $stmt->bind_param(
            'isi',
            $productId,
            $imagePath,
            $sortOrder
        );

        $stmt->execute();
    }

    $stmt->close();

    /* SAVE BENEFITS */

    if ($benefitsText !== '') {

        $benefitLines = preg_split('/\R/u', $benefitsText);

        $stmt = $conn->prepare("
            INSERT INTO product_benefits (
                product_id,
                benefit_text,
                sort_order
            )
            VALUES (?, ?, ?)
        ");

        $sortOrder = 0;

        foreach ($benefitLines as $benefitLine) {

            $benefit = trim($benefitLine);

            if ($benefit === '') {
                continue;
            }

            if (mb_strlen($benefit) > 500) {
                throw new RuntimeException(
                    'Each benefit must be 500 characters or less.'
                );
            }

            $stmt->bind_param(
                'isi',
                $productId,
                $benefit,
                $sortOrder
            );

            $stmt->execute();

            $sortOrder++;
        }

        $stmt->close();
    }

    /* AUDIT LOG */

    $adminId = (int) $_SESSION['user_id'];

    $auditAction = $isEditing ? 'UPDATE' : 'CREATE';
    $auditModule = 'Products';
    $recordId = (string) $productId;

    $auditDescription = sprintf(
        ($isEditing ? 'Updated' : 'Created') . ' product "%s" (%s).',
        $productName,
        $productCode
    );

    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;

    $stmt = $conn->prepare("
        INSERT INTO audit_logs (
            user_id,
            action,
            module,
            record_id,
            description,
            ip_address
        )
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        'isssss',
        $adminId,
        $auditAction,
        $auditModule,
        $recordId,
        $auditDescription,
        $ipAddress
    );

    $stmt->execute();
    $stmt->close();

    /* COMMIT */

    $conn->commit();
    $transactionStarted = false;

    $_SESSION['product_success'] =
        $isEditing ? 'Product updated successfully.' : 'Product added successfully.';

} catch (Throwable $exception) {

    if ($transactionStarted) {
        $conn->rollback();
    }

    foreach ($savedFiles as $path) {
        if (is_file($path)) {
            unlink($path);
        }
    }

    error_log(
        'Save Product Error: ' . $exception->getMessage()
    );

    $message =
        $exception instanceof RuntimeException && !($exception instanceof mysqli_sql_exception)
            ? $exception->getMessage()
            : 'Unable to save the product. Please try again.';
    if ($exception instanceof mysqli_sql_exception) {
        $saveErrorStatus = 500;
        if ((int) $exception->getCode() === 1062) {
            $saveErrorStatus = 409;
            $message = 'Product SKU already exists.';
        }
    }
    failProduct($message, $saveErrorStatus);
}

if (productJsonRequest()) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode(['success' => true, 'message' => $_SESSION['product_success']]);
    exit;
}

header('Location: ./products.php');
exit;
