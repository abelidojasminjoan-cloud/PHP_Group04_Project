#!/bin/sh
set -eu

: "${PORT:=10000}"
case "$PORT" in
    ''|*[!0-9]*) echo 'PORT must be a number.' >&2; exit 1 ;;
esac
if [ "${#PORT}" -gt 5 ] || [ "$PORT" -lt 1 ] || [ "$PORT" -gt 65535 ]; then
    echo 'PORT must be between 1 and 65535.' >&2
    exit 1
fi
export PORT

# Render supplies its default HTTPS origin; APP_URL can override it for a domain.
APP_URL="${APP_URL:-${RENDER_EXTERNAL_URL:-}}"
: "${APP_URL:?Set APP_URL to the public website URL.}"
export APP_URL
: "${DB_HOST:?Set DB_HOST to your hosted MySQL server.}"
: "${DB_NAME:?Set DB_NAME.}"
: "${DB_USERNAME:?Set DB_USERNAME.}"

# A persistent disk mounts over these directories, so set permissions at startup.
mkdir -p /var/www/storage/uploads/products /var/www/storage/uploads/profiles \
    /var/www/storage/uploads/reviews /var/www/storage/sessions /var/www/storage/returns
chown -R www-data:www-data /var/www/storage/uploads /var/www/storage/sessions /var/www/storage/returns
chmod 700 /var/www/storage/sessions /var/www/storage/returns

# Keep the bundled signatures current. clamscan itself runs only for uploads.
if [ "${UPLOAD_SCANNER:-clamav}" = 'clamav' ]; then
    if [ ! -x "${UPLOAD_SCANNER_PATH:-/usr/bin/clamscan}" ]; then
        echo 'Upload scanner missing: use the updated PureVia Docker image.' >&2
        exit 1
    fi
    /usr/bin/freshclam --daemon --stdout
fi

exec apache2-foreground
