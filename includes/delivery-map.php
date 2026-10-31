<?php
// Shared delivery map. The caller supplies element IDs; no schema change is needed.
$deliveryMapKey = trim((string) ($_ENV['GOOGLE_MAPS_API_KEY'] ?? ''));
$deliveryMapStyle = trim((string) ($_ENV['GOOGLE_MAPS_MAP_ID'] ?? '')) ?: 'DEMO_MAP_ID';
?>
<section class="pv-delivery-map" data-delivery-map
    data-api-key="<?= pvHtml($deliveryMapKey) ?>" data-map-id="<?= pvHtml($deliveryMapStyle) ?>"
    data-notes-id="<?= pvHtml($deliveryMapNotesId) ?>" data-field-ids="<?= pvHtml(implode(',', $deliveryMapFieldIds)) ?>"
    data-current-button="<?= pvHtml($deliveryMapCurrentButton ?? '') ?>" data-find-button="<?= pvHtml($deliveryMapFindButton ?? '') ?>"
    data-link-id="<?= pvHtml($deliveryMapLinkId ?? '') ?>" aria-label="Delivery location map">
    <div class="pv-delivery-map-toolbar"><strong>Choose your delivery pin</strong><div>
        <button type="button" class="pv-map-button" data-map-find>Find address</button>
        <?php if (empty($deliveryMapCurrentButton)): ?><button type="button" class="pv-map-button" data-map-current>Use current location</button><?php endif; ?>
    </div></div>
    <div class="pv-delivery-map-canvas" data-map-canvas aria-label="Google map for choosing a delivery pin"><p data-map-placeholder>Loading delivery map…</p></div>
    <p class="pv-delivery-map-status" data-map-status role="status" aria-live="polite">Click the map or drag the marker, then select Use this pin.</p>
    <div class="pv-delivery-map-footer"><span data-map-coordinates></span><div><button type="button" class="pv-map-button" data-map-clear disabled>Remove pin</button><button type="button" class="pv-map-button pv-map-confirm" data-map-confirm disabled>Use this pin</button></div></div>
    <a class="pv-delivery-map-link" data-map-link target="_blank" rel="noopener noreferrer" hidden>View saved pin on Google Maps</a>
</section>
