#!/usr/bin/env bash

echo "[FIX] Restarting services..."
systemctl restart nginx
systemctl restart php8.3-fpm
systemctl restart mariadb

echo "[FIX] Repairing permissions..."
chown -R www-data:www-data /var/www/paserexpress
find /var/www/paserexpress -type d -exec chmod 755 {} \;
find /var/www/paserexpress -type f -exec chmod 644 {} \;

echo "[FIX] Clearing PHP-FPM cache..."
rm -rf /var/lib/php/sessions/* || true

echo "[FIX] Test Nginx config..."
nginx -t && systemctl reload nginx

echo "[DONE] Basic common issues fixed."
