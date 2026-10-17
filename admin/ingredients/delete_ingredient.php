<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function ingredientDeleteResponse(int $status, array $data): never
{
    http_response_code($status);
    echo json_encode($data, JSON_THROW_ON_ERROR);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    ingredientDeleteResponse(405, ['success' => false, 'message' => 'Method not allowed.']);
}

$deleteTransactionStarted = false;
$linkedIngredientMessage = 'This ingredient is still linked to products or customer preferences. Remove those links before deleting it.';
try {
    $adminId = (int) ($_SESSION['user_id'] ?? 0);
    $stmt = $conn->prepare("
        SELECT u.id FROM users u
        INNER JOIN roles r ON r.id = u.role_id
        WHERE u.id = ? AND u.status = 'active' AND LOWER(r.role_name) = 'admin'
        LIMIT 1
    ");
    $stmt->bind_param('i', $adminId);
    $stmt->execute();
    $allowed = $stmt->get_result()->num_rows === 1;
    $stmt->close();
    if (!$allowed) {
        ingredientDeleteResponse(403, ['success' => false, 'message' => 'Admin access is required. Please sign in again.']);
    }

    $token = $_POST['csrf_token'] ?? null;
    $sessionToken = $_SESSION['csrf_ingredient'] ?? null;
    if (!is_string($token) || !is_string($sessionToken) || $sessionToken === ''
        || !hash_equals($sessionToken, $token)) {
        ingredientDeleteResponse(403, ['success' => false, 'message' => 'Invalid security token. Refresh the page.']);
    }

    $submittedId = $_POST['ingredient_id'] ?? null;
    $ingredientId = is_string($submittedId)
        ? filter_var($submittedId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
        : false;
    if ($ingredientId === false) {
        ingredientDeleteResponse(400, ['success' => false, 'message' => 'Invalid ingredient.']);
    }

    $conn->begin_transaction();
    $deleteTransactionStarted = true;
    $stmt = $conn->prepare('SELECT ingredient_name FROM ingredients WHERE id = ? FOR UPDATE');
    $stmt->bind_param('i', $ingredientId);
    $stmt->execute();
    $ingredient = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$ingredient) {
        $conn->rollback();
        $deleteTransactionStarted = false;
        ingredientDeleteResponse(404, ['success' => false, 'message' => 'This ingredient no longer exists. Refresh the list.']);
    }

    // Locking the ingredient also prevents new foreign-key links during deletion.
    // Preserve product ingredient data and saved customer preferences.
    foreach (['product_ingredients', 'user_avoided_ingredients'] as $linkTable) {
        $stmt = $conn->prepare("SELECT ingredient_id FROM {$linkTable} WHERE ingredient_id = ? LIMIT 1");
        $stmt->bind_param('i', $ingredientId);
        $stmt->execute();
        $hasLinks = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        if ($hasLinks) {
            $conn->rollback();
            $deleteTransactionStarted = false;
            ingredientDeleteResponse(409, ['success' => false, 'message' => $linkedIngredientMessage]);
        }
    }

    $stmt = $conn->prepare('DELETE FROM ingredients WHERE id = ?');
    $stmt->bind_param('i', $ingredientId);
    $stmt->execute();
    $stmt->close();

    $recordId = (string) $ingredientId;
    $description = 'Deleted ingredient "' . $ingredient['ingredient_name'] . '".';
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $stmt = $conn->prepare("
        INSERT INTO audit_logs (user_id, action, module, record_id, description, ip_address)
        VALUES (?, 'DELETE', 'Ingredients', ?, ?, ?)
    ");
    $stmt->bind_param('isss', $adminId, $recordId, $description, $ipAddress);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    $deleteTransactionStarted = false;
    $_SESSION['ingredient_success'] = 'Ingredient deleted successfully.';
    ingredientDeleteResponse(200, ['success' => true, 'message' => $_SESSION['ingredient_success']]);
} catch (Throwable $error) {
    if ($deleteTransactionStarted) {
        $conn->rollback();
    }
    if ($error instanceof mysqli_sql_exception && $error->getCode() === 1451) {
        ingredientDeleteResponse(409, ['success' => false, 'message' => $linkedIngredientMessage]);
    }
    error_log('Delete Ingredient Error: ' . $error->getMessage());
    ingredientDeleteResponse(500, ['success' => false, 'message' => 'Unable to delete this ingredient. Please try again.']);
}
