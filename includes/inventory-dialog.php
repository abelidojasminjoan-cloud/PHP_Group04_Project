<dialog class="inventory-dialog" id="inventoryManageDialog" aria-labelledby="inventoryManageTitle">
<form id="inventoryManageForm" action="<?= BASE_URL ?>/actions/admin/inventory/adjust.php" method="post">
<header class="form-modal-header inventory-dialog-header"><div><h2 id="inventoryManageTitle">Manage Inventory</h2><p id="inventoryManageProduct">Loading product…</p></div><button type="button" class="inventory-dialog-close" data-inventory-close aria-label="Close Manage Inventory">&times;</button></header>
<div class="form-modal-body inventory-dialog-body">
<p class="inventory-dialog-error" id="inventoryManageError" role="alert" hidden></p>
<div class="inventory-dialog-stats"><div><span>CURRENT STOCK</span><strong id="inventoryCurrentStock">—</strong></div><div><span>LOW STOCK THRESHOLD</span><strong id="inventoryCurrentThreshold">—</strong></div><div><span>LAST UPDATED</span><strong id="inventoryLastUpdated">—</strong></div></div>
<input type="hidden" name="csrf_token" value="<?= pvHtml($_SESSION['csrf_token']) ?>"><input type="hidden" name="variant_id" id="inventoryVariantId"><input type="hidden" name="revision" id="inventoryRevision"><input type="hidden" name="request_id" id="inventoryRequestId">
<fieldset id="inventoryManageFields" disabled><div class="inventory-dialog-grid">
<label for="inventoryAdjustmentType">Adjustment Type<select name="type" id="inventoryAdjustmentType"><option value="add">Add to stock</option><option value="remove">Remove from stock</option></select></label>
<label for="inventoryAdjustmentReason">Reason<select name="reason" id="inventoryAdjustmentReason"><option value="restock">Restock</option><option value="cancelled_order">Cancelled Order</option><option value="customer_return">Customer Return</option><option value="damaged_expired">Damaged / Expired</option><option value="inventory_correction">Inventory Correction</option><option value="other">Other</option></select></label>
<label for="inventoryAdjustmentQuantity">Quantity<input type="number" name="quantity" id="inventoryAdjustmentQuantity" placeholder="Enter 0 for threshold only" min="0" max="2147483647" step="1" required></label>
<label for="inventoryLowThreshold"><span>Low Stock Threshold <span class="inventory-required" aria-hidden="true">*</span></span><input type="number" name="threshold" id="inventoryLowThreshold" min="0" max="2147483647" step="1" required></label>
<label class="inventory-dialog-full" for="inventoryAdjustmentNotes">Notes<textarea name="notes" id="inventoryAdjustmentNotes" maxlength="2000" rows="4" placeholder="Order number, return condition, damage details, or other context"></textarea></label>
</div></fieldset>
<p class="inventory-adjustment-help" id="inventoryAdjustmentHelp" hidden></p>
<p class="inventory-stock-preview" aria-live="polite" hidden>New stock total: <strong id="inventoryNewStock">—</strong></p>
</div>
<footer class="form-modal-footer inventory-dialog-footer"><button type="button" class="form-cancel-button inventory-dialog-cancel" data-inventory-close>Cancel</button><button class="form-submit-button inventory-dialog-save" id="inventoryManageSave" type="submit" disabled>Save Changes</button></footer>
</form></dialog>
