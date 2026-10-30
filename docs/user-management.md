# Admin user management

Accounts cannot be deleted through user management, including direct API requests.
The generic create action is also disabled; Add Staff retains its dedicated pending
account, invitation, OTP, activation, and configured Authenticator flow.

Admins can update names and phone numbers. A staff account may retain its empty
last name because staff creation derives its display name from the email address.
Roles, sign-in email addresses, password hashes, verification markers, and
Authenticator settings are not editable here. Password recovery stays with the
user through the email-verified Forgot password flow.

Active, verified accounts may be deactivated and later reactivated. Pending
accounts must finish their own email verification and activation; saving a profile
cannot activate them. An administrator cannot disable or lock their own access.

Status changes and explicit unlocks require confirmation and a 5-500 character
reason. A profile-only save never clears failed attempts. Unlocking requires a
verified, locked account, Active status, and the explicit unlock option. Changing
access increments session_version so old sessions are rejected on their next
request. It does not erase orders, payments, reviews, account or audit history.

The server checks active admin authorization again under a transaction lock,
validates CSRF at the endpoint, and rejects stale revisions. Every successful
profile/access update writes an audit record with actor, target, action, IP and
browser. Access updates include the reason and session revocation. No password,
OTP, or Authenticator secret is returned in the user-management response.