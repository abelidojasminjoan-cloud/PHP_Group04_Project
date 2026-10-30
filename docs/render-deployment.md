# Deploy PureVia to Render with Docker

## Create the web service

1. Push the updated project to your Git repository. Keep `.env`, uploaded customer
   files, and local `vendor` out of the repository and build context.
2. In Render, choose **New > Web Service**, connect the repository, and select
   **Docker**. Use the project root as Root Directory and `./Dockerfile` as Dockerfile
   Path. Leave Docker Command empty so the image's default startup command runs.
3. Set Health Check Path to `/healthz.txt`. This checks Apache availability; it does
   not replace checking the database-backed homepage after deployment.
4. Add the environment variables below before deploying.
5. For persistent uploads and sessions, attach a disk mounted at `/var/www/storage`.
   Transfer existing product images to `/var/www/storage/uploads/products` and other
   uploads to their corresponding subdirectories. The image starts with empty upload
   directories; images under `assets` remain part of the image.
6. Deploy, then check `/`, `/shop.php`, sign-in, image uploads, and checkout.

The container runs Apache on `0.0.0.0:$PORT` (10000 by default). Render supplies the
public HTTPS endpoint. [Render web services](https://render.com/docs/web-services)
use this port binding; the image does not use PHP's development server.

## Environment variables

Set these in Render's **Environment** settings. No private `.env` is copied into
Docker, and credentials are not passed as Docker build arguments.

| Variable | Value |
| --- | --- |
| `APP_ENV` | `production` (already the image default) |
| `APP_URL` | Your HTTPS Render or custom-domain URL, with no trailing slash; if omitted, startup uses `RENDER_EXTERNAL_URL` |
| `DB_HOST` | Reachable hosted MySQL/MariaDB hostname |
| `DB_PORT` | Your provider's port, usually `3306` |
| `DB_NAME` | The hosted database name |
| `DB_USERNAME` | The hosted database user |
| `DB_PASSWORD` | The hosted database password |
| `MAIL_HOST` | Your SMTP provider hostname |
| `MAIL_PORT` | STARTTLS port supported by your SMTP provider, usually `587` |
| `MAIL_USERNAME` | SMTP username |
| `MAIL_PASSWORD` | SMTP password or app password |
| `MAIL_FROM_ADDRESS` | Verified sender email |
| `MAIL_FROM_NAME` | `PureVia` |
| `STAFF_DEFAULT_PASSWORD` | Private initial staff password satisfying the configured policy |
| `TWO_FACTOR_ENABLED` | `true` |
| `PAYPAL_MODE` | `sandbox` while testing |
| `PAYPAL_CLIENT_ID` | Matching PayPal Sandbox app Client ID |
| `PAYPAL_CLIENT_SECRET` | Matching PayPal Sandbox app Secret |
| `PAYPAL_WEBHOOK_ID` | Registered webhook ID from the same app; see [webhook setup](paypal-setup.md#render-payment-webhook) |

Use an external MySQL/MariaDB server and import `database/purevia_db.sql` plus the
required migrations through that provider's tools. This Docker image does not run
or initialize a database. `localhost` and XAMPP on your computer are not reachable
as the production database. Check any TLS connection requirements with the database
provider before choosing its connection settings.

Render's [free service limits](https://render.com/docs/free) block outbound SMTP
ports 25, 465, and 587. The current Gmail/PHPMailer OTP, staff invitation, and receipt
emails therefore require hosting that permits the configured SMTP port. An email
HTTP API would require a separate application change; this Docker setup retains the
existing SMTP implementation.

[Persistent disks](https://render.com/docs/disks) require a paid service. Without a
disk at `/var/www/storage`, uploaded images and login sessions are lost when the
container is replaced. Apache exposes uploaded JPG/PNG/WebP images only; session
files remain private. Source folders such as config, vendor, and services are outside
the public document root.

Schedule `php /var/www/app/tools/expire-paypal-checkouts.php` separately for abandoned
PayPal reservations. Database credentials and PayPal environment must match the web
service. This Dockerfile does not install a scheduler or enable live payments.

## Optional local Docker check

From the project root, on a computer with Docker installed:

```powershell
docker build -t purevia .
docker run --rm -p 10000:10000 --env-file .env -e APP_ENV=local -e APP_URL=http://localhost:10000 -e DB_HOST=host.docker.internal -v purevia-storage:/var/www/storage purevia
```

The example uses Docker Desktop to reach your local MySQL server. Open
http://localhost:10000/. The local `APP_ENV=local` override allows cookies on HTTP;
Render must use `production` and its HTTPS URL. Linux Docker hosts may need an
explicit host-gateway mapping to reach a host database.

This setup preserves `php -S localhost:8000 -t public` and the existing Apache/XAMPP

## Registration and database rollout

Add `RECAPTCHA_SITE_KEY` and `RECAPTCHA_SECRET_KEY` to Render and authorize the
Render hostname (or custom hostname) in your reCAPTCHA dashboard. Keep secrets in
Render Environment, never in a Dockerfile, Git, or client JavaScript.

Use a paid web service with a persistent disk for the existing SMTP and image upload
features. A free service is only a limited preview: current SMTP OTP emails will not
work on its blocked ports and uploads will not persist. No paid service is created
by these files.

Before importing database SQL, review the export for personal data, existing users,
password hashes, tokens, and test transactions. Use a sanitized production database.
Apply only missing migrations: some baseline tables/columns may already be present.
Back up an existing database before migrating. No database is modified at image
build or startup. Configure provider-required MySQL TLS before deployment; the
existing database connector uses its existing local connection configuration.

PayPal remains Sandbox. Use the Business Sandbox app credentials in Render, and a
separate Personal Sandbox buyer to test payment. APP_URL must be the deployed HTTPS
origin so PayPal return URLs and activation/reset links point to the deployed store.
Test an approved payment and a cancelled payment; check that paid status changes
only after a verified capture. Test OTP delivery, staff activation, password reset,
admin authentication, uploads, and stock reservation/cancellation before going live.

The Docker image cannot include your ignored upload files. Transfer existing images
to the persistent disk before expecting existing catalog images to display. Render
single-disk services use one instance; do not horizontally scale file-based sessions
without first changing session and upload storage.
## HTTP 500: security_settings does not exist

If Render logs report `Table 'defaultdb.security_settings' doesn't exist`, Docker
and the MySQL connection are working, but the selected database is missing an
application table. Keep the Dockerfile path, build context, and command unchanged.

1. Check Render Environment `DB_NAME` points to the database where you imported
   PureVia. Hosted providers may name it `defaultdb`; that name is valid.
2. In the hosted MySQL provider's SQL console or a connected MySQL client, select
   that same database and run `SHOW TABLES;`.
3. If this is a new, empty database, import a reviewed/sanitized copy of
   `database/purevia_db.sql` into it. Importing via LOCAL XAMPP phpMyAdmin only
   changes your computer's database, unless explicitly connected to the hosted DB.
4. If only `security_settings` is missing, copy and execute its `CREATE TABLE`
   statement from `database/purevia_db.sql` in the hosted database. Preserve
   existing tables and policy records; do not reimport the full SQL file over
   an existing database just to repair one missing table.
5. Reload the site and inspect Render logs for any remaining missing schema.
   If you changed Render DB_NAME, save the environment settings and redeploy.

Pushing this SQL file to Git does not execute it. The image intentionally does not
run schema imports automatically. Do not enable public PHP error display or bypass
security policy checks to hide missing tables.
## MySQL Error 3750 during SQL import

Aiven requires a primary key in each CREATE TABLE statement. Older phpMyAdmin dumps
add primary keys later with ALTER TABLE, so their initial CREATE statements fail.
The current database/purevia_db.sql defines all primary keys, including composite
keys, inside CREATE TABLE and retains secondary indexes and auto-increment setup.
Re-exporting from phpMyAdmin may produce the older order again.

Use the corrected SQL export with defaultdb selected as your Workbench default
schema. Execute the entire script, not only the current statement or selection.
Refresh Schemas afterward and check that security_settings and the other tables
exist. Keep sql_require_primary_key enabled.

If an earlier import created some tables, inspect the import errors and existing
tables before retrying a full import; do not drop a database containing data you
need. These SQL dumps initialize a database and are not incremental migrations.
Public-page links now use /login.php, /shop.php and /order-success.php at the deployed origin. No nested public compatibility directory is needed. Apache redirects legacy /public/page.php requests to the clean page URLs.


The original account, actions, admin, staff, assets and uploads folders remain outside public/. Windows uses local junctions and Docker creates Linux links so the application keeps the same URLs without a routes folder. Local startup is php -S localhost:8000 -t public.
