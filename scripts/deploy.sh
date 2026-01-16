#!/usr/bin/env bash
set -euo pipefail

APP_ROOT="/var/www/paserexpress"
BACKUP_DIR="/var/www/paserexpress_backup_$(date +%Y%m%d_%H%M%S)"
REPO_URL="git@github.com:azizcool1998/paserexpress.git"
BRANCH="main"

echo "------------------------------------------"
echo " 🚀 PaserExpress Deploy Script (with ROLLBACK)"
echo "------------------------------------------"

# ============================
# 0. Helper: Require command
# ============================
require_cmd() {
    command -v "$1" >/dev/null 2>&1 || {
        echo "❌ Error: '$1' command not found. Install it first."
        exit 1
    }
}

require_cmd git
require_cmd systemctl

# ============================
# 1. Backup existing app
# ============================
if [[ -d "$APP_ROOT" ]]; then
    echo "📦 Preparing backup before update..."
    cp -a "$APP_ROOT" "$BACKUP_DIR"
    echo "✔ Backup created: $BACKUP_DIR"
fi

# Function to restore backup on failure
rollback() {
    echo ""
    echo "⚠️ ERROR DETECTED — Starting rollback..."
    
    if [[ -d "$BACKUP_DIR" ]]; then
        rm -rf "$APP_ROOT"
        mv "$BACKUP_DIR" "$APP_ROOT"

        echo "✔ Backup restored successfully."
    else
        echo "❌ No backup available! Rollback skipped."
    fi

    echo "♻ Restarting services after rollback..."
    systemctl reload nginx || true
    systemctl restart php8.3-fpm || true

    echo "❌ Deploy FAILED but rollback SUCCESSFUL."
    exit 1
}

# ============================
# 2. Update repository
# ============================
echo "📡 Fetching latest code..."

set +e
git clone "$REPO_URL" "$APP_ROOT" 2>/dev/null
CLONE_RESULT=$?
set -e

if [[ $CLONE_RESULT -ne 0 ]]; then
    echo "🔄 Repo already exists. Updating..."

    cd "$APP_ROOT" || rollback

    git fetch --all || rollback
    git reset --hard "origin/$BRANCH" || rollback
fi

# ============================
# 3. Fix permissions
# ============================
echo "🔧 Fixing permissions..."

chown -R www-data:www-data "$APP_ROOT" || rollback
find "$APP_ROOT" -type d -exec chmod 755 {} \; || rollback
find "$APP_ROOT" -type f -exec chmod 644 {} \; || rollback

# ============================
# 4. Reload required services
# ============================
echo "🔁 Reloading services..."
systemctl reload nginx || rollback
systemctl restart php8.3-fpm || rollback

# ============================
# 5. Deploy SUCCESS
# ============================
echo "------------------------------------------"
echo " ✅ Deployment completed successfully!"
echo "------------------------------------------"

# Clean leftover backup (optional)
rm -rf "$BACKUP_DIR" || true
