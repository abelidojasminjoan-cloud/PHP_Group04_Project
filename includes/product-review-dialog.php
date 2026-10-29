<dialog id="productReviewDialog" class="review-dialog" aria-labelledby="productReviewTitle" aria-describedby="productReviewSubtitle">
<form id="productReviewForm" action="<?= BASE_URL ?>/actions/reviews/submit.php" method="post" enctype="multipart/form-data">
    <header class="form-modal-header review-dialog-header">
        <div><h2 id="productReviewTitle">Review your products</h2><p id="productReviewSubtitle">Share your experience with other PureVia customers</p></div>
        <button type="button" class="form-modal-close" data-review-close aria-label="Close review form">&times;</button>
    </header>
    <div class="form-modal-body review-dialog-body">
        <p id="productReviewMessage" class="review-message" tabindex="-1" role="status" aria-live="polite" hidden></p>
        <div id="productReviewFields">
            <div class="form-group" id="reviewProductChoice" hidden><label for="reviewProductSelect">Choose a product</label><select id="reviewProductSelect" class="form-control"></select></div>
            <div class="review-product"><div><strong id="reviewProductName"></strong><span id="reviewProductVariant"></span></div><span class="review-quantity" id="reviewProductQuantity"></span></div>
            <input type="hidden" name="csrf_token" value="<?= pvHtml($csrfToken) ?>">
            <input type="hidden" name="order_id" id="reviewOrderId">
            <input type="hidden" name="product_id" id="reviewProductId">
            <fieldset class="review-rating"><legend>Your rating <span class="form-required" aria-hidden="true">*</span></legend><div class="review-stars">
                <?php for ($star = 1; $star <= 5; $star++): ?>
                <label class="review-star"><input type="radio" name="rating" value="<?= $star ?>" required aria-label="<?= $star ?> <?= $star === 1 ? 'star' : 'stars' ?>"><span aria-hidden="true">★</span></label>
                <?php endfor; ?>
            </div></fieldset>
            <div class="form-group"><label for="reviewText">Your Review <span class="form-required" aria-hidden="true">*</span></label><textarea id="reviewText" class="form-control form-textarea" name="review_text" rows="4" maxlength="5000" required placeholder="What did you like? How did the product work for your skin?"></textarea></div>
            <div class="review-upload"><div><strong>Add photos</strong><small id="reviewPhotoHelp">Optional · Up to 5 JPG, PNG, or WebP images · 5 MB each</small></div><button type="button" id="reviewUploadButton"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M12 16V3m-5 5 5-5 5 5M4 14v7h16v-7"/></svg>Upload photos</button><input id="reviewPhotos" type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple hidden aria-describedby="reviewPhotoHelp"></div>
            <ul class="review-photo-list" id="reviewPhotoList" aria-label="Selected photos"></ul>
        </div>
    </div>
    <footer class="form-modal-footer review-dialog-footer"><button type="button" class="form-cancel-button" data-review-close>Cancel</button><button type="submit" class="form-submit-button" id="reviewSubmit">Submit Review</button></footer>
</form>
</dialog>