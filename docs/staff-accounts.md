# Staff account creation and activation

Admins open Users → Add Staff and enter an email address. The display name is
created from its local part. The server assigns the staff role and pending status;
this form cannot create administrators or customers.

`STAFF_DEFAULT_PASSWORD` in `.env` is the shared initial password for newly created
staff accounts. Each account stores its own salted password hash. Installing this
feature configures a generated value if one is not already present. It does not
change existing account passwords. Keep `.env` private. If you change the initial
password, only subsequently created accounts use the new value. It must satisfy
the current security password policy.

The initial sign-in details are sent through the existing SMTP configuration.
If the invitation cannot be sent, creation rolls back and the admin can try again.

1. Staff sign in with their email and initial password.
2. A pending staff account receives a 6-digit email OTP. It has no dashboard access.
3. The existing verification screen checks the OTP (10-minute expiry, 5 guesses).
4. A correct OTP sends a separate, single-use activation link valid for 30 minutes.
   The account remains pending until that email link is used.
5. The activation button activates the account and returns to sign-in. The normal
   existing login and configured two-factor checks then apply.

OTP requests share the registration resend limits: 60-second cooldown and 5/hour.
Creation records a CREATE audit event. Credentials and raw OTPs are not logged.
Staff should replace the shared initial password after setup; the existing Forgot
Password flow can be used for an activated staff account.

Test mail delivery with a controlled staff email. Development links use the local
APP_URL; an emailed localhost link can only be opened on the computer hosting the
website. Configure a reachable APP_URL when deploying.
After activation and successful sign-in, staff who have not changed their initial
password see a dashboard warning. It links to account/renew-password.php, which
also accepts a staff account still using its initial password, even when the
normal password-expiry policy is disabled. The form requires authentication,
CSRF verification, the current password, a different policy-compliant password,
and matching confirmation. Staff cannot choose the configured shared password.

The warning persists until the initial password is changed. It does not impose a
new deadline, lock the account, or bypass activation/Google Authenticator. Leaving
the shared password in place lets anyone who knows it attempt the password step.
The existing expiry and lockout policies still apply independently. A null
password_changed_at keeps the warning visible if STAFF_DEFAULT_PASSWORD rotates;
a matching current default hash is also detected. No password is shown in the UI.