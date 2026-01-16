#!/usr/bin/env bash
set -euo pipefail

# =========================
# PaserExpress Installer FINAL (Part 1)
# =========================

info(){ echo -e "\n[INFO] $*"; }
warn(){ echo -e "\n[WARN] $*"; }
err(){ echo -e "\n[ERR ] $*" >&2; exit 1; }

trim(){ echo -n "$1" | xargs; }

need_root(){
  if [[ "${EUID:-$(id -u)}" -ne 0 ]]; then
    err "Jalankan sebagai ROOT. Gunakan: sudo bash <(curl -fsSL ...)"
  fi
}

need_cmd(){ command -v "$1" >/dev/null 2>&1 || err "Command not found: $1"; }

ask_yn() {
  local prompt="$1"
  local default="${2:-y}"
  local ans
  while true; do
    read -rp "${prompt} (y/n, default: ${default}): " ans
    ans="$(trim "${ans,,}")"
    if [[ -z "$ans" ]]; then echo "$default"; return 0; fi
    case "$ans" in
      y|yes|1|true) echo "y"; return 0 ;;
      n|no|0|false) echo "n"; return 0 ;;
      *) warn "Input tidak valid. Gunakan y/n." ;;
    esac
  done
}

ask_secret() {
  local prompt="$1"
  local v
  while true; do
    read -rsp "${prompt}: " v; echo
    v="$(trim "$v")"
    [[ -n "$v" ]] && { echo "$v"; return; }
    warn "Tidak boleh kosong."
  done
}

# =========================
# DOMAIN VALIDATION FINAL
# =========================

valid_domain_regex='^([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}$'

ask_domain() {
  local prompt="$1"
  local domain=""

  while true; do
    read -rp "${prompt}: " domain
    domain="$(trim "$domain")"

    if [[ -z "$domain" ]]; then
      warn "Domain tidak boleh kosong."
      continue
    fi

    if [[ "$domain" =~ $valid_domain_regex ]]; then
      echo "$domain"
      return 0
    else
      warn "Format domain salah!"
      warn "Contoh domain valid:"
      warn "  example.com"
      warn "  api.example.com"
      warn "  web.aziztech.us"
    fi
  done
}
# =========================
# SSHD PORT CONFIG
# =========================

configure_sshd_ports(){
  local p1="$1"
  local p2="$2"
  local p3="$3"

  local dropin="/etc/ssh/sshd_config.d/99-paserexpress.conf"
  info "Menulis SSHD drop-in: ${dropin}"

  mkdir -p /etc/ssh/sshd_config.d
  cat > "$dropin" <<EOF
Port ${p1}
Port ${p2}
Port ${p3}
EOF

  sshd -t || err "Konfigurasi SSH salah! Tidak jadi restart."
  systemctl restart ssh || systemctl restart sshd
}

# =========================
# FIREWALL (UFW)
# =========================

configure_ufw(){
  local want_https="$1"
  local s1="$2"
  local s2="$3"
  local s3="$4"

  apt-get install -y ufw

  ufw allow "${s1}/tcp"
  ufw allow "${s2}/tcp"
  ufw allow "${s3}/tcp"
  ufw allow 80/tcp

  [[ "$want_https" == "y" ]] && ufw allow 443/tcp

  ufw --force enable
}

# =========================
# Nginx Snippets
# =========================

write_headers_snippet(){
  mkdir -p /etc/nginx/snippets
  cat >/etc/nginx/snippets/paserexpress-headers.conf <<'EOF'
add_header X-Frame-Options "SAMEORIGIN";
add_header X-Content-Type-Options "nosniff";
add_header Referrer-Policy "strict-origin-when-cross-origin";
EOF
}

write_hsts_snippet(){
  mkdir -p /etc/nginx/snippets
  cat >/etc/nginx/snippets/paserexpress-hsts.conf <<'EOF'
add_header Strict-Transport-Security "max-age=31536000" always;
EOF
}

# =========================
# NGINX HTTP SITE
# =========================

write_nginx_http_site(){
  local domain="$1"
  local app_root="$2"
  local conf="/etc/nginx/sites-available/${domain}.conf"
  local pub="${app_root}/src/public"

  cat > "$conf" <<NGINX
server {
    listen 80;
    server_name ${domain};

    root ${pub};
    index index.php index.html;

    include /etc/nginx/snippets/paserexpress-headers.conf;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \\.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }
}
NGINX

  ln -sf "$conf" /etc/nginx/sites-enabled/"${domain}.conf"
  rm -f /etc/nginx/sites-enabled/default || true
}

# =========================
# ENV WRITER
# =========================

write_env(){
  local app="$1"
  local web="$2"
  local dbn="$3"
  local dbu="$4"
  local dbp="$5"
  local dbdom="$6"
  local dbmail="$7"
  local node="$8"
  local admu="$9"
  local admp="${10}"
  local adme="${11}"
  local adf="${12}"
  local adl="${13}"
  local adw="${14}"
  local tele="${15}"

  cat > "${app}/.env" <<EOF
APP_NAME="Paser Express"
APP_ENV=production
APP_DEBUG=false

APP_BASE_URL=http://${web}

DB_HOST=127.0.0.1
DB_NAME=${dbn}
DB_USER=${dbu}
DB_PASS=${dbp}
DB_EMAIL=${dbmail}
DB_DOMAIN=${dbdom}

NODE_DOMAIN=${node}

ADMIN_USERNAME=${admu}
ADMIN_PASSWORD=${admp}
ADMIN_EMAIL=${adme}
ADMIN_FIRST_NAME=${adf}
ADMIN_LAST_NAME=${adl}
ADMIN_WHATSAPP=${adw}

TELEMETRY_ENABLED=${tele}
EOF

  chmod 600 "${app}/.env"
}
deploy_app(){
  local root="$1"
  local repo="$2"
  local branch="$3"

  if [[ -d "$root/.git" ]]; then
    (cd "$root" && git fetch --all && git reset --hard "origin/${branch}")
  else
    rm -rf "$root"
    git clone --depth 1 -b "$branch" "$repo" "$root"
  fi
}

setup_db(){
  local app="$1"
  local name="$2"
  local user="$3"
  local pass="$4"

  mysql -e "CREATE DATABASE IF NOT EXISTS \`${name}\`;"
  mysql -e "CREATE USER IF NOT EXISTS '${user}'@'localhost' IDENTIFIED BY '${pass}';"
  mysql -e "GRANT ALL PRIVILEGES ON \`${name}\`.* TO '${user}'@'localhost'; FLUSH PRIVILEGES;"

  mysql -u"$user" -p"$pass" "$name" < "${app}/config/schema.sql"
}

seed_admin(){
  local root="$1"
  local u="$2"
  local p="$3"
  local e="$4"
  local f="$5"
  local l="$6"
  local w="$7"

  php "${root}/src/cli/seed_admin.php" \
    --username="$u" \
    --password="$p" \
    --email="$e" \
    --first="$f" \
    --last="$l" \
    --wa="$w"
}

setup_https(){
  local domain="$1"
  local email="$2"

  apt-get install -y certbot python3-certbot-nginx

  certbot --nginx -d "$domain" \
    --non-interactive --agree-tos -m "$email" || return 1

  return 0
}
# =========================
# MAIN INSTALLER
# =========================

need_root

SSH_P1="22"
SSH_P2="9898"
SSH_P3="$(ask_domain 'Masukkan port SSH ke-3 (1024-65535)')"

WEB_DOMAIN="$(ask_domain 'Masukkan domain WEBSITE')"
DB_DOMAIN="$(ask_domain 'Masukkan domain DATABASE')"
NODE_DOMAIN="$(ask_domain 'Masukkan domain NODE')"

APP_ROOT="/var/www/paserexpress"

DB_NAME="paserexpress"
DB_USER="paser_user"
DB_PASS="$(ask_secret 'Password database')"
DB_EMAIL="admin@${DB_DOMAIN}"

ADMIN_USERNAME="admin"
ADMIN_FIRST="Admin"
ADMIN_LAST="Paser"
ADMIN_EMAIL="admin@${WEB_DOMAIN}"
ADMIN_WA="62000000"
ADMIN_PASS="$(ask_secret 'Password admin')"

AUTO_HTTPS="$(ask_yn 'Enable HTTPS otomatis?' 'y')"
TELEMETRY="yes"

apt-get update -y
apt-get install -y nginx git php8.3-fpm php8.3-mysql mariadb-server

configure_sshd_ports "$SSH_P1" "$SSH_P2" "$SSH_P3"
write_headers_snippet
write_hsts_snippet

deploy_app "$APP_ROOT" "https://github.com/azizcool1998/paserexpress.git" "main"

write_env "$APP_ROOT" "$WEB_DOMAIN" "$DB_NAME" "$DB_USER" "$DB_PASS" "$DB_DOMAIN" "$DB_EMAIL" "$NODE_DOMAIN" \
  "$ADMIN_USERNAME" "$ADMIN_PASS" "$ADMIN_EMAIL" "$ADMIN_FIRST" "$ADMIN_LAST" "$ADMIN_WA" "$TELEMETRY"

setup_db "$APP_ROOT" "$DB_NAME" "$DB_USER" "$DB_PASS"

seed_admin "$APP_ROOT" "$ADMIN_USERNAME" "$ADMIN_PASS" "$ADMIN_EMAIL" "$ADMIN_FIRST" "$ADMIN_LAST" "$ADMIN_WA"

write_nginx_http_site "$WEB_DOMAIN" "$APP_ROOT"
systemctl reload nginx

if [[ "$AUTO_HTTPS" == "y" ]]; then
  setup_https "$WEB_DOMAIN" "$ADMIN_EMAIL" && systemctl reload nginx
fi

info "=== INSTALASI SELESAI ==="
echo "Website: http://${WEB_DOMAIN}"
echo "Admin Login: http://${WEB_DOMAIN}/?page=login"
