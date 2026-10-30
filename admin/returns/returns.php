<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/role-check.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../services/ReturnManagement.php';
requirePureViaAdmin($conn);
$csrfToken=csrfToken();$pageTitle='Returns';
$returns=ReturnManagement::rows($conn,'SELECT r.id,r.order_id,r.reason,r.resolution,r.status,r.created_at,o.order_number,o.first_name,o.last_name,SUM(ri.quantity) AS quantity,SUM(ri.quantity*i.unit_price) AS amount,GROUP_CONCAT(i.product_name ORDER BY i.id SEPARATOR ", ") AS items FROM order_return_requests r JOIN orders o ON o.id=r.order_id JOIN order_return_items ri ON ri.return_request_id=r.id JOIN order_items i ON i.id=ri.order_item_id GROUP BY r.id,r.order_id,r.reason,r.resolution,r.status,r.created_at,o.order_number,o.first_name,o.last_name ORDER BY r.created_at DESC,r.id DESC');
$counts=['pending'=>0,'approved'=>0,'completed'=>0];foreach($returns as $return){if(isset($counts[$return['status']]))$counts[$return['status']]++;}
function retEsc($text):string{return pvHtml((string)($text??''));}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Returns | PureVia</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&family=Source+Serif+4:wght@500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/sidemenu.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/inventory.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/responsive.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/form2.css?v=<?= filemtime(__DIR__.'/../../assets/css/form2.css') ?>">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin-returns.css?v=<?= filemtime(__DIR__.'/../../assets/css/admin-returns.css') ?>"></head><body>
<?php include __DIR__.'/../../includes/sidemenu.php'; ?>
<div class="admin-layout"><header class="admin-topbar"><p class="admin-topbar-title">Returns</p></header><main class="inventory-main returns-main">
    <section class="inventory-page-header">
        <div class="inventory-heading"><h1>Returns</h1><p><?= number_format(count($returns)) ?> records</p></div>
    </section>
    <section class="inventory-card">
        <div class="inventory-toolbar">
            <div class="inventory-search"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><input type="search" id="returnsSearch" placeholder="Search returns..." aria-label="Search returns by return, order, customer or product" autocomplete="off"></div>
            <div class="inventory-filter-dropdown"><i class="fa-solid fa-filter inventory-filter-icon" aria-hidden="true"></i><select id="returnsFilter" class="inventory-filter-select" aria-label="Filter returns by status"><option value="">All Statuses</option><option value="pending">Pending Review</option><option value="approved">In Progress</option><option value="rejected">Rejected</option><option value="completed">Completed</option></select><i class="fa-solid fa-chevron-down inventory-filter-arrow" aria-hidden="true"></i></div>
        </div>
        <div class="inventory-table-wrapper"><table class="inventory-table returns-table"><thead><tr><th>RETURN ID</th><th>ORDER</th><th>CUSTOMER</th><th>ITEMS</th><th>REASON</th><th>RESOLUTION</th><th>AMOUNT</th><th>STATUS</th><th>ACTIONS</th></tr></thead><tbody>
<?php foreach($returns as $return): ?><tr class="inventory-row" data-return-row data-status="<?= retEsc($return['status']) ?>" data-search="<?= retEsc(strtolower('RET-'.$return['id'].' '.$return['order_number'].' '.$return['first_name'].' '.$return['last_name'].' '.$return['items'].' '.$return['reason'])) ?>"><td>RET-<?= (int)$return['id'] ?></td><td><?= retEsc($return['order_number']) ?></td><td><?= retEsc($return['first_name'].' '.$return['last_name']) ?></td><td><strong><?= retEsc($return['items']) ?></strong><small><?= (int)$return['quantity'] ?> units</small></td><td><?= retEsc($return['reason']) ?></td><td><span class="return-tag"><?= retEsc(ucfirst($return['resolution'])) ?></span></td><td>&#8369;<?= number_format((float)$return['amount'],2) ?></td><td><span class="return-tag return-tag-<?= retEsc($return['status']) ?>"><?= retEsc(ucfirst($return['status'])) ?></span></td><td><button type="button" class="return-manage" data-manage-return="<?= (int)$return['id'] ?>">Manage</button></td></tr><?php endforeach; ?>
<tr id="returnsEmpty" <?= $returns?'hidden':'' ?>><td colspan="9"><div class="returns-empty-message" role="status"><i class="fa-regular fa-folder-open" aria-hidden="true"></i><p>No return requests found.</p></div></td></tr>
</tbody></table></div></section></main></div>
<dialog class="return-dialog return-admin-dialog" id="manageReturnDialog" aria-labelledby="manageReturnTitle"><form id="manageReturnForm" class="purevia-form" action="<?= BASE_URL ?>/actions/admin/returns/manage.php" method="post" data-base-url="<?= retEsc(BASE_URL) ?>">
<header class="form-modal-header"><div><h2 id="manageReturnTitle">Manage Return Request</h2><p id="manageReturnNumber"></p></div><button type="button" class="form-modal-close" data-return-close aria-label="Close manage return">&times;</button></header>
<div class="form-modal-body"><p id="manageReturnMessage" class="rt-error" role="status" tabindex="-1" hidden></p><div id="manageReturnFields">
<input type="hidden" name="csrf_token" value="<?= retEsc($csrfToken) ?>"><input type="hidden" name="id" id="manageReturnId"><input type="hidden" name="revision" id="manageReturnRevision">
<dl class="return-details-grid"><div><dt>Reason</dt><dd id="manageReturnReason"></dd></div><div><dt>Requested</dt><dd id="manageReturnDate"></dd></div><div><dt>Resolution</dt><dd id="manageReturnResolution"></dd></div><div><dt>Return Method</dt><dd id="manageReturnMethod"></dd></div><div class="return-details-wide"><dt>Customer Details</dt><dd id="manageReturnDetails"></dd></div></dl>
<h3>Evidence</h3><div id="manageReturnEvidence" class="return-evidence"></div>
<div class="form-grid form-grid-2"><div class="form-group"><label for="manageReturnStatus">Return Status <span class="form-required">*</span></label><select id="manageReturnStatus" name="status" class="form-control" required><option value="pending">Pending Review</option><option value="approved">Approved / In Progress</option><option value="rejected">Rejected</option><option value="completed">Completed</option></select></div><div class="form-group"><label for="manageReturnNote">Internal Note <span class="form-required">*</span></label><textarea class="form-control form-textarea" id="manageReturnNote" name="internal_note" rows="3" maxlength="2000" required placeholder="Add review notes or processing details"></textarea></div></div>
<section id="manageReturnCompletion" hidden><div class="form-group"><label for="manageReturnReference">Refund transaction or replacement shipment reference <span class="form-required">*</span></label><input id="manageReturnReference" class="form-control" name="resolution_reference" maxlength="200"></div><label class="return-check"><input type="checkbox" name="confirm_resolution" value="1">I confirm the refund or replacement has already been fulfilled.</label><p class="return-note">Saving a return does not issue a PayPal refund, send a replacement, or schedule a courier. Record the reference after completing those actions.</p><h3>Received stock</h3><p>Restock only unopened, undamaged, inspected items. Leave damaged or opened items as “Do not restock”. Choose the exact size received.</p><div id="manageReturnStock"></div><label class="return-check"><input type="checkbox" name="confirm_inspected" value="1">The items selected for restocking have been received and inspected as sellable.</label></section>
<p class="return-note" id="manageReturnReadOnly" hidden>This request is closed and read-only.</p>
</div></div><footer class="form-modal-footer"><button type="button" class="form-cancel-button" data-return-close>Cancel</button><button type="submit" class="form-submit-button" id="manageReturnSave">Save Changes</button></footer></form></dialog>
<script nonce="<?= pvScriptNonce() ?>" src="<?= BASE_URL ?>/assets/js/sidemenu.js"></script><script nonce="<?= pvScriptNonce() ?>" src="<?= BASE_URL ?>/assets/js/admin-returns.js"></script></body></html>