<?php
require __DIR__.'/../skin/_boot.php';
$pageTitle='Skin Type Management';
$types=$conn->query('SELECT t.*,(SELECT COUNT(*) FROM product_skin_types p WHERE p.skin_type_id=t.id) AS product_count FROM skin_types t ORDER BY t.id')->fetch_all(MYSQLI_ASSOC);
$saConfig=['types'=>$types];
$saStyles=['concerns','skin-types'];
$saMainClass='skin-concerns-main sa-types-page';
require __DIR__.'/../skin/_header.php';
?>
<section class="skin-concerns-page-header">
    <div class="skin-concerns-heading"><h1>Skin Types</h1><p><?= count($types) ?> records</p></div>
    <button type="button" class="add-concern-button" data-sa-type="0" aria-haspopup="dialog" aria-controls="saModal"><span aria-hidden="true">+</span> Add Skin Type</button>
</section>
<section class="skin-concerns-card">
    <div class="skin-concerns-toolbar">
        <div class="skin-concerns-search">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="10.5" cy="10.5" r="7"/><path d="m16 16 5 5"/></svg>
            <input type="search" id="saTypeSearch" placeholder="Search skin types..." aria-label="Search skin types" autocomplete="off">
        </div>
        <div class="skin-concern-filter-dropdown">
            <svg class="skin-concern-filter-icon" width="15" height="15" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M3 4h18v3l-7 8v5l-4-2v-3L3 7Z"/></svg>
            <select id="saTypeStatus" class="skin-concern-filter-select" aria-label="Filter skin types by status"><option value="">All Status</option><option value="active">Active</option><option value="inactive">Inactive</option></select>
            <svg class="skin-concern-filter-arrow" width="12" height="12" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="m3 5 5 5 5-5"/></svg>
        </div>
    </div>
    <div class="skin-concerns-table-wrapper">
        <table class="skin-concerns-table"><thead><tr><th>NAME</th><th>DESCRIPTION</th><th>STATUS</th><th>ACTIONS</th></tr></thead><tbody id="saTypeRows">
        <?php foreach($types as $type):?>
            <tr class="skin-concern-row" data-name="<?= saEsc(mb_strtolower($type['skin_type_name'])) ?>" data-status="<?= saEsc($type['status']) ?>">
                <td><span class="skin-concern-name"><?= saEsc($type['skin_type_name']) ?></span></td>
                <td><span class="skin-concern-description"><?= saEsc($type['description']?:'—') ?></span></td>
                <td><span class="skin-concern-status status-<?= saEsc($type['status']) ?>"><?= saEsc(strtoupper($type['status'])) ?></span></td>
                <td><div class="skin-concern-actions">
                    <button type="button" class="skin-concern-action-button edit-concern-button" data-sa-type="<?= (int)$type['id'] ?>" aria-label="Edit <?= saEsc($type['skin_type_name']) ?>" title="Edit skin type" aria-haspopup="dialog" aria-controls="saModal"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5H5a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2h13a2 2 0 0 0 2-2v-7M16 3l5 5M10 14l-1 4 4-1L22 8a2 2 0 0 0-5-5Z"/></svg></button>
                    <button type="button" class="skin-concern-action-button delete-concern-button" data-sa-delete="<?= (int)$type['id'] ?>" aria-label="Delete <?= saEsc($type['skin_type_name']) ?>" title="Delete skin type" aria-haspopup="dialog" aria-controls="saModal"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7m4-7v7"/></svg></button>
                </div></td>
            </tr>
        <?php endforeach; ?>
        <tr id="saTypeEmpty" class="no-skin-concerns-row" hidden><td colspan="4"><div class="no-skin-concerns-message"><p>No skin types match your search.</p></div></td></tr>
        </tbody></table>
    </div>
</section>
<?php require __DIR__.'/../skin/_footer.php'; ?>
