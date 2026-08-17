#!/bin/sh

# Cache configurations
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations (will skip if DB is not configured correctly yet)
php artisan migrate --force || true

# Seed the database
php artisan db:seed --force || true

# Fix permissions for storage and bootstrap cache just in case they were created as root
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Start PHP-FPM in background
php-fpm -D

# Start Nginx in foreground
nginx -g "daemon off;"
