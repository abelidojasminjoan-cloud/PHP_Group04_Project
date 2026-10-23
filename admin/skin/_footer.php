</main></div>
<div class="form-modal sa-modal" id="saModal" aria-hidden="true">
    <div class="form-modal-backdrop" data-sa-close></div><div class="form-modal-dialog form-modal-large" role="dialog" aria-modal="true" aria-labelledby="saModalTitle" tabindex="-1">
    <form id="saForm" class="purevia-form" novalidate><header class="form-modal-header"><div><h2 id="saModalTitle"></h2><p id="saModalSubtitle"></p></div><button class="form-modal-close" type="button" data-sa-close aria-label="Close">×</button></header>
    <div class="form-modal-body"><p id="saFormError" class="sa-form-error" role="alert" tabindex="-1" hidden></p><div id="saFields"></div></div><footer class="form-modal-footer"><button class="form-cancel-button" type="button" data-sa-close>Cancel</button><button class="form-submit-button" type="submit" id="saSubmit">Save Changes</button></footer></form></div>
</div>
<script nonce="<?= pvScriptNonce() ?>">window.PUREVIA_SKIN_ADMIN=<?= pvJson(['baseUrl'=>BASE_URL,'csrfToken'=>csrfToken()]+($saConfig??[]),JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;</script>
<script nonce="<?= pvScriptNonce() ?>" src="<?= BASE_URL ?>/assets/js/sidemenu.js"></script><script nonce="<?= pvScriptNonce() ?>" src="<?= BASE_URL ?>/assets/js/skin-admin.js"></script>
</body></html>
