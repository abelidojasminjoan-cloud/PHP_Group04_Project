<dialog class="return-dialog return-customer-dialog" id="orReturnModal" aria-labelledby="orReturnTitle" aria-describedby="orReturnSubtitle">
    <form class="purevia-form" id="orReturnForm" enctype="multipart/form-data" novalidate>
        <header class="form-modal-header">
            <div>
                <h2 id="orReturnTitle">Return Order</h2>
                <p id="orReturnSubtitle">Choose products to return from <span id="orOrderNumber"></span></p>
            </div>
            <button type="button" class="form-modal-close" data-or-close aria-label="Close return dialog">&times;</button>
        </header>
        
            <div class="form-modal-body">
                <div id="orReturnFields"><ol class="return-steps" aria-label="Return request steps"><li><span>1</span>Select items</li><li><span>2</span>Describe issue</li><li><span>3</span>Choose resolution</li></ol>
                    <fieldset class="return-fieldset form-group" id="orProductsGroup" aria-describedby="orProductsError">
                        <legend>Select products</legend>
                        <div class="return-products" id="orProducts"></div>
                        <p class="rt-error" id="orProductsError" role="alert" hidden></p>
                    </fieldset>
                    <div class="form-group">
                        <label for="orReason">Reason for Return <span class="form-required" aria-hidden="true">*</span></label>
                        <div class="oc-select-wrap">
                            <select class="form-control" id="orReason" name="reason" aria-required="true" aria-describedby="orReasonError">
                                <option value="">Select a reason</option>
                                <?php foreach (orderReturnReasons() as $returnReason): ?>
                                    <option value="<?= pvHtml($returnReason, ENT_QUOTES, 'UTF-8') ?>"><?= pvHtml($returnReason, ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <p class="rt-error" id="orReasonError" role="alert" hidden></p>
                    </div>
                    <fieldset class="return-fieldset form-group" id="orResolutionGroup" aria-describedby="orResolutionError">
                        <legend>Preferred Resolution <span class="form-required" aria-hidden="true">*</span></legend>
                        <div class="return-choices">
                            <label class="return-choice">
                                <input type="radio" name="resolution" value="refund" checked aria-required="true">
                                <span><strong>Refund</strong><small>Return payment to the original method</small></span>
                            </label>
                            <label class="return-choice">
                                <input type="radio" name="resolution" value="replacement" aria-required="true">
                                <span><strong>Replacement</strong><small>Send the same products again</small></span>
                            </label>
                        </div>
                        <p class="rt-error" id="orResolutionError" role="alert" hidden></p>
                    </fieldset>
                    <div class="form-group">
                        <label for="orDetails">Return Details</label>
                        <textarea class="form-control form-textarea" id="orDetails" name="details" rows="3" maxlength="1000" placeholder="Describe the issue, condition of the products, and packaging"></textarea>
                    </div>
                    <div class="return-upload"><div><strong>Upload evidence</strong><small>Add photos of the product, packaging, damage, or issue. Up to 5 images, 5 MB each.</small></div><button type="button" class="return-outline" id="orAddPhotos">&#8593; Add photos</button><input type="file" id="orPhotos" accept="image/jpeg,image/png,image/webp" multiple hidden></div>
                    <ul id="orPhotoList" class="return-photos" aria-label="Selected evidence photos"></ul><p class="rt-error" id="orPhotosError" role="alert" hidden></p>
                    <fieldset class="return-fieldset form-group"><legend>How will you return the products? <span class="form-required">*</span></legend><div class="return-choices"><label class="return-choice"><input type="radio" name="return_method" value="courier" checked required><span><strong>Courier Pickup</strong><small>Request collection from your delivery address</small></span></label><label class="return-choice"><input type="radio" name="return_method" value="dropoff"><span><strong>Drop-off</strong><small>Bring the parcel to an approved location</small></span></label></div></fieldset>
                    <p class="return-note">Return requests are reviewed within 1–2 business days. Keep the products and original packaging until you receive return instructions.</p>
                </div>
                <p class="rt-error" id="orSubmitError" role="alert" hidden></p>
                <p class="return-success" id="orSuccess" role="status" hidden></p>

            </div>
                <footer class="form-modal-footer">
                    <button type="button" class="form-cancel-button" data-or-close id="orKeepOrder">Cancel</button>
                    <button type="submit" class="form-submit-button" id="orSubmitReturn">Submit Return Request</button>
                </footer>
        </form>

</dialog>