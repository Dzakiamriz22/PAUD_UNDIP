#!/bin/sh
set -e  # Stop jika ada perintah yang gagal

echo "Running Laravel setup..."

echo "Installing composer dependencies..."
composer install --no-dev --optimize-autoloader

echo "Running migrations..."
php artisan migrate --force --seed
php artisan permission:create-permission delete-backup
php artisan permission:create-permission download-backup

echo "Generating Shield resources..."
php artisan shield:generate --resource=RoleResource --panel=admin

echo "Clearing and caching..."
php artisan filament:optimize-clear
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Npm setup..."
npm install
npm run build

echo "Starting supervisor..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf