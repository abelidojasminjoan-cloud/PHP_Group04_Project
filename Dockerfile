# syntax=docker/dockerfile:1
FROM composer:2 AS composer-bin
FROM php:8.3-apache-bookworm

ENV APP_ENV=production \
    PORT=10000 \
    UPLOAD_SCANNER=clamav \
    UPLOAD_SCANNER_PATH=/usr/bin/clamscan \
    UPLOAD_SCAN_TIMEOUT=60
WORKDIR /var/www/app

# Install the extensions used by the store and payment integration.
RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip libonig-dev libcurl4-openssl-dev clamav clamav-freshclam \
    && docker-php-ext-install -j"$(nproc)" mysqli pdo_mysql opcache mbstring curl \
    && a2enmod headers rewrite \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && rm -rf /var/lib/apt/lists/*

# Uploads fail closed unless a working scanner and signature database are present.
COPY docker/freshclam.conf /etc/clamav/freshclam.conf
RUN mkdir -p /var/lib/clamav /var/log/clamav /var/run/clamav \
    && chown -R clamav:clamav /var/lib/clamav /var/log/clamav /var/run/clamav \
    && freshclam --stdout \
    && printf 'PureVia upload scanner readiness check\n' > /tmp/purevia-scan-check.txt \
    && su -s /bin/sh www-data -c 'clamscan --no-summary /tmp/purevia-scan-check.txt' \
    && rm /tmp/purevia-scan-check.txt

COPY --from=composer-bin /usr/bin/composer /usr/local/bin/composer
COPY composer.json composer.lock ./
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install \
    --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader

COPY . .
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/purevia.ini
COPY docker/start.sh /usr/local/bin/purevia-start

# Expose the original web folders through links, without copying source files.
RUN set -eux; \
    mkdir -p /var/www/storage/uploads/products /var/www/storage/uploads/profiles \
        /var/www/storage/uploads/reviews /var/www/storage/sessions; \
    ln -s /var/www/storage/uploads /var/www/app/uploads; \
    for directory in assets actions account admin staff uploads; do \
        ln -s "/var/www/app/$directory" "/var/www/app/public/$directory"; \
    done; \
    printf 'Listen ${PORT}\n' > /etc/apache2/ports.conf; \
    printf 'ok\n' > /var/www/app/public/healthz.txt; \
    sed -i 's/\r$//' /usr/local/bin/purevia-start; \
    chmod +x /usr/local/bin/purevia-start; \
    php -r 'foreach (["mysqli", "pdo_mysql", "mbstring", "curl", "openssl", "fileinfo", "sodium"] as $extension) { if (!extension_loaded($extension)) { fwrite(STDERR, "Missing extension: " . $extension . PHP_EOL); exit(1); } }'

EXPOSE 10000
HEALTHCHECK --interval=30s --timeout=5s --start-period=20s \
    CMD curl --fail --silent "http://127.0.0.1:${PORT}/healthz.txt" || exit 1
CMD ["/usr/local/bin/purevia-start"]
