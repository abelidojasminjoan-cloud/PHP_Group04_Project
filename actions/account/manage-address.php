<?php

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

if (empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$action = $_POST['action'] ?? '';
$addressId = filter_var(
    $_POST['address_id'] ?? 0,
    FILTER_VALIDATE_INT
) ?: 0;

$listUrl = BASE_URL . '/account/addresses.php';
$formUrl = BASE_URL . '/account/address_form.php';

if ($action === 'update' && $addressId > 0) {
    $formUrl .= '?id=' . $addressId;
}

function addressFail(
    string $message,
    string $redirect
): never {
    $_SESSION['address_error'] = $message;
    header('Location: ' . $redirect);
    exit;
}

/* INITIALIZE VARIABLES */

$data = [];
$isDefault = 0;
$existing = null;
$message = '';
$addressLabel = 'Home';
$auditRecordId = (string) $addressId;

/* CSRF PROTECTION */

$submittedToken = $_POST['csrf_token'] ?? '';

if (
    !is_string($submittedToken) ||
    !isset($_SESSION['csrf_token']) ||
    !is_string($_SESSION['csrf_token']) ||
    !hash_equals(
        $_SESSION['csrf_token'],
        $submittedToken
    )
) {
    http_response_code(403);
    exit('Invalid security token.');
}

/* VALIDATE ACTION */

$allowedActions = [
    'create',
    'update',
    'delete',
    'set_default'
];

if (
    !is_string($action) ||
    !in_array($action, $allowedActions, true)
) {
    addressFail('Invalid address action.', $listUrl);
}

/* VALIDATE ADDRESS DATA */

if ($action === 'create' || $action === 'update') {

    $fields = [
        'recipient_first_name' => 100,
        'recipient_last_name' => 100,
        'phone' => 30,
        'address_line1' => 255,
        'address_line2' => 255,
        'barangay' => 100,
        'city' => 100,
        'province' => 100,
        'postal_code' => 20,
        'country' => 100
    ];

    $fieldErrors = [];
    foreach ($fields as $field => $maxLength) {
        $value = $_POST[$field] ?? '';
        if (!is_string($value)) {
            $fieldErrors[$field] = 'Invalid form value.';
            $value = '';
        }
        $data[$field] = trim($value);
        if (mb_strlen($data[$field]) > $maxLength) {
            $fieldErrors[$field] = 'Use no more than ' . $maxLength . ' characters.';
        }
    }
    $requiredFields = [
        'recipient_first_name' => 'first name', 'recipient_last_name' => 'last name',
        'address_line1' => 'street address', 'city' => 'city', 'province' => 'province',
        'postal_code' => 'postal code', 'country' => 'country'
    ];
    foreach ($requiredFields as $field => $label) {
        if ($data[$field] === '') $fieldErrors[$field] = 'Please enter your ' . $label . '.';
    }
    if ($data['phone'] !== '' && !preg_match('/^[0-9+\\s()\\-]+$/', $data['phone'])) {
        $fieldErrors['phone'] = 'Please enter a valid phone number.';
    }
    $addressLabel = $_POST['address_label'] ?? 'Home';
    if (!is_string($addressLabel) || !in_array($addressLabel, ['Home','Office'], true)) {
        $fieldErrors['address_label'] = 'Please select Home or Office.';
        $addressLabel = 'Home';
    }
    if ($fieldErrors) {
        $_SESSION['address_field_errors'] = $fieldErrors;
        $_SESSION['address_old'] = $data + [
            'id' => $action === 'update' ? $addressId : 0,
            'address_label' => $addressLabel,
            'is_default' => ($_POST['is_default'] ?? null) === '1' ? 1 : 0
        ];
        addressFail('Please check the highlighted fields.', $formUrl);
    }

    $isDefault = (
        ($_POST['is_default'] ?? null) === '1'
    ) ? 1 : 0;
}

/* DATABASE OPERATIONS */

$transactionStarted = false;

try {

    $conn->begin_transaction();
    $transactionStarted = true;

    /* LOCK AND AUTHORIZE CUSTOMER */

    $stmt = $conn->prepare("
        SELECT id
        FROM users
        WHERE id = ?
          AND role_id = 3
          AND status = 'active'
        LIMIT 1
        FOR UPDATE
    ");

    $stmt->bind_param('i', $userId);
    $stmt->execute();

    $authorized = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$authorized) {
        throw new RuntimeException(
            'Customer account unavailable.'
        );
    }

    /* CHECK ADDRESS OWNERSHIP */

    if ($action !== 'create') {

        if ($addressId <= 0) {
            throw new RuntimeException(
                'Invalid address ID.'
            );
        }

        $stmt = $conn->prepare("
            SELECT id, is_default
            FROM user_addresses
            WHERE id = ? AND user_id = ?
            LIMIT 1
            FOR UPDATE
        ");

        $stmt->bind_param(
            'ii',
            $addressId,
            $userId
        );

        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$existing) {
            throw new RuntimeException(
                'Address not found.'
            );
        }
    }

    /* CREATE ADDRESS */

    if ($action === 'create') {

        $stmt = $conn->prepare("
            SELECT COUNT(*) AS total
            FROM user_addresses
            WHERE user_id = ?
        ");

        $stmt->bind_param('i', $userId);
        $stmt->execute();

        $addressCount = (int) $stmt
            ->get_result()
            ->fetch_assoc()['total'];

        $stmt->close();

        if ($addressCount === 0) {
            $isDefault = 1;
        }

        if ($isDefault === 1) {

            $stmt = $conn->prepare("
                UPDATE user_addresses
                SET is_default = 0
                WHERE user_id = ?
            ");

            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $stmt->close();
        }

        $stmt = $conn->prepare("
            INSERT INTO user_addresses (
                user_id,
                address_label,
                recipient_first_name,
                recipient_last_name,
                phone,
                address_line1,
                address_line2,
                barangay,
                city,
                province,
                postal_code,
                country,
                is_default
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            'isssssssssssi',
            $userId,
            $addressLabel,
            $data['recipient_first_name'],
            $data['recipient_last_name'],
            $data['phone'],
            $data['address_line1'],
            $data['address_line2'],
            $data['barangay'],
            $data['city'],
            $data['province'],
            $data['postal_code'],
            $data['country'],
            $isDefault
        );

        $stmt->execute();

        $auditRecordId = (string) $conn->insert_id;

        $stmt->close();

        $message = 'Address added successfully.';
    }

    /* UPDATE ADDRESS */

    elseif ($action === 'update') {

        if ((int) $existing['is_default'] === 1) {
            $isDefault = 1;
        }

        if ($isDefault === 1) {

            $stmt = $conn->prepare("
                UPDATE user_addresses
                SET is_default = 0
                WHERE user_id = ?
            ");

            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $stmt->close();
        }

        $stmt = $conn->prepare("
            UPDATE user_addresses
            SET
                address_label = ?,
                recipient_first_name = ?,
                recipient_last_name = ?,
                phone = ?,
                address_line1 = ?,
                address_line2 = ?,
                barangay = ?,
                city = ?,
                province = ?,
                postal_code = ?,
                country = ?,
                is_default = ?
            WHERE id = ?
              AND user_id = ?
        ");

        $stmt->bind_param(
            'sssssssssssiii',
            $addressLabel,
            $data['recipient_first_name'],
            $data['recipient_last_name'],
            $data['phone'],
            $data['address_line1'],
            $data['address_line2'],
            $data['barangay'],
            $data['city'],
            $data['province'],
            $data['postal_code'],
            $data['country'],
            $isDefault,
            $addressId,
            $userId
        );

        $stmt->execute();
        $stmt->close();

        $message = 'Address updated successfully.';
    }

    
    /* DELETE ADDRESS */

    elseif ($action === 'delete') {

        $wasDefault = (
            (int) $existing['is_default'] === 1
        );

        /* DELETE CUSTOMER'S OWN ADDRESS */

        $stmt = $conn->prepare("
            DELETE FROM user_addresses
            WHERE id = ?
              AND user_id = ?
        ");

        $stmt->bind_param(
            'ii',
            $addressId,
            $userId
        );

        $stmt->execute();
        $stmt->close();

        /*
         * IF DEFAULT ADDRESS WAS DELETED,
         * AUTOMATICALLY ASSIGN ANOTHER ADDRESS.
         */

        if ($wasDefault) {

            $stmt = $conn->prepare("
                SELECT id
                FROM user_addresses
                WHERE user_id = ?
                ORDER BY created_at ASC, id ASC
                LIMIT 1
                FOR UPDATE
            ");

            $stmt->bind_param('i', $userId);
            $stmt->execute();

            $replacement = $stmt
                ->get_result()
                ->fetch_assoc();

            $stmt->close();

            if ($replacement) {

                $replacementId = (int) $replacement['id'];

                $stmt = $conn->prepare("
                    UPDATE user_addresses
                    SET is_default = 1
                    WHERE id = ?
                      AND user_id = ?
                ");

                $stmt->bind_param(
                    'ii',
                    $replacementId,
                    $userId
                );

                $stmt->execute();
                $stmt->close();
            }
        }

        $message = 'Address deleted successfully.';
    }



    /* SET DEFAULT ADDRESS */

    elseif ($action === 'set_default') {

        $stmt = $conn->prepare("
            UPDATE user_addresses
            SET is_default = 0
            WHERE user_id = ?
        ");

        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("
            UPDATE user_addresses
            SET is_default = 1
            WHERE id = ? AND user_id = ?
        ");

        $stmt->bind_param(
            'ii',
            $addressId,
            $userId
        );

        $stmt->execute();
        $stmt->close();

        $message = 'Default address updated successfully.';
    }

    /* AUDIT LOG */

    $auditActions = [
        'create' => 'CREATE',
        'update' => 'UPDATE',
        'delete' => 'DELETE',
        'set_default' => 'UPDATE'
    ];

    $auditDescriptions = [
        'create' => 'Customer added a delivery address.',
        'update' => 'Customer updated a delivery address.',
        'delete' => 'Customer deleted a delivery address.',
        'set_default' => 'Customer changed the default delivery address.'
    ];

    $auditAction = $auditActions[$action];
    $auditDescription = $auditDescriptions[$action];
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
        VALUES (?, ?, 'Addresses', ?, ?, ?)
    ");

    $stmt->bind_param(
        'issss',
        $userId,
        $auditAction,
        $auditRecordId,
        $auditDescription,
        $ipAddress
    );

    $stmt->execute();
    $stmt->close();

    /* COMMIT */

    $conn->commit();
    $transactionStarted = false;

    $_SESSION['address_success'] = $message;

    header('Location: ' . $listUrl);
    exit;

} catch (Throwable $exception) {

    if ($transactionStarted) {
        $conn->rollback();
    }

    error_log(
        'Address management error: ' .
        $exception->getMessage()
    );

    $errorMessage = $exception instanceof RuntimeException
        ? $exception->getMessage()
        : 'Unable to process address. Please try again.';

    addressFail(
        $errorMessage,
        in_array($action, ['create', 'update'], true)
            ? $formUrl
            : $listUrl
    );
}
