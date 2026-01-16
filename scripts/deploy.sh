#!/usr/bin/env bash
set -e

REPO="https://github.com/azizcool1998/paserexpress.git"
APP_DIR="/var/www/paserexpress"

echo "[Deploy] Starting deployment..."

if [[ -d "$APP_DIR/.git" ]]; then
    echo "[Deploy] Pulling latest changes..."
    git -C "$APP_DIR" fetch --all
    git -C "$APP_DIR" reset --hard origin/main
else
    echo "[Deploy] Cloning fresh copy..."
    rm -rf "$APP_DIR"
    git clone "$REPO" "$APP_DIR"
fi

echo "[Deploy] Setting permissions..."
chown -R www-data:www-data "$APP_DIR"
chmod -R 755 "$APP_DIR"

echo "[Deploy] Reloading services..."
systemctl reload nginx || true
systemctl restart php8.3-fpm || true

echo "[Deploy] Deployment completed successfully."
