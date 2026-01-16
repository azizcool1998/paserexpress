#!/usr/bin/env bash
set -e

APP_ROOT="/var/www/paserexpress"
UPDATE_DIR="${APP_ROOT}/src/update"
ENV_FILE="${APP_ROOT}/.env"

REPO_URL="https://github.com/azizcool1998/paserexpress.git"
BRANCH="main"

BACKUP_DIR="${APP_ROOT}/src/storage/backups"
mkdir -p "$BACKUP_DIR"

NOW=$(date +"%Y-%m-%d_%H-%M-%S")
BACKUP_FILE="${BACKUP_DIR}/update_backup_${NOW}.tar.gz"

echo "[UPDATE] Backup before update..."
tar -czf "$BACKUP_FILE" \
  -C "$APP_ROOT" src \
  -C "$APP_ROOT" config \
  "$ENV_FILE"

TMP_DIR="/tmp/paserexpress_update"
rm -rf "$TMP_DIR"
git clone --depth=1 -b "$BRANCH" "$REPO_URL" "$TMP_DIR"

echo "[UPDATE] Applying update..."
rsync -av --exclude=".env" "$TMP_DIR/" "$APP_ROOT/"

VERSION=$(git -C "$TMP_DIR" rev-parse --short HEAD)
echo "$VERSION" > "${UPDATE_DIR}/last_version.txt"
echo "$NOW" > "${UPDATE_DIR}/last_update.txt"

rm -rf "$TMP_DIR"

systemctl reload nginx || true
systemctl reload php8.3-fpm || true

echo "[UPDATE] SUCCESS"
