#!/usr/bin/env bash
set -euo pipefail

# ===== Helpers =====
info(){ echo -e "\n[INFO] $*"; }
warn(){ echo -e "\n[WARN] $*"; }
err(){ echo -e "\n[ERR ] $*" >&2; exit 1; }

need_cmd(){ command -v "$1" >/dev/null 2>&1 || err "Command not found: $1"; }

# ===== Preconditions =====
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

if [[ ! -d "src" ]]; then
  err "Folder 'src' tidak ditemukan. Jalankan install.sh dari root repo."
fi

# ===== Input =====
info "=== Setup Domain ==="
read -rp "Domain website (contoh: example.com): " WEB_DOMAIN
read -rp "Tambahkan www? (y/n): " WEB_WWW_YN
WEB_WWW_YN="${WEB_WWW_YN,,}"

read -rp "Domain database (metadata saja, contoh: db.example.com): " DB_DOMAIN
read -rp "Domain Node (opsional, boleh kosong): " NODE_DOMAIN

info "=== Setup Lokasi Deploy ==="
read -rp "Path deploy di server (default: /var/www/${WEB_DOMAIN}): " APP_ROOT
APP_ROOT="${APP_ROOT:-/var/www/${WEB_DOMAIN}}"

info "=== Setup Database (MariaDB) ==="
read -rp "Nama database: " DB_NAME
read -rp "Username database: " DB_USER
read -rsp "Password database: " DB_PASS; echo
read -rp "Email database (metadata): " DB_EMAIL

info "=== Setup Admin Pertama ==="
read -rp "Username admin: " ADMIN_USERNAME
read -rp "First name admin: " ADMIN_FIRST
read -rp "Last name admin: " ADMIN_LAST
read -rp "Email admin: " ADMIN_EMAIL
read -rp "Nomor WhatsApp admin (format 62xxxx): " ADMIN_WA
read -rsp "Password admin: " ADMIN_PASS; echo

read -rp "Administrator? (yes/no, default yes): " ADMIN_FLAG
ADMIN_FLAG="${ADMIN_FLAG:-yes}"
ADMIN_FLAG="${ADMIN_FLAG,,}"
IS_ADMIN=1
if [[ "$ADMIN_FLAG" == "no" ]]; then IS_ADMIN=0; fi

info "=== Optional: HTTPS Let's Encrypt ==="
read -rp "Install SSL (certbot) sekarang? (y/n): " SSL_YN
SSL_YN="${SSL_YN,,}"

# ===== Install packages =====
info "Install paket yang dibutuhkan (nginx, php-fpm, mariadb)..."
sudo apt update
sudo apt -y install nginx mariadb-server \
  php8.3-fpm php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip

sudo systemctl enable --now nginx
sudo systemctl enable --now php8.3-fpm
sudo systemctl enable --now mariadb

# ===== Create app directory =====
info "Menyiapkan folder deploy: ${APP_ROOT}"
sudo mkdir -p "$APP_ROOT"
sudo rsync -a --delete ./ "$APP_ROOT"/

# Permissions: owner user, group www-data (aman untuk nginx membaca)
sudo chown -R "$USER":www-data "$APP_ROOT"
sudo find "$APP_ROOT" -type d -exec chmod 2755 {} \;
sudo find "$APP_ROOT" -type f -exec chmod 0644 {} \;
sudo chmod +x "$APP_ROOT/install.sh" || true

# ===== Generate .env =====
info "Generate .env"
ENV_PATH="$APP_ROOT/.env"
APP_NAME="My Website"

cat > "$ENV_PATH" <<EOF
APP_NAME="${APP_NAME}"
APP_ENV=production
APP_DEBUG=false
APP_BASE_URL=https://${WEB_DOMAIN}

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=${DB_NAME}
DB_USER=${DB_USER}
DB_PASS=${DB_PASS}
DB_DOMAIN=${DB_DOMAIN}
DB_EMAIL=${DB_EMAIL}

NODE_DOMAIN=${NODE_DOMAIN}

ADMIN_USERNAME=${ADMIN_USERNAME}
ADMIN_PASSWORD=${ADMIN_PASS}
ADMIN_EMAIL=${ADMIN_EMAIL}
ADMIN_FIRST_NAME=${ADMIN_FIRST}
ADMIN_LAST_NAME=${ADMIN_LAST}
ADMIN_WHATSAPP=${ADMIN_WA}
EOF

chmod 600 "$ENV_PATH"

# ===== Setup MariaDB database/user =====
info "Setup MariaDB: create database + user"
need_cmd mysql

# Create DB and user using sudo mysql (root via unix_socket)
sudo mysql -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
sudo mysql -e "GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost'; FLUSH PRIVILEGES;"

# Import schema
info "Import schema.sql"
SCHEMA="$APP_ROOT/config/schema.sql"
if [[ ! -f "$SCHEMA" ]]; then
  err "Schema tidak ditemukan: $SCHEMA"
fi
mysql -u"${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" < "$SCHEMA"

# ===== Seed admin =====
info "Membuat admin pertama"
need_cmd php
php "$APP_ROOT/src/cli/seed_admin.php" \
  --username="${ADMIN_USERNAME}" \
  --password="${ADMIN_PASS}" \
  --email="${ADMIN_EMAIL}" \
  --first="${ADMIN_FIRST}" \
  --last="${ADMIN_LAST}" \
  --wa="${ADMIN_WA}" \
  --role="admin" \
  --is_admin="${IS_ADMIN}"

# ===== Nginx site config =====
info "Konfigurasi Nginx site"
SITE_CONF="/etc/nginx/sites-available/${WEB_DOMAIN}.conf"
ROOT_PUBLIC="${APP_ROOT}/src/public"

SERVER_NAMES="${WEB_DOMAIN}"
if [[ "$WEB_WWW_YN" == "y" ]]; then
  SERVER_NAMES="${SERVER_NAMES} www.${WEB_DOMAIN}"
fi

sudo tee "$SITE_CONF" >/dev/null <<NGINX
server {
    listen 80;
    listen [::]:80;

    server_name ${SERVER_NAMES};

    root ${ROOT_PUBLIC};
    index index.php;

    access_log /var/log/nginx/${WEB_DOMAIN}.access.log;
    error_log  /var/log/nginx/${WEB_DOMAIN}.error.log;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    client_max_body_size 20m;

    location ~* \\.(?:css|js|jpg|jpeg|png|gif|svg|ico|webp|woff2|woff|ttf)\$ {
        expires 7d;
        add_header Cache-Control "public, max-age=604800";
        try_files \$uri =404;
    }

    location ~ /\\.(?!well-known) { deny all; }
    location ~* \\.(?:env|ini|log|sql|bak|swp)\$ { deny all; }

    location ^~ /api/ {
        rewrite ^/api/(.+)\$ /../api/\$1.php last;
    }

    location / {
        try_files \$uri /index.php?\$query_string;
    }

    location ~ \\.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        fastcgi_hide_header X-Powered-By;
    }
}
NGINX

sudo ln -sf "$SITE_CONF" "/etc/nginx/sites-enabled/${WEB_DOMAIN}.conf"
sudo rm -f /etc/nginx/sites-enabled/default || true

sudo nginx -t
sudo systemctl reload nginx

# ===== SSL (optional) =====
if [[ "$SSL_YN" == "y" ]]; then
  info "Install & setup Certbot SSL"
  sudo apt -y install certbot python3-certbot-nginx
  if [[ "$WEB_WWW_YN" == "y" ]]; then
    sudo certbot --nginx -d "${WEB_DOMAIN}" -d "www.${WEB_DOMAIN}"
  else
    sudo certbot --nginx -d "${WEB_DOMAIN}"
  fi
fi

info "=== SELESAI ==="
echo "Website: http://${WEB_DOMAIN} (atau https jika SSL dipasang)"
echo "Admin login: /?page=login"
echo "Admin panel: /?page=admin_dashboard"
echo "Nginx error log: /var/log/nginx/${WEB_DOMAIN}.error.log"
