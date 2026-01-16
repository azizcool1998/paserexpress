#!/usr/bin/env bash
set -euo pipefail

APP_ROOT="/var/www/paserexpress"
REPO_URL="git@github.com:azizcool1998/paserexpress.git"
BRANCH="main"

echo "------------------------------------------"
echo " 🚀 PaserExpress Deploy Script"
echo "------------------------------------------"

# ============================
# 1. CHECK DEPENDENCIES
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
# 2. CLONE REPO (FIRST TIME)
# ============================
if [[ ! -d "$APP_ROOT/.git" ]]; then
    echo "📥 Cloning repo for the first time..."
    rm -rf "$APP_ROOT"
    git clone "$REPO_URL" "$APP_ROOT"
else
    echo "🔄 Repo exists, updating..."
fi

cd "$APP_ROOT"

# ============================
# 3. FETCH LATEST VERSION
# ============================
echo "📡 Fetching latest code..."
git fetch --all

echo "🔃 Reset local changes..."
git reset --hard "origin/$BRANCH"

# ============================
# 4. PERMISSIONS FIX
# ============================
echo "🔧 Fixing permissions..."
chown -R www-data:www-data "$APP_ROOT"
find "$APP_ROOT" -type d -exec chmod 755 {} \;
find "$APP_ROOT" -type f -exec chmod 644 {} \;

# ============================
# 5. RELOAD SERVICES
# ============================
echo "🔁 Reloading services..."

systemctl reload nginx || echo "⚠️ Nginx reload failed (check manually)"
systemctl restart php8.3-fpm || echo "⚠️ PHP-FPM restart failed"

echo "------------------------------------------"
echo " ✅ Deployment completed successfully!"
echo "------------------------------------------"
