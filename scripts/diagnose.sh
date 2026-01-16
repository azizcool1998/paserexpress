#!/usr/bin/env bash

echo "=== PASEREXPRESS DIAGNOSE ==="

echo "[1] Checking Nginx..."
systemctl status nginx --no-pager || echo "Nginx error!"

echo "[2] Checking PHP-FPM..."
systemctl status php8.3-fpm --no-pager || echo "PHP-FPM error!"

echo "[3] Checking MariaDB..."
systemctl status mariadb --no-pager || echo "MariaDB error!"

echo "[4] Checking Nginx config..."
nginx -t || echo "Nginx configuration BROKEN!"

echo "[5] File permissions audit..."
ls -lah /var/www/paserexpress/src/public

echo "[6] PHP error log:"
tail -n 30 /var/log/php8.3-fpm.log 2>/dev/null || echo "No php log"

echo "[7] Nginx error log:"
tail -n 30 /var/log/nginx/error.log 2>/dev/null || echo "No nginx error log"

echo "=== Diagnosis Completed ==="
