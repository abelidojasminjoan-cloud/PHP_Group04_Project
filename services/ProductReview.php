<?php
declare(strict_types=1);

/** Save a verified purchase review. User-row locking serializes duplicate submissions. */
function submitProductReview(mysqli $conn, int $userId, int $orderId, int $productId, int $rating, string $text, array $photos = []): int
{
    $text = trim($text);
    if ($rating < 1 || $rating > 5 || $text === '' || !mb_check_encoding($text, 'UTF-8') || mb_strlen($text) > 5000) {
        throw new InvalidArgumentException('Select 1–5 stars and enter a review of up to 5,000 characters.');
    }
    if (count($photos) > 5) throw new InvalidArgumentException('Upload up to 5 photos.');
    $validated = [];
    $mimeTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    foreach ($photos as $photo) {
        if (!is_array($photo) || ($photo['error'] ?? null) !== UPLOAD_ERR_OK || !is_string($photo['tmp_name'] ?? null) || !is_uploaded_file($photo['tmp_name'])) {
            throw new InvalidArgumentException('A photo could not be uploaded. Please select it again.');
        }
        $size = filesize($photo['tmp_name']);
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($photo['tmp_name']);
        $info = @getimagesize($photo['tmp_name']);
        if (!$size || $size > 5 * 1024 * 1024 || !isset($mimeTypes[$mime]) || !$info || ($info['mime'] ?? '') !== $mime || $info[0] * $info[1] > 20000000) {
            throw new InvalidArgumentException('Photos must be JPG, PNG, or WebP, up to 5 MB and 20 megapixels each.');
        }
        $validated[] = ['source' => $photo['tmp_name'], 'name' => bin2hex(random_bytes(20)) . '.' . $mimeTypes[$mime]];
    }
    $savedFiles = [];
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND role_id = 3 AND status = 'active' FOR UPDATE");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) throw new DomainException('An active customer account is required.');
        $stmt->close();
        $stmt = $conn->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ? AND order_status IN ('delivered', 'completed') FOR UPDATE");
        $stmt->bind_param('ii', $orderId, $userId);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) throw new DomainException('You can review products from your delivered orders only.');
        $stmt->close();
        $stmt = $conn->prepare('SELECT oi.id FROM order_items oi INNER JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ? AND oi.product_id = ? LIMIT 1');
        $stmt->bind_param('ii', $orderId, $productId);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) throw new DomainException('This product is not available for review in this order.');
        $stmt->close();
        $stmt = $conn->prepare('SELECT id FROM product_reviews WHERE user_id = ? AND product_id = ? LIMIT 1');
        $stmt->bind_param('ii', $userId, $productId);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) throw new DomainException('You have already submitted a review for this product.');
        $stmt->close();
        $title = 'Customer review';
        $stmt = $conn->prepare("INSERT INTO product_reviews (product_id, user_id, rating, title, review_text, verified_purchase, status) VALUES (?, ?, ?, ?, ?, 1, 'pending')");
        $stmt->bind_param('iiiss', $productId, $userId, $rating, $title, $text);
        $stmt->execute();
        $reviewId = (int) $conn->insert_id;
        $stmt->close();
        if ($validated) {
            $directory = dirname(__DIR__) . '/uploads/reviews';
            if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) throw new RuntimeException('Review upload directory unavailable.');
            $stmt = $conn->prepare('INSERT INTO review_images (review_id, image_path) VALUES (?, ?)');
            foreach ($validated as $photo) {
                $destination = $directory . '/' . $photo['name'];
                if (!move_uploaded_file($photo['source'], $destination)) throw new RuntimeException('Unable to save review photo.');
                $savedFiles[] = $destination;
                $imagePath = 'uploads/reviews/' . $photo['name'];
                $stmt->bind_param('is', $reviewId, $imagePath);
                $stmt->execute();
            }
            $stmt->close();
        }
        $conn->commit();
        return $reviewId;
    } catch (Throwable $error) {
        $conn->rollback();
        foreach ($savedFiles as $file) { if (is_file($file)) unlink($file); }
        throw $error;
    }
}