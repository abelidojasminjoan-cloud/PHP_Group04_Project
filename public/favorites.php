<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Favorites | PureVia</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/header.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/search.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/footer.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/responsive.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/favorites.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&amp;family=Source+Serif+4:wght@600&amp;display=swap" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>
<main class="fv-page">
    <div class="fv-container">
        <header class="fv-heading">
            <div><p class="fv-eyebrow">Saved for later</p><h1>My Favorites</h1><p id="fvCount">0 products in your collection</p></div>
            <button type="button" class="fv-clear" id="fvClear" disabled>Clear All</button>
        </header>
        <div class="fv-toolbar" id="fvToolbar">
            <div class="fv-search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="10.5" cy="10.5" r="7"/><path d="m16 16 5 5"/></svg>
                <input type="search" id="fvSearch" placeholder="Search saved products" aria-label="Search saved products">
            </div>
            <label class="fv-sort">Sort by <select id="fvSort"><option value="recent">Recently saved</option><option value="name">Name: A–Z</option><option value="price-low">Price: Low to high</option><option value="price-high">Price: High to low</option></select></label>
        </div>
        <section class="fv-empty" id="fvEmpty" aria-labelledby="fvEmptyTitle" hidden>
            <span class="fv-empty-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z"/></svg></span>
            <h2 id="fvEmptyTitle">Your favorites list is empty</h2>
            <p>Tap the heart on products you love to keep them together here.</p>
            <a class="fv-explore" id="fvExplore" href="<?= BASE_URL ?>/public/shop.php">Explore Products</a>
        </section>
        <div class="fv-grid" id="fvGrid"></div>
        <p class="fv-status" id="fvStatus" role="status">Loading saved products…</p>
    </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
<script>
window.PUREVIA_FAVORITES_PAGE = <?= json_encode([
    'baseUrl' => BASE_URL, 'csrfToken' => $_SESSION['csrf_token'],
    'canAddToBag' => (int) ($_SESSION['user_id'] ?? 0) > 0 && ($_SESSION['role'] ?? '') === 'customer'
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
window.PUREVIA_BASE_URL = <?= json_encode(BASE_URL) ?>;
</script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script src="<?= BASE_URL ?>/assets/js/search.js"></script>
<script src="<?= BASE_URL ?>/assets/js/favorites.js"></script>
<script src="<?= BASE_URL ?>/assets/js/cart_add.js"></script>
</body>
</html>
