<div class="oc-overlay or-return-modal" id="orReturnModal" hidden>
    <section class="oc-dialog" role="dialog" aria-modal="true" aria-labelledby="orReturnTitle" aria-describedby="orReturnSubtitle" tabindex="-1">
        <header class="oc-header">
            <div>
                <h2 id="orReturnTitle">Return Order</h2>
                <p id="orReturnSubtitle">Choose products to return from <span id="orOrderNumber"></span></p>
            </div>
            <button type="button" class="oc-close" data-or-close aria-label="Close return dialog">&times;</button>
        </header>
        <form class="oc-form" id="orReturnForm" novalidate>
            <div class="oc-body">
                <div id="orReturnFields">
                    <fieldset class="or-fieldset oc-field" id="orProductsGroup" aria-describedby="orProductsError">
                        <legend>Select products</legend>
                        <div class="or-products" id="orProducts"></div>
                        <p class="oc-error" id="orProductsError" role="alert" hidden></p>
                    </fieldset>
                    <div class="oc-field">
                        <label for="orReason">Reason for Return <span class="oc-required" aria-hidden="true">*</span></label>
                        <div class="oc-select-wrap">
                            <select id="orReason" name="reason" aria-required="true" aria-describedby="orReasonError">
                                <option value="">Select a reason</option>
                                <?php foreach (orderReturnReasons() as $returnReason): ?>
                                    <option value="<?= pvHtml($returnReason, ENT_QUOTES, 'UTF-8') ?>"><?= pvHtml($returnReason, ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <p class="oc-error" id="orReasonError" role="alert" hidden></p>
                    </div>
                    <fieldset class="or-fieldset oc-field" id="orResolutionGroup" aria-describedby="orResolutionError">
                        <legend>Preferred Resolution <span class="oc-required" aria-hidden="true">*</span></legend>
                        <div class="or-resolutions">
                            <label class="or-choice">
                                <input type="radio" name="resolution" value="refund" checked aria-required="true">
                                <span><strong>Refund</strong><small>Return payment to the original method</small></span>
                            </label>
                            <label class="or-choice">
                                <input type="radio" name="resolution" value="replacement" aria-required="true">
                                <span><strong>Replacement</strong><small>Send the same products again</small></span>
                            </label>
                        </div>
                        <p class="oc-error" id="orResolutionError" role="alert" hidden></p>
                    </fieldset>
                    <div class="oc-field">
                        <label for="orDetails">Return Details</label>
                        <textarea id="orDetails" name="details" rows="3" maxlength="1000" placeholder="Describe the issue, condition of the products, and packaging"></textarea>
                    </div>
                    <p class="oc-note">Return requests are reviewed within 1–2 business days. Keep the products and original packaging until you receive return instructions.</p>
                </div>
                <p class="oc-error" id="orSubmitError" role="alert" hidden></p>
                <p class="or-success" id="orSuccess" role="status" hidden></p>
                <footer class="oc-footer">
                    <button type="button" class="oc-keep" data-or-close id="orKeepOrder">Cancel</button>
                    <button type="submit" class="oc-confirm or-submit" id="orSubmitReturn">Submit Return Request</button>
                </footer>
            </div>
        </form>
    </section>
</div>
