<?php
require __DIR__.'/../skin/_boot.php';
$pageTitle='Suitability Management';
$q=is_string($_GET['q']??null)?mb_substr(trim($_GET['q']),0,150):'';$like='%'.$q.'%';
$status=is_string($_GET['status']??null)&&in_array($_GET['status'],['active','inactive'],true)?$_GET['status']:'';
$categoryId=filter_var($_GET['category']??0,FILTER_VALIDATE_INT,['options'=>['min_range'=>0]])?:0;
$categories=$conn->query("SELECT id,category_name FROM categories WHERE status='active' ORDER BY category_name")->fetch_all(MYSQLI_ASSOC);
$s=$conn->prepare("SELECT t.*,
 (SELECT COUNT(*) FROM product_suitability_tags pt JOIN products p ON p.id=pt.product_id WHERE pt.suitability_tag_id=t.id AND (?=0 OR p.category_id=?)) AS product_count
 FROM suitability_tags t
 WHERE (t.name LIKE ? OR t.description LIKE ? OR EXISTS(SELECT 1 FROM product_suitability_tags pt JOIN products p ON p.id=pt.product_id WHERE pt.suitability_tag_id=t.id AND p.product_name LIKE ?))
 AND (?='' OR t.status=?)
 AND (?=0 OR EXISTS(SELECT 1 FROM product_suitability_tags pt JOIN products p ON p.id=pt.product_id WHERE pt.suitability_tag_id=t.id AND p.category_id=?))
 ORDER BY t.id");
$s->bind_param('iisssssii',$categoryId,$categoryId,$like,$like,$like,$status,$status,$categoryId,$categoryId);$s->execute();$tags=$s->get_result()->fetch_all(MYSQLI_ASSOC);
$saConfig=['tags'=>$tags];$saStyles=['concerns','suitability'];$saMainClass='skin-concerns-main sa-suitability-page';
require __DIR__.'/../skin/_header.php';
?>
<section class="skin-concerns-page-header">
    <div class="skin-concerns-heading"><h1>Suitability Management</h1><p><?= count($tags) ?> records</p></div>
    <button type="button" class="add-concern-button" data-sa-tag="0" aria-haspopup="dialog" aria-controls="saModal"><span aria-hidden="true">+</span> Add Suitability</button>
</section>
<section class="skin-concerns-card">
    <form class="skin-concerns-toolbar" id="suTagFilters" method="get">
        <div class="skin-concerns-search">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="10.5" cy="10.5" r="7"/><path d="m16 16 5 5"/></svg>
            <input type="search" id="suTagSearch" name="q" value="<?= saEsc($q) ?>" placeholder="Search suitability..." aria-label="Search suitability or assigned products">
        </div>
        <div class="skin-concern-filter-dropdown">
            <svg class="skin-concern-filter-icon" width="15" height="15" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M3 4h18v3l-7 8v5l-4-2v-3L3 7Z"/></svg>
            <select id="suCategory" name="category" class="skin-concern-filter-select" aria-label="Filter by assigned product category" title="Categories of products assigned to each suitability tag">
                <option value="0">All Categories</option>
                <?php foreach($categories as $category):?><option value="<?= (int)$category['id'] ?>" <?= $categoryId===(int)$category['id']?'selected':'' ?>><?= saEsc($category['category_name']) ?></option><?php endforeach; ?>
            </select>
            <svg class="skin-concern-filter-arrow" width="12" height="12" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="m3 5 5 5 5-5"/></svg>
        </div>
        <div class="skin-concern-filter-dropdown">
            <select id="suTagStatus" name="status" class="skin-concern-filter-select su-status-filter" aria-label="Filter suitability by status">
                <?php foreach([''=>'All Status','active'=>'Active','inactive'=>'Inactive'] as $value=>$label):?><option value="<?= $value ?>" <?= $status===$value?'selected':'' ?>><?= $label ?></option><?php endforeach; ?>
            </select>
            <svg class="skin-concern-filter-arrow" width="12" height="12" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="m3 5 5 5 5-5"/></svg>
        </div>
    </form>
    <div class="skin-concerns-table-wrapper">
        <table class="skin-concerns-table su-tags-table"><thead><tr><th>NAME</th><th>DESCRIPTION</th><th>PRODUCTS</th><th>STATUS</th><th>ACTIONS</th></tr></thead><tbody>
        <?php foreach($tags as $tag):?>
            <tr class="skin-concern-row" data-suitability-id="<?= (int)$tag['id'] ?>">
                <td><span class="skin-concern-name"><?= saEsc($tag['name']) ?></span></td>
                <td><span class="skin-concern-description"><?= saEsc($tag['description']?:'—') ?></span></td>
                <td class="su-product-count"><?= (int)$tag['product_count'] ?></td>
                <td><span class="skin-concern-status status-<?= saEsc($tag['status']) ?>"><?= saEsc(strtoupper($tag['status'])) ?></span></td>
                <td><div class="skin-concern-actions">
                    <button type="button" class="skin-concern-action-button edit-concern-button" data-sa-tag="<?= (int)$tag['id'] ?>" aria-label="Edit <?= saEsc($tag['name']) ?>" title="Edit suitability" aria-haspopup="dialog" aria-controls="saModal"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5H5a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2h13a2 2 0 0 0 2-2v-7M16 3l5 5M10 14l-1 4 4-1L22 8a2 2 0 0 0-5-5Z"/></svg></button>
                    <button type="button" class="skin-concern-action-button delete-concern-button" data-sa-tag-delete="<?= (int)$tag['id'] ?>" aria-label="Delete <?= saEsc($tag['name']) ?>" title="Delete suitability" aria-haspopup="dialog" aria-controls="saModal"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7m4-7v7"/></svg></button>
                </div></td>
            </tr>
        <?php endforeach; ?>
        <?php if(!$tags):?><tr class="no-skin-concerns-row"><td colspan="5"><div class="no-skin-concerns-message"><p>No suitability records match your filters.</p></div></td></tr><?php endif; ?>
        </tbody></table>
    </div>
</section>
<?php require __DIR__.'/../skin/_footer.php'; ?>
