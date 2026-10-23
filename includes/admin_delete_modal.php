<?php
// Shared admin confirmation only. All styling is scoped to #adminDeleteModal.
?>
<div class="form-modal" id="adminDeleteModal" aria-hidden="true">
    <div class="form-modal-backdrop" data-close-admin-delete></div>
    <div class="form-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="adminDeleteTitle" aria-describedby="adminDeleteDescription" tabindex="-1">
        <form class="purevia-form" id="adminDeleteForm" novalidate>
            <header class="form-modal-header">
                <h2 id="adminDeleteTitle">Confirm deletion</h2>
                <button type="button" class="form-modal-close" data-close-admin-delete aria-label="Close confirmation">
                    <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                </button>
            </header>
            <div class="form-modal-body">
                <p id="adminDeleteDescription">Are you sure you want to delete <strong id="adminDeleteName"></strong>?</p>
                <p class="admin-delete-note" id="adminDeleteNote"></p>
                <p class="admin-delete-error" id="adminDeleteError" role="alert" tabindex="-1" hidden></p>
            </div>
            <footer class="form-modal-footer">
                <button type="button" class="form-cancel-button" id="adminDeleteCancel" data-close-admin-delete>Cancel</button>
                <button type="submit" class="form-submit-button" id="adminDeleteSubmit">Delete</button>
            </footer>
        </form>
    </div>
</div>
<script nonce="<?= pvScriptNonce() ?>" src="<?= BASE_URL ?>/assets/js/admin_delete.js"></script>
