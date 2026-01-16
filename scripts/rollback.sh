#!/usr/bin/env bash
set -e

APP_DIR="/var/www/paserexpress"

echo "[Rollback] Checking git history..."

cd "$APP_DIR"

git reset --hard HEAD~1

systemctl reload nginx
systemctl restart php8.3-fpm

echo "[Rollback] Rolled back to previous version."
