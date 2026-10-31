<?php
// Homepage only: published text reviews, without customer-uploaded images.
$homeReviewFrom = "FROM product_reviews r
    JOIN users u ON u.id=r.user_id
    JOIN products p ON p.id=r.product_id
    JOIN categories c ON c.id=p.category_id
    WHERE r.status='approved' AND p.status='active' AND c.status='active'
      AND TRIM(r.review_text)<>'' AND r.rating BETWEEN 1 AND 5";
$homeReviewSummary = $conn->query('SELECT COUNT(*) AS total, COALESCE(AVG(r.rating),0) AS average_rating ' . $homeReviewFrom)->fetch_assoc();
$homeReviews = $conn->query('SELECT r.rating,r.title,r.review_text,r.verified_purchase,u.first_name,u.last_name,p.id AS product_id,p.product_name '
    . $homeReviewFrom . ' ORDER BY r.created_at DESC,r.id DESC LIMIT 6')->fetch_all(MYSQLI_ASSOC);
$homeReviewTotal = (int) $homeReviewSummary['total'];
?>
<section class="pv-home-reviews" aria-labelledby="homeReviewsTitle" data-home-reviews>
    <div class="pv-reviews-container">
        <div class="pv-reviews-heading">
            <div><p class="pv-reviews-eyebrow">THE SKIN DIARIES</p><h2 id="homeReviewsTitle">Rituals worth repeating.</h2></div>
            <div class="pv-home-review-controls">
                <p class="pv-reviews-summary"><?php if ($homeReviewTotal): ?><?= number_format((float)$homeReviewSummary['average_rating'],1) ?> / 5 average &middot; <?= number_format($homeReviewTotal) ?> <?= $homeReviewTotal===1?'customer review':'customer reviews' ?><?php else: ?>Customer reviews<?php endif; ?></p>
                <button type="button" class="pv-home-reviews-toggle" data-review-toggle aria-pressed="false" hidden>Pause reviews</button>
            </div>
        </div>
        <?php if ($homeReviews): ?>
        <div class="pv-home-reviews-viewport" data-review-viewport aria-label="Customer reviews">
            <div class="pv-home-reviews-track" data-review-track>
                <div class="pv-home-reviews-group" data-review-group>
                <?php foreach ($homeReviews as $homeReview):
                    $rating = max(1,min(5,(int)$homeReview['rating']));
                    $first = trim($homeReview['first_name']);
                    $author = ($first ?: 'Customer') . (trim($homeReview['last_name'])!==''?' '.mb_substr(trim($homeReview['last_name']),0,1).'.':'');
                    $text = trim(preg_replace('/\s+/u',' ',$homeReview['review_text']));
                    $excerpt = mb_strlen($text)>280 ? mb_substr($text,0,277).'…' : $text;
                    $title = trim($homeReview['title']);
                ?>
                    <article class="pv-review-card pv-home-review-card">
                        <div class="pv-review-stars" role="img" aria-label="<?= $rating ?> out of 5 stars"><span aria-hidden="true"><?= str_repeat('&#9733;',$rating) ?><span class="pv-home-review-empty-stars"><?= str_repeat('&#9734;',5-$rating) ?></span></span></div>
                        <?php if ($title!=='' && mb_strtolower($title)!=='customer review'): ?><h3><?= pvHtml(mb_substr($title,0,100)) ?></h3><?php endif; ?>
                        <blockquote>&ldquo;<?= pvHtml($excerpt) ?>&rdquo;</blockquote>
                        <footer><p class="pv-review-author"><?= pvHtml($author) ?><?php if ((int)$homeReview['verified_purchase']===1): ?> <span>&middot; Verified buyer</span><?php endif; ?></p><a href="<?= BASE_URL ?>/product.php?id=<?= (int)$homeReview['product_id'] ?>"><?= pvHtml($homeReview['product_name']) ?></a></footer>
                    </article>
                <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php else: ?><p class="pv-home-reviews-empty">Customer reviews will appear here once approved.</p><?php endif; ?>
    </div>
</section>
