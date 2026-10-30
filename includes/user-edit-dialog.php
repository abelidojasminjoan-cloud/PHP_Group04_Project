<div id="userModal" class="form-modal user-edit-modal" aria-hidden="true">
    <div class="form-modal-backdrop" data-close-user-modal></div>
    <div class="form-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="userFormTitle" aria-describedby="userFormDescription" tabindex="-1">
        <form id="userForm" class="purevia-form" action="<?= BASE_URL ?>/actions/admin/users/user.php" method="post" data-admin-id="<?= (int) $currentUser['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= pvHtml(csrfToken()) ?>">
            <input type="hidden" name="id"><input type="hidden" name="revision"><input type="hidden" name="action" value="update"><input type="hidden" name="role_id">
            <header class="form-modal-header">
                <div><h2 id="userFormTitle">Manage User</h2><p id="userFormDescription">Update profile details and manage sign-in access.</p></div>
                <button type="button" class="form-modal-close" data-close-user-modal aria-label="Close user form">&times;</button>
            </header>
            <div class="form-modal-body">
                <p id="userFormError" class="form-alert form-alert-error" role="alert" tabindex="-1" hidden></p>
                <div class="form-grid form-grid-2">
                    <div class="form-group"><label for="userFirstName">First Name <span class="form-required">*</span></label><input id="userFirstName" class="form-control" name="first_name" maxlength="100" autocomplete="off" required></div>
                    <div class="form-group"><label for="userLastName">Last Name <span id="userLastNameRequired" class="form-required">*</span></label><input id="userLastName" class="form-control" name="last_name" maxlength="100" autocomplete="off" required></div>
                </div>
                <div class="form-group"><label for="userEmail">Sign-in email</label><input id="userEmail" class="form-control" name="email" type="email" readonly aria-describedby="userIdentityHelp"></div>
                <div class="form-group"><label for="userPhone">Phone</label><input id="userPhone" class="form-control" name="phone" type="tel" maxlength="30" autocomplete="off"></div>
                <div class="form-grid form-grid-2">
                    <div class="form-group"><label for="userRoleLabel">Role</label><input id="userRoleLabel" class="form-control" readonly aria-describedby="userIdentityHelp"></div>
                    <div class="form-group"><label for="userStatus">Account status</label><select id="userStatus" name="status" class="form-control" aria-describedby="userStatusHelp" required><option value="active">Active</option><option value="inactive">Inactive</option><option value="locked">Locked</option><option value="pending">Pending verification</option></select></div>
                </div>
                <p class="user-form-help" id="userIdentityHelp">The sign-in email and assigned role are protected. Passwords stay private; users reset them through Forgot password and email verification.</p>
                <p class="user-form-help" id="userStatusHelp">Inactive blocks sign-in and preserves the user's orders and history. Pending accounts must complete email activation.</p>
                <div id="userUnlockSection" class="user-access-panel" hidden><label class="user-access-check"><input type="checkbox" name="unlock_account" id="userUnlock"> Unlock sign-in access</label><p class="user-form-help">Select Active and confirm this option to clear the account's failed-attempt lock.</p></div>
                <div id="userAccessReview" class="user-access-panel" hidden>
                    <div class="form-group"><label for="userAccessReason">Reason for access change <span class="form-required">*</span></label><textarea id="userAccessReason" name="reason" class="form-control form-textarea" minlength="5" maxlength="500" placeholder="Explain why access is being changed"></textarea></div>
                    <label class="user-access-check"><input type="checkbox" id="userAccessConfirm" name="confirm_access_change"> I confirm this access change and understand that existing sessions will be signed out.</label>
                </div>
                <p class="user-retention-note">Accounts are retained for audit and order history. Use Inactive to remove access; accounts cannot be deleted here.</p>
            </div>
            <footer class="form-modal-footer"><button type="button" class="form-cancel-button" data-close-user-modal>Cancel</button><button type="submit" id="userSaveButton" class="form-submit-button">Save Changes</button></footer>
        </form>
    </div>
</div>