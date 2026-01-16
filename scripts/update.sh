#!/usr/bin/env bash
set -e

APP_DIR="/var/www/paserexpress"

echo "[Update] Updating PaserExpress..."

git -C "$APP_DIR" fetch --all
git -C "$APP_DIR" reset --hard origin/main

chown -R www-data:www-data "$APP_DIR"

systemctl reload nginx
systemctl restart php8.3-fpm

echo "[Update] Update completed."
