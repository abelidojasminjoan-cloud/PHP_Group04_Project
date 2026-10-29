<dialog id="staffAccountDialog" class="staff-account-dialog" aria-labelledby="staffAccountTitle">
<form id="staffAccountForm" action="<?= BASE_URL ?>/actions/admin/users/create-staff.php" method="post">
<header class="form-modal-header"><h2 id="staffAccountTitle">Add Staff Account</h2><button type="button" class="form-modal-close" data-staff-close aria-label="Close Add Staff Account">&times;</button></header>
<div class="form-modal-body">
<p id="staffAccountMessage" class="staff-account-message" tabindex="-1" role="status" hidden></p>
<div id="staffAccountFields"><input type="hidden" name="csrf_token" value="<?= pvHtml($_SESSION['csrf_token']) ?>">
<div class="form-group"><label for="staffAccountEmail">Email Address <span class="form-required" aria-hidden="true">*</span></label><input id="staffAccountEmail" class="form-control" name="email" type="email" maxlength="255" placeholder="staff@purevia.com" autocomplete="email" required></div>
<p class="staff-account-help">The display name is created from the email address. An initial password will be sent to the new staff member for their first login.</p></div>
</div>
<footer class="form-modal-footer"><button type="button" class="form-cancel-button" data-staff-close>Cancel</button><button type="submit" class="form-submit-button" id="staffAccountSubmit">Create Account</button></footer>
</form></dialog>