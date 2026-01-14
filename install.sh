#!/usr/bin/env bash
set -euo pipefail

# ===== Helpers =====
info(){ echo -e "\n[INFO] $*"; }
warn(){ echo -e "\n[WARN] $*"; }
err(){ echo -e "\n[ERR ] $*" >&2; exit 1; }

need_cmd(){ command -v "$1" >/dev/null 2>&1 || err "Command not found: $1"; }

trim(){ echo -n "$1" | xargs; }

# Accept: y/yes/1/true/on  | n/no/0/false/off
ask_yn() {
  local prompt="$1"
  local default="${2:-y}"  # y or n
  local ans
  while true; do
    read -rp "${prompt} (y/n, default: ${default}): " ans
    ans="$(trim "${ans,,}")"
    if [[ -z "$ans" ]]; then echo "$default"; return 0; fi
    case "$ans" in
      y|yes|1|true|on) echo "y"; return 0 ;;
      n|no|0|false|off) echo "n"; return 0 ;;
      *) warn "Input tidak valid. Pakai y/n atau yes/no." ;;
    esac
  done
}

# returns "yes" or "no"
ask_yesno_word() {
  local prompt="$1"
  local default_word="${2:-yes}" # yes/no
  local default_letter="y"
  [[ "${default_word,,}" == "no" ]] && default_letter="n"
  local yn
  yn="$(ask_yn "$prompt" "$default_letter")"
  [[ "$yn" == "y" ]] && echo "yes" || echo "no"
}

ask_nonempty() {
  local prompt="$1"
  local default="${2:-}"
  local v
  while true; do
    if [[ -n "$default" ]]; then
      read -rp "${prompt} (default: ${default}): " v
      v="${v:-$default}"
    else
      read -rp "${prompt}: " v
    fi
    v="$(trim "$v")"
    [[ -n "$v" ]] && { echo "$v"; return 0; }
    warn "Tidak boleh kosong."
  done
}

ask_secret() {
  local prompt="$1"
  local v
  while true; do
    read -rsp "${prompt}: " v; echo
    v="$(trim "$v")"
    [[ -n "$v" ]] && { echo "$v"; return 0; }
    warn "Tidak boleh kosong."
  done
}

# ===== Preconditions =====
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

[[ -d "src" ]] || err "Folder 'src' tidak ditemukan. Jalankan install.sh dari root repo."

# ===== Input =====
info "=== Setup Domain ==="
WEB_DOMAIN="$(ask_nonempty "Domain website (contoh: example.com)")"
DB_DOMAIN="$(ask_nonempty "Domain database (metadata saja, contoh: db.example.com)")"
read -rp "Domain Node (opsional, boleh kosong) (default kosong): " NODE_DOMAIN
NODE_DOMAIN="$(trim "${NODE_DOMAIN:-}")"

info "=== Setup Lokasi Deploy ==="
APP_ROOT="$(ask_nonempty "Path deploy di server" "/var/www/${WEB_DOMAIN}")"

info "=== Setup Database (MariaDB) ==="
DB_NAME="$(ask_nonempty "Nama database")"
DB_USER="$(ask_nonempty "Username database")"
DB_PASS="$(ask_secret "Password database")"
DB_EMAIL="$(ask_nonempty "Email database (metadata)")"

info "=== Setup Admin Pertama ==="
ADMIN_USERNAME="$(ask_nonempty "Username admin")"
ADMIN_FIRST="$(ask_nonempty "First name admin")"
ADMIN_LAST="$(ask_nonempty "Last name admin")"
ADMIN_EMAIL="$(ask_nonempty "Email admin")"
ADMIN_WA="$(ask_nonempty "Nomor WhatsApp admin (format 62xxxx)")"
ADMIN_PASS="$(ask_secret "Password admin")"

# default YES jika Enter
ADMIN_FLAG="$(ask_yesno_word "Administrator? (yes/no)" "yes")"
IS_ADMIN=1
[[ "${ADMIN_FLAG,,}" == "no" ]] && IS_ADMIN=0

info "=== Opsional Otomatisasi ==="
AUTO_UFW="$(ask_yn "Auto configure UFW (firewall) untuk website sesuai konfigurasi?" "y")"
AUTO_HTTPS="$(ask_yn "Auto configure HTTPS using Let's Encrypt?" "y")"
TELEMETRY="$(ask_yesno_word "Enable sending anonymous telemetry data?" "yes")"

# ===== Assume SSL false at start (sesuai permintaan) =====
# Kita set .env awal pakai http. Kalau AUTO_HTTPS=y dan certbot sukses, nanti diubah ke https.
ASSUME_SSL="false"
if [[ "$AUTO_UFW" == "y" && "$AUTO_HTTPS" == "y" ]]; then
  ASSUME_SSL="false"
fi

# ===== Review + pre-check + confirm =====
info "=== REVIEW KONFIGURASI ==="
cat <<EOF
Domain website        : $WEB_DOMAIN
Domain database       : $DB_DOMAIN
Domain node (opsional): ${NODE_DOMAIN:-"(kosong)"}

Path deploy           : $APP_ROOT

DB name               : $DB_NAME
DB user               : $DB_USER
DB pass               : (disembunyikan)
DB email              : $DB_EMAIL

Admin username        : $ADMIN_USERNAME
Admin nama            : $ADMIN_FIRST $ADMIN_LAST
Admin email           : $ADMIN_EMAIL
Admin whatsapp        : $ADMIN_WA
Admin password        : (disembunyikan)
Administrator         : $ADMIN_FLAG

Auto UFW              : $AUTO_UFW
Auto HTTPS            : $AUTO_HTTPS
Assume SSL (awal)     : $ASSUME_SSL
Telemetry             : $TELEMETRY
EOF

CONTINUE="$(ask_yn "Lanjutkan installation dengan konfigurasi ini?" "n")"
[[ "$CONTINUE" == "y" ]] || err "Dibatalkan oleh user."

# ===== Install packages =====
info "Install paket yang dibutuhkan (nginx, php-fpm, mariadb)..."
sudo apt update
sudo apt -y install nginx mariadb-server \
  php8.3-fpm php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip rsync curl

sudo systemctl enable --now nginx
sudo systemctl enable --now php8.3-fpm
sudo systemctl enable --now mariadb

# ===== Auto UFW =====
if [[ "$AUTO_UFW" == "y" ]]; then
  info "Auto configure UFW..."
  sudo apt -y install ufw
  sudo ufw allow OpenSSH >/dev/null || true

  if [[ "$AUTO_HTTPS" == "y" ]]; then
    sudo ufw allow 'Nginx Full' >/dev/null || true
  else
    sudo ufw allow 'Nginx HTTP' >/dev/null || true
  fi

  # enable only if not active
  if ! sudo ufw status | grep -q "Status: active"; then
    sudo ufw --force enable
  fi
  sudo ufw status
fi

# ===== Create app directory =====
info "Menyiapkan folder deploy: ${APP_ROOT}"
sudo mkdir -p "$APP_ROOT"
sudo rsync -a --delete ./ "$APP_ROOT"/

sudo chown -R "$USER":www-data "$APP_ROOT"
sudo find "$APP_ROOT" -type d -exec chmod 2755 {} \;
sudo find "$APP_ROOT" -type f -exec chmod 0644 {} \;
sudo chmod +x "$APP_ROOT/install.sh" || true

# ===== Generate .env (ASSUME SSL FALSE => http) =====
info "Generate .env"
ENV_PATH="$APP_ROOT/.env"
APP_NAME="My Website"

cat > "$ENV_PATH" <<EOF
APP_NAME="${APP_NAME}"
APP_ENV=production
APP_DEBUG=false
APP_BASE_URL=http://${WEB_DOMAIN}

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

TELEMETRY_ENABLED=${TELEMETRY}
EOF

chmod 600 "$ENV_PATH"

# ===== Setup MariaDB database/user =====
info "Setup MariaDB: create database + user"
need_cmd mysql

sudo mysql -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
sudo mysql -e "GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost'; FLUSH PRIVILEGES;"

# Import schema
info "Import schema.sql"
SCHEMA="$APP_ROOT/config/schema.sql"
[[ -f "$SCHEMA" ]] || err "Schema tidak ditemukan: $SCHEMA"
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

# ===== Nginx site config (HTTP dulu; HTTPS akan diurus certbot jika dipilih) =====
info "Konfigurasi Nginx site (HTTP)"
SITE_CONF="/etc/nginx/sites-available/${WEB_DOMAIN}.conf"
ROOT_PUBLIC="${APP_ROOT}/src/public"

sudo tee "$SITE_CONF" >/dev/null <<NGINX
server {
    listen 80;
    listen [::]:80;

    server_name ${WEB_DOMAIN};

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

# ===== Auto HTTPS (Let's Encrypt) =====
if [[ "$AUTO_HTTPS" == "y" ]]; then
  info "Auto configure HTTPS (Let's Encrypt via certbot)..."
  sudo apt -y install certbot python3-certbot-nginx

  # certbot akan menambah server block 443 dan (biasanya) redirect http->https
  sudo certbot --nginx -d "${WEB_DOMAIN}"

  # Update .env ke https setelah certbot sukses
  sudo sed -i "s|^APP_BASE_URL=http://${WEB_DOMAIN}\$|APP_BASE_URL=https://${WEB_DOMAIN}|g" "$ENV_PATH"

  sudo nginx -t
  sudo systemctl reload nginx
fi

info "=== SELESAI ==="
echo "Website: https://${WEB_DOMAIN} (jika HTTPS aktif) atau http://${WEB_DOMAIN}"
echo "Admin login: /?page=login"
echo "Admin panel: /?page=admin_dashboard"
echo "Nginx error log: /var/log/nginx/${WEB_DOMAIN}.error.log"
