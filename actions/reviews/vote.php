
<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json; charset=utf-8');

function voteResponse(int $status, array $data): void
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    voteResponse(405, [
        'success' => false,
        'message' => 'Method not allowed.'
    ]);
}

$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    voteResponse(401, [
        'success' => false,
        'message' => 'Please log in first.'
    ]);
}

$token = $_POST['csrf_token'] ?? '';

if (
    !is_string($token) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $token)
) {
    voteResponse(403, [
        'success' => false,
        'message' => 'Invalid security token.'
    ]);
}

$reviewId = filter_var(
    $_POST['review_id'] ?? null,
    FILTER_VALIDATE_INT
);

$vote = $_POST['vote'] ?? '';

if (
    !$reviewId ||
    !is_string($vote) ||
    !in_array($vote, ['up', 'down'], true)
) {
    voteResponse(422, [
        'success' => false,
        'message' => 'Invalid vote.'
    ]);
}

/* VERIFY ACTIVE CUSTOMER */

$stmt = $conn->prepare("
    SELECT id
    FROM users
    WHERE id = ?
      AND role_id = 3
      AND status = 'active'
    LIMIT 1
");

$stmt->bind_param('i', $userId);
$stmt->execute();

$customer = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$customer) {
    voteResponse(403, [
        'success' => false,
        'message' => 'Customer access required.'
    ]);
}

try {
    $conn->begin_transaction();

    /* LOCK REVIEW */

    $stmt = $conn->prepare("
        SELECT id, user_id
        FROM product_reviews
        WHERE id = ?
          AND status = 'approved'
        FOR UPDATE
    ");

    $stmt->bind_param('i', $reviewId);
    $stmt->execute();

    $review = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$review) {
        throw new RuntimeException('Review not found.');
    }

    if ((int) $review['user_id'] === $userId) {
        throw new RuntimeException('You cannot vote on your own review.');
    }

    /* EXISTING VOTE */

    $stmt = $conn->prepare("
        SELECT id, vote
        FROM review_votes
        WHERE review_id = ? AND user_id = ?
        LIMIT 1
    ");

    $stmt->bind_param('ii', $reviewId, $userId);
    $stmt->execute();

    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $finalVote = $vote;

    if ($existing) {

        if ($existing['vote'] === $vote) {

            /* CLICK SAME VOTE AGAIN TO REMOVE */

            $stmt = $conn->prepare("
                DELETE FROM review_votes
                WHERE id = ?
            ");

            $existingId = (int) $existing['id'];
            $stmt->bind_param('i', $existingId);
            $stmt->execute();
            $stmt->close();

            $finalVote = '';

        } else {

            /* CHANGE VOTE */

            $stmt = $conn->prepare("
                UPDATE review_votes
                SET vote = ?
                WHERE id = ?
            ");

            $existingId = (int) $existing['id'];
            $stmt->bind_param('si', $vote, $existingId);
            $stmt->execute();
            $stmt->close();
        }

    } else {

        /* FIRST VOTE */

        $stmt = $conn->prepare("
            INSERT INTO review_votes (review_id, user_id, vote)
            VALUES (?, ?, ?)
        ");

        $stmt->bind_param('iis', $reviewId, $userId, $vote);
        $stmt->execute();
        $stmt->close();
    }

    /* RECOMPUTE HELPFUL COUNT */

    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM review_votes
        WHERE review_id = ? AND vote = 'up'
    ");

    $stmt->bind_param('i', $reviewId);
    $stmt->execute();

    $helpfulCount = (int) $stmt->get_result()
        ->fetch_assoc()['total'];

    $stmt->close();

    $stmt = $conn->prepare("
        UPDATE product_reviews
        SET helpful_count = ?
        WHERE id = ?
    ");

    $stmt->bind_param('ii', $helpfulCount, $reviewId);
    $stmt->execute();
    $stmt->close();

    $conn->commit();

    voteResponse(200, [
        'success' => true,
        'vote' => $finalVote,
        'helpful_count' => $helpfulCount
    ]);

} catch (Throwable $e) {

    $conn->rollback();

    error_log('Review vote error: ' . $e->getMessage());

    voteResponse(
        $e instanceof RuntimeException ? 400 : 500,
        [
            'success' => false,
            'message' => $e instanceof RuntimeException
                ? $e->getMessage()
                : 'Unable to save your vote.'
        ]
    );
}
