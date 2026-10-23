<?php // Cancellation-only markup; styles are scoped separately from other forms. ?>
<div class="oc-overlay" id="ocCancelModal" hidden>
    <section class="oc-dialog" role="dialog" aria-modal="true" aria-labelledby="ocCancelTitle" aria-describedby="ocCancelSubtitle" tabindex="-1">
        <header class="oc-header">
            <div>
                <h2 id="ocCancelTitle">Cancel Order</h2>
                <p id="ocCancelSubtitle">Tell us why you’re cancelling <span id="ocOrderNumber"></span></p>
            </div>
            <button type="button" class="oc-close" data-oc-close aria-label="Close cancellation dialog">&times;</button>
        </header>
        <form class="oc-form" id="ocCancelForm" novalidate>
            <div class="oc-body">
                <div class="oc-field">
                    <label for="ocReason">Cancellation Reason <span class="oc-required" aria-hidden="true">*</span></label>
                    <div class="oc-select-wrap">
                        <select id="ocReason" name="reason" aria-required="true" aria-describedby="ocReasonError">
                            <option value="">Select a reason</option>
                            <?php foreach (orderCancellationReasons() as $reason): ?>
                                <option value="<?= pvHtml($reason, ENT_QUOTES, 'UTF-8') ?>"><?= pvHtml($reason, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <p class="oc-error" id="ocReasonError" role="alert" hidden></p>
                </div>
                <div class="oc-field">
                    <label for="ocDetails">Additional Details</label>
                    <textarea id="ocDetails" name="details" rows="3" maxlength="1000" placeholder="Share any details that may help us improve"></textarea>
                </div>
                <p class="oc-note">Cancellation is available before the deadline shown on your order, while it is awaiting confirmation. No payment is collected for cancelled cash-on-delivery orders.</p>
                <p class="oc-error" id="ocSubmitError" role="alert" hidden></p>
                <footer class="oc-footer">
                    <button type="button" class="oc-keep" data-oc-close>Keep Order</button>
                    <button type="submit" class="oc-confirm" id="ocConfirmCancel">Confirm Cancellation</button>
                </footer>
            </div>
        </form>
    </section>
</div>

<div class="oc-overlay" id="ocDetailsModal" hidden>
    <section class="oc-dialog oc-details-dialog" role="dialog" aria-modal="true" aria-labelledby="ocDetailsTitle" tabindex="-1">
        <header class="oc-header">
            <h2 id="ocDetailsTitle">Cancellation Details</h2>
            <button type="button" class="oc-close" data-oc-close aria-label="Close cancellation details">&times;</button>
        </header>
        <div class="oc-body">
            <dl class="oc-details-grid">
                <div><dt>Order ID</dt><dd id="ocDetailNumber"></dd></div>
                <div><dt>Status</dt><dd class="oc-cancelled">Cancelled</dd></div>
                <div><dt>Requested</dt><dd id="ocDetailRequested"></dd></div>
                <div><dt>Reason</dt><dd id="ocDetailReason"></dd></div>
                <div class="oc-details-wide"><dt>Additional Details</dt><dd class="oc-additional" id="ocDetailText"></dd></div>
            </dl>
            <div class="oc-note oc-refund"><span>Refund Status</span><p id="ocDetailRefund"></p></div>
        </div>
    </section>
</div>
