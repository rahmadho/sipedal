#!/bin/sh
set -e

# Pastikan permission storage & bootstrap cache aman
mkdir -p /var/www/.postgresql
chown -R www-data:www-data /var/www /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Cache konfigurasi & rute saat container start
php /var/www/html/artisan package:discover --ansi
php /var/www/html/artisan config:cache || true
php /var/www/html/artisan route:cache || true
php /var/www/html/artisan view:cache || true

exec "$@"
