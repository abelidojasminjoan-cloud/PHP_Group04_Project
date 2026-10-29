# Deploy PureVia to Render with Docker

## Create the web service

1. Push the updated project to your Git repository. Keep `.env`, uploaded customer
   files, and local `vendor` out of the repository and build context.
2. In Render, choose **New → Web Service**, connect the repository, and select
   **Docker**. Use the project root as Root Directory and `./Dockerfile` as Dockerfile
   Path. Leave Docker Command empty so the image's default startup command runs.
3. Set Health Check Path to `/healthz.txt`. This checks Apache availability; it does
   not replace checking the database-backed homepage after deployment.
4. Add the environment variables below before deploying.
5. For persistent uploads and sessions, attach a disk mounted at `/var/www/storage`.
   Transfer existing product images to `/var/www/storage/uploads/products` and other
   uploads to their corresponding subdirectories. The image starts with empty upload
   directories; images under `assets` remain part of the image.
6. Deploy, then check `/`, `/public/shop.php`, sign-in, image uploads, and checkout.

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
subdirectory URLs. Production URL paths come from APP_URL. Windows-only public
junctions are excluded from Docker; the image creates Linux links to the same web
folders. The existing layout, forms, payment choices, and business logic are unchanged.
