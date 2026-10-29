<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../services/ProductReview.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
function reviewResponse(int $status, bool $success, string $message): never {
    http_response_code($status);
    echo pvJson(['success' => $success, 'message' => $message]);
    exit;
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') reviewResponse(405, false, 'Method not allowed.');
$userId = (int) ($_SESSION['user_id'] ?? 0);
if ($userId < 1) reviewResponse(401, false, 'Please sign in to submit a review.');
$token = $_POST['csrf_token'] ?? null;
if (!is_string($token) || !is_string($_SESSION['csrf_token'] ?? null) || $_SESSION['csrf_token'] === '' || !hash_equals($_SESSION['csrf_token'], $token)) reviewResponse(403, false, 'Please refresh the page and try again.');
$ids = [];
foreach (['order_id', 'product_id', 'rating'] as $key) {
    $ids[$key] = filter_var($_POST[$key] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$ids[$key]) reviewResponse(422, false, 'Please select a product and a star rating.');
}
$text = $_POST['review_text'] ?? null;
if (!is_string($text)) reviewResponse(422, false, 'Please enter your review.');
$photos = [];
if (isset($_FILES['photos'])) {
    $uploads = $_FILES['photos'];
    if (!is_array($uploads['error'] ?? null) || count($uploads['error']) > 5) reviewResponse(422, false, 'Upload up to 5 photos.');
    foreach ($uploads['error'] as $index => $error) {
        if ($error === UPLOAD_ERR_NO_FILE) continue;
        $photos[] = ['error' => $error, 'tmp_name' => $uploads['tmp_name'][$index] ?? null];
    }
}
try {
    submitProductReview($conn, $userId, $ids['order_id'], $ids['product_id'], $ids['rating'], $text, $photos);
    reviewResponse(200, true, 'Thank you! Your review has been submitted for approval.');
} catch (InvalidArgumentException $error) {
    reviewResponse(422, false, $error->getMessage());
} catch (DomainException $error) {
    reviewResponse(409, false, $error->getMessage());
} catch (Throwable $error) {
    error_log('Review submission failed: ' . $error->getMessage());
    reviewResponse(500, false, 'Your review could not be saved. Please try again.');
}