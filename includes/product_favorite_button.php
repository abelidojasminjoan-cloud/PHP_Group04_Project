<?php /** @var array{id: int|string, product_name: string} $product */ ?>
<button type="button" class="pv-product-favorite"
        data-favorite-id="<?= (int) $product['id'] ?>"
        data-favorite-name="<?= pvHtml((string) $product['product_name'], ENT_QUOTES, 'UTF-8') ?>"
        data-favorite-scope="<?= pvHtml(BASE_URL . ':' . (int) ($_SESSION['user_id'] ?? 0), ENT_QUOTES, 'UTF-8') ?>"
        aria-pressed="false"
        aria-label="<?= pvHtml('Add ' . $product['product_name'] . ' to favorites', ENT_QUOTES, 'UTF-8') ?>">
    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
        <path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z"/>
    </svg>
</button>
