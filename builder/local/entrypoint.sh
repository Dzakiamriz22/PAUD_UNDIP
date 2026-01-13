#!/bin/sh
set -e  # Stop jika ada perintah yang gagal

echo "Running Laravel setup..."

echo "Installing composer dependencies..."
composer install

echo "Running migrations..."
php artisan migrate --force --seed
php artisan permission:create-permission delete-backup
php artisan permission:create-permission download-backup

echo "Generating Shield resources..."
php artisan shield:generate --resource=RoleResource --panel=admin

# echo "Clearing and caching..."
# php artisan filament:optimize-clear
# php artisan optimize:clear
# php artisan config:cache
# php artisan route:cache
# php artisan view:cache

echo "Npm setup [START]"
npm install
# npm install -g puppeteer
# npx puppeteer browser install chrome
# npm run dev
echo "Npm setup [DONE]"

echo "Starting supervisor..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
