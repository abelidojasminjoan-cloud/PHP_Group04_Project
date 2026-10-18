<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/public/index.php');
    exit;
}

function e(mixed $value): string
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

$userId = (int) $_SESSION['user_id'];

/* AUTHORIZED CUSTOMER */
$stmt = $conn->prepare("
    SELECT first_name, last_name, email, phone
    FROM users
    WHERE id = ?
      AND role_id = 3
      AND status = 'active'
    LIMIT 1
");
$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    http_response_code(403);
    exit('Access denied.');
}

$fullName = trim(
    $user['first_name'] . ' ' . $user['last_name']
);

$initials = strtoupper(
    mb_substr($user['first_name'], 0, 1) .
    mb_substr($user['last_name'], 0, 1)
);

/* LOAD CUSTOMER ADDRESSES */
$stmt = $conn->prepare("
    SELECT *
    FROM user_addresses
    WHERE user_id = ?
    ORDER BY is_default DESC, created_at ASC, id ASC
");
$stmt->bind_param('i', $userId);
$stmt->execute();
$addresses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* CSRF */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* FLASH MESSAGES */
$success = (string) ($_SESSION['address_success'] ?? '');
$error = (string) ($_SESSION['address_error'] ?? '');
$fieldErrors = $_SESSION['address_field_errors'] ?? [];
$oldAddress = $_SESSION['address_old'] ?? [];
unset($_SESSION['address_field_errors'], $_SESSION['address_old']);
if ($fieldErrors) $error = '';

unset(
    $_SESSION['address_success'],
    $_SESSION['address_error']
);

/* EDIT / OPEN MODAL */
$editId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
) ?: 0;

$openModal = isset($_GET['add']) || $editId > 0;
$editAddress = null;

if ($editId > 0) {
    foreach ($addresses as $address) {
        if ((int) $address['id'] === $editId) {
            $editAddress = $address;
            break;
        }
    }

    if (!$editAddress) {
        $openModal = false;
        $error = 'Address not found.';
    }
}

/* DEFAULT FORM */
$defaultForm = [
    'id' => 0,
    'recipient_first_name' => $user['first_name'],
    'recipient_last_name' => $user['last_name'],
    'phone' => $user['phone'] ?? '',
    'country' => 'Philippines',
    'province' => '',
    'city' => '',
    'barangay' => '',
    'postal_code' => '',
    'address_line1' => '',
    'address_line2' => '',
    'address_label' => 'Home',
    'is_default' => count($addresses) === 0 ? 1 : 0
];

$initialForm = $editAddress ?? $defaultForm;
if ($oldAddress && (int) ($oldAddress['id'] ?? 0) === (int) ($editAddress['id'] ?? 0)) {
    $initialForm = array_replace($initialForm, $oldAddress);
    $openModal = true;
}

/* DATA FOR EDIT MODAL */
$addressJson = [];

foreach ($addresses as $address) {
    $addressJson[(string) $address['id']] = [
        'id' => (int) $address['id'],
        'recipient_first_name' => $address['recipient_first_name'],
        'recipient_last_name' => $address['recipient_last_name'],
        'phone' => $address['phone'],
        'country' => $address['country'],
        'province' => $address['province'],
        'city' => $address['city'],
        'barangay' => $address['barangay'],
        'postal_code' => $address['postal_code'],
        'address_line1' => $address['address_line1'],
        'address_line2' => $address['address_line2'],
        'address_label' => in_array(
            $address['address_label'],
            ['Home', 'Office'],
            true
        ) ? $address['address_label'] : 'Home',
        'is_default' => (int) $address['is_default']
    ];
}

$jsonFlags = JSON_HEX_TAG |
    JSON_HEX_AMP |
    JSON_HEX_APOS |
    JSON_HEX_QUOT;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>Delivery Addresses | PureVia</title>

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
        href="<?= BASE_URL ?>/assets/css/header.css"
    >

    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/search.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/assets/css/account.css"
    >

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/assets/css/addresses-modal.css"
    >
    <!-- Responsive adjustments -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/responsive.css">
</head>

<body>

<?php require __DIR__ . '/../includes/header.php'; ?>

<main class="account-page pv-address-page">

    <div class="pv-account-intro">
        <h1>My Account</h1>
        <p>Manage your profile, addresses, and preferences</p>
    </div>

    <div class="account-layout">

        <!-- ACCOUNT SIDEBAR -->
        <aside class="account-sidebar">

            <div class="account-user">
                <div class="account-user-avatar">
                    <?= e($initials) ?>
                </div>

                <h3><?= e($fullName) ?></h3>
                <p><?= e($user['email']) ?></p>
                <span class="account-role">CUSTOMER</span>
            </div>

            <div class="account-sidebar-divider"></div>

            <nav class="account-sidebar-nav">

                <a
                    href="<?= BASE_URL ?>/account/profile.php"
                    class="account-nav-item"
                >
                    <span>👤</span> Profile
                </a>

                <a
                    href="<?= BASE_URL ?>/account/addresses.php"
                    class="account-nav-item active"
                    aria-current="page"
                >
                    <span>📍</span> Addresses
                </a>

                <a href="<?= BASE_URL ?>/account/payment-cards.php"
                   class="account-nav-item">
                    <span aria-hidden="true">&#9635;</span> Payment Cards
                </a>

                <a href="<?= BASE_URL ?>/account/my-reviews.php"
                   class="account-nav-item">
                    <span aria-hidden="true">&#9734;</span> My Reviews
                </a>

                <a
                    href="<?= BASE_URL ?>/account/skin-profile.php"
                    class="account-nav-item"
                >
                    <span>✨</span> Skin Profile
                </a>

                <a
                    href="<?= BASE_URL ?>/account/security.php"
                    class="account-nav-item"
                >
                    <span>🔒</span> Security
                </a>

                <div class="account-sidebar-divider"></div>

                <a
                    href="<?= BASE_URL ?>/public/my-orders.php"
                    class="account-nav-item"
                >
                    <span>📦</span> My Orders
                </a>

                <a
                    href="<?= BASE_URL ?>/public/shop.php"
                    class="account-nav-item"
                >
                    <span>🛒</span> Shop
                </a>

            </nav>
        </aside>

        <!-- ADDRESSES CONTENT -->
        <section class="account-content">

            <div class="account-content-header">
                <h2>Delivery Addresses</h2>

                <button
                    type="button"
                    class="account-edit-btn address-add-btn"
                    id="openAddressModal"
                >
                    + Add Address
                </button>
            </div>

            <?php if ($success !== ''): ?>
                <div class="account-alert success" role="status">
                    <?= e($success) ?>
                </div>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <div class="account-form-error" role="alert">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <div class="address-list">

                <?php if (!$addresses): ?>
                    <div class="address-empty">
                        <div class="address-empty-icon">📍</div>

                        <h3>No delivery addresses yet</h3>

                        <p>
                            Add your first address for faster checkout.
                        </p>

                        <button
                            type="button"
                            class="account-save-btn"
                            data-address-add
                        >
                            + Add Address
                        </button>
                    </div>
                <?php endif; ?>

                <?php foreach ($addresses as $address): ?>
                    <?php
                    $id = (int) $address['id'];
                    $isDefault = (int) $address['is_default'] === 1;

                    $label = in_array(
                        $address['address_label'],
                        ['Home', 'Office'],
                        true
                    ) ? $address['address_label'] : 'Home';
                    ?>

                    <article class="address-card">

                        <div class="address-card-content">

                            <div class="address-card-heading">
                                <h3><?= e($label) ?></h3>

                                <?php if ($isDefault): ?>
                                    <span class="address-default-badge">
                                        DEFAULT
                                    </span>
                                <?php endif; ?>
                            </div>

                            <?php
                                $recipientName = trim(
                                    $address['recipient_first_name'] . ' ' .
                                    $address['recipient_last_name']
                                );

                                $phoneDigits = preg_replace(
                                    '/\D+/',
                                    '',
                                    (string) ($address['phone'] ?? '')
                                );

                                // Accept 09XXXXXXXXX, 9XXXXXXXXX, or 639XXXXXXXXX
                                if (preg_match('/^09\d{9}$/', $phoneDigits)) {
                                    $phoneDigits = substr($phoneDigits, 1);
                                } elseif (preg_match('/^639\d{9}$/', $phoneDigits)) {
                                    $phoneDigits = substr($phoneDigits, 2);
                                }

                                if (preg_match('/^9\d{9}$/', $phoneDigits)) {
                                    $formattedPhone = '(+63) ' .
                                        substr($phoneDigits, 0, 3) . ' ' .
                                        substr($phoneDigits, 3, 3) . ' ' .
                                        substr($phoneDigits, 6, 4);
                                } else {
                                    $formattedPhone = trim((string) ($address['phone'] ?? ''));
                                }
                            ?>

                            <div class="address-recipient">
                                <span class="address-recipient-name">
                                    <?= e($recipientName) ?>
                                </span>

                                <?php if ($formattedPhone !== ''): ?>
                                    <span class="address-recipient-divider"></span>

                                    <span class="address-recipient-phone">
                                        <?= e($formattedPhone) ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <p><?= e($address['address_line1']) ?></p>

                            <?php if (!empty($address['address_line2'])): ?>
                                <p><?= e($address['address_line2']) ?></p>
                            <?php endif; ?>

                            <?php if (!empty($address['barangay'])): ?>
                                <p><?= e($address['barangay']) ?></p>
                            <?php endif; ?>

                            <p>
                                <?= e($address['city']) ?>,
                                <?= e($address['province']) ?>
                                <?= e($address['postal_code']) ?>
                            </p>

                            <p><?= e($address['country']) ?></p>

                        </div>

                        
                        <div class="address-card-actions">

                            <div class="address-actions-main">

                                <!-- EDIT: AVAILABLE FOR ALL ADDRESSES -->
                                <button
                                    type="button"
                                    class="address-edit-link"
                                    data-address-edit="<?= $id ?>"
                                >
                                    Edit
                                </button>

                                <!-- DELETE: AVAILABLE FOR ALL ADDRESSES -->
                                <form novalidate
                                    method="POST"
                                    action="<?= BASE_URL ?>/actions/account/manage-address.php"
                                    data-address-delete-form
                                >
                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= e($_SESSION['csrf_token']) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="delete"
                                    >

                                    <input
                                        type="hidden"
                                        name="address_id"
                                        value="<?= $id ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="address-delete-btn"
                                    >
                                        Delete
                                    </button>
                                </form>

                            </div>

                            <!-- BOTTOM-RIGHT: SET AS DEFAULT -->
                            <?php if (!$isDefault): ?>

                                <form novalidate
                                    method="POST"
                                    action="<?= BASE_URL ?>/actions/account/manage-address.php"
                                    class="address-set-default-form"
                                >
                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= e($_SESSION['csrf_token']) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="set_default"
                                    >

                                    <input
                                        type="hidden"
                                        name="address_id"
                                        value="<?= $id ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="address-default-btn"
                                    >
                                        Set as Default
                                    </button>
                                </form>

                            <?php endif; ?>

                        </div>

                    </article>
                <?php endforeach; ?>

            </div>
        </section>
    </div>
</main>

<!-- ADD / EDIT ADDRESS MODAL -->
<div
    class="pv-address-overlay"
    id="addressModal"
    role="presentation"
    hidden
>
    <section
        class="pv-address-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="addressModalTitle"
    >

        <header class="pv-modal-header">

            <div>
                <h2 id="addressModalTitle">New Address</h2>

                <p id="addressModalSubtitle">
                    Add a delivery address for faster checkout
                </p>
            </div>

            <button
                type="button"
                class="pv-modal-close"
                id="closeAddressModal"
                aria-label="Close address form"
            >
                &times;
            </button>

        </header>

        <form novalidate
            id="addressModalForm"
            method="POST"
            action="<?= BASE_URL ?>/actions/account/manage-address.php"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e($_SESSION['csrf_token']) ?>"
            >

            <input
                type="hidden"
                name="action"
                id="addressAction"
                value="create"
            >

            <input
                type="hidden"
                name="address_id"
                id="addressId"
                value="0"
            >

            <div class="pv-modal-body">

                <div class="pv-address-form-grid">

                    <!-- FIRST NAME -->
                    <div class="pv-field">
                        <label for="recipient_first_name">
                            First Name
                        </label>

                        <input
                            type="text"
                            id="recipient_first_name"
                            name="recipient_first_name"
                            maxlength="100"
                            aria-required="true"
                        >
                    </div>

                    <!-- LAST NAME -->
                    <div class="pv-field">
                        <label for="recipient_last_name">
                            Last Name
                        </label>

                        <input
                            type="text"
                            id="recipient_last_name"
                            name="recipient_last_name"
                            maxlength="100"
                            aria-required="true"
                        >
                    </div>

                    <!-- PHONE -->
                    <div class="pv-field pv-field-full">
                        <label for="addressPhone">
                            Phone Number
                        </label>

                        <input
                            type="tel"
                            id="addressPhone"
                            name="phone"
                            maxlength="30"
                            placeholder="+63 9XX XXX XXXX"
                        >
                    </div>

                    <!-- COUNTRY -->
                    <div class="pv-field pv-field-full">
                        <label for="addressCountry">
                            Country
                        </label>

                        <select
                            id="addressCountry"
                            name="country"
                            aria-required="true"
                        >
                            <option value="Philippines">
                                Philippines
                            </option>
                        </select>
                    </div>

                    <!-- REGION -->
                    <div class="pv-field">
                        <label for="addressRegion">
                            Region
                        </label>

                        <select id="addressRegion" aria-required="true">
                            <option value="">
                                Select region
                            </option>
                        </select>
                    </div>

                    <!-- PROVINCE -->
                    <div class="pv-field">
                        <label for="addressProvince">
                            Province
                        </label>

                        <select
                            id="addressProvince"
                            name="province"
                            aria-required="true"
                            disabled
                        >
                            <option value="">
                                Select province
                            </option>
                        </select>
                    </div>

                    <!-- CITY -->
                    <div class="pv-field">
                        <label for="addressCity">
                            City / Municipality
                        </label>

                        <select
                            id="addressCity"
                            name="city"
                            aria-required="true"
                            disabled
                        >
                            <option value="">
                                Select city
                            </option>
                        </select>
                    </div>

                    <!-- BARANGAY -->
                    <div class="pv-field">
                        <label for="addressBarangay">
                            Barangay
                        </label>

                        <select
                            id="addressBarangay"
                            name="barangay"
                            disabled
                        >
                            <option value="">
                                Select barangay
                            </option>
                        </select>
                    </div>

                    <!-- POSTAL CODE -->
                    <div class="pv-field pv-field-full">
                        <label for="addressPostalCode">
                            Postal Code
                        </label>

                        <input
                            type="text"
                            id="addressPostalCode"
                            name="postal_code"
                            maxlength="20"
                            inputmode="numeric"
                            placeholder="e.g. 1200"
                            aria-required="true"
                        >
                    </div>

                    <!-- FIXED SCROLLABLE TEXTAREA -->
                    <div class="pv-field pv-field-full">
                        <label for="addressLine1">
                            Complete Address
                        </label>

                        <textarea
                            id="addressLine1"
                            class="pv-complete-address"
                            name="address_line1"
                            maxlength="255"
                            rows="3"
                            placeholder="Street name, building, house or unit number"
                            aria-required="true"
                        ></textarea>
                    </div>

                    <!-- ADDITIONAL DETAILS -->
                    <div class="pv-field pv-field-full">
                        <label for="addressLine2">
                            Additional Address Details
                            <span class="pv-optional">
                                (Optional)
                            </span>
                        </label>

                        <input
                            type="text"
                            id="addressLine2"
                            name="address_line2"
                            maxlength="255"
                            placeholder="Floor, landmark, or delivery instructions"
                        >
                    </div>

                    <!-- LOCATION -->
                    <div class="pv-field pv-field-full">

                        <div class="pv-location-box">

                            <div class="pv-location-icon">
                                ⌖
                            </div>

                            <div class="pv-location-copy">
                                <strong>Add a location pin</strong>

                                <span>
                                    Help your rider find the address more easily.
                                </span>
                            </div>

                            <button
                                type="button"
                                id="addressLocationButton"
                                class="pv-location-button"
                            >
                                Add Location
                            </button>

                        </div>
                    </div>

                    <!-- HOME / OFFICE / DEFAULT -->
                    <div class="pv-field pv-field-full pv-label-section">

                        <label>Label as</label>

                        <div class="pv-label-row">

                            <div class="pv-address-labels">

                                <label class="pv-label-choice">
                                    <input
                                        type="radio"
                                        name="address_label"
                                        value="Home"
                                        checked
                                        aria-required="true"
                                    >
                                    <span>Home</span>
                                </label>

                                <label class="pv-label-choice">
                                    <input
                                        type="radio"
                                        name="address_label"
                                        value="Office"
                                        aria-required="true"
                                    >
                                    <span>Office</span>
                                </label>

                            </div>

                            <label class="pv-default-choice">
                                <input
                                    type="checkbox"
                                    id="addressIsDefault"
                                    name="is_default"
                                    value="1"
                                >
                                <span>
                                    Set as default address
                                </span>
                            </label>

                        </div>

                        <p
                            id="addressDefaultHint"
                            class="pv-default-hint"
                            hidden
                        >
                            Your first address automatically becomes the default.
                        </p>

                    </div>

                </div>
            </div>

            <!-- MODAL FOOTER -->
            <footer class="pv-modal-footer">

                <button
                    type="button"
                    class="pv-modal-cancel"
                    id="cancelAddressModal"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="pv-modal-save"
                    id="saveAddressButton"
                >
                    Save Address
                </button>

            </footer>

        </form>
    </section>
</div>

<script>
window.PUREVIA_ADDRESS_CONFIG = <?= json_encode([
    'baseUrl' => BASE_URL,
    'addresses' => $addressJson,
    'defaultForm' => $defaultForm,
    'initialForm' => $initialForm,
    'openModal' => $openModal,
    'isEdit' => $editAddress !== null,
    'fieldErrors' => $fieldErrors
], $jsonFlags) ?>;
</script>

<script src="<?= BASE_URL ?>/assets/js/account.js"></script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script src="<?= BASE_URL ?>/assets/js/search.js"></script>
<script src="<?= BASE_URL ?>/assets/js/addresses-modal.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
