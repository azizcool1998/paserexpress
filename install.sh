#!/usr/bin/env bash
set -euo pipefail

# =========================
# PaserExpress Full Installer (single-file)
# Run:
#   sudo bash <(curl -fsSL https://raw.githubusercontent.com/azizcool1998/paserexpress/main/install.sh)
# =========================

info(){ echo -e "\n[INFO] $*"; }
warn(){ echo -e "\n[WARN] $*"; }
err(){ echo -e "\n[ERR ] $*" >&2; exit 1; }

trim(){ echo -n "$1" | xargs; }

need_root(){
  if [[ "${EUID:-$(id -u)}" -ne 0 ]]; then
    err "Jalankan sebagai root. Contoh: sudo bash <(curl -fsSL https://raw.githubusercontent.com/azizcool1998/paserexpress/main/install.sh)"
  fi
}

need_cmd(){ command -v "$1" >/dev/null 2>&1 || err "Command not found: $1"; }

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

backup_file(){
  local f="$1"
  if [[ -f "$f" ]]; then
    cp -a "$f" "${f}.bak.$(date +%F_%H%M%S)"
  fi
}

check_os(){
  if [[ -f /etc/os-release ]]; then
    . /etc/os-release
    if [[ "${ID:-}" != "ubuntu" ]]; then
      warn "OS bukan Ubuntu (ID=${ID:-unknown}). Script tetap mencoba jalan."
    fi
  fi
}

apt_install(){
  local pkgs=("$@")
  DEBIAN_FRONTEND=noninteractive apt-get update -y
  DEBIAN_FRONTEND=noninteractive apt-get install -y "${pkgs[@]}"
}

configure_ufw(){
  local want_https="$1" # y/n
  info "Auto configure UFW..."
  apt_install ufw
  ufw allow OpenSSH >/dev/null 2>&1 || true
  if [[ "$want_https" == "y" ]]; then
    ufw allow 'Nginx Full' >/dev/null 2>&1 || true
  else
    ufw allow 'Nginx HTTP' >/dev/null 2>&1 || true
  fi
  if ! ufw status | grep -q "Status: active"; then
    ufw --force enable
  fi
  ufw status || true
}

write_headers_snippet(){
  local snippet="/etc/nginx/snippets/paserexpress-headers.conf"
  info "Menulis headers snippet: ${snippet}"
  mkdir -p /etc/nginx/snippets
  cat > "$snippet" <<'HDR'
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
add_header Permissions-Policy "geolocation=(), microphone=(), camera=()" always;
HDR
}

write_hsts_snippet(){
  local snippet="/etc/nginx/snippets/paserexpress-hsts.conf"
  info "Menulis HSTS snippet (HTTPS only): ${snippet}"
  mkdir -p /etc/nginx/snippets
  cat > "$snippet" <<'HDR'
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
HDR
}

write_nginx_http_site(){
  local domain="$1"
  local app_root="$2"
  local site_conf="/etc/nginx/sites-available/${domain}.conf"
  local public_root="${app_root}/src/public"

  info "Menulis Nginx config HTTP: ${site_conf}"
  backup_file "$site_conf"

  cat > "$site_conf" <<NGINX
server {
    listen 80;
    listen [::]:80;

    server_name ${domain};

    root ${public_root};
    index index.php index.html;

    access_log /var/log/nginx/paserexpress.access.log;
    error_log  /var/log/nginx/paserexpress.error.log;

    include /etc/nginx/snippets/paserexpress-headers.conf;

    client_max_body_size 20m;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \\.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\\.(?!well-known) { deny all; }
    location ~* \\.(?:env|ini|log|sql|bak|swp)\$ { deny all; }

    location ~* \\.(?:css|js|jpg|jpeg|png|gif|svg|ico|webp|woff2|woff|ttf)\$ {
        expires 7d;
        add_header Cache-Control "public, max-age=604800";
        try_files \$uri =404;
    }
}
NGINX

  ln -sf "$site_conf" "/etc/nginx/sites-enabled/${domain}.conf"
  rm -f /etc/nginx/sites-enabled/default || true
}

reload_nginx(){
  nginx -t
  systemctl reload nginx
}

write_env(){
  local app_root="$1"
  local domain="$2"
  local db_name="$3"
  local db_user="$4"
  local db_pass="$5"
  local db_domain="$6"
  local db_email="$7"
  local node_domain="$8"
  local admin_user="$9"
  local admin_pass="${10}"
  local admin_email="${11}"
  local admin_first="${12}"
  local admin_last="${13}"
  local admin_wa="${14}"
  local telemetry="${15}"

  local env_path="${app_root}/.env"
  info "Menulis .env: ${env_path}"
  mkdir -p "$app_root"

  cat > "$env_path" <<EOF
APP_NAME="Paser Express"
APP_ENV=production
APP_DEBUG=false
APP_BASE_URL=http://${domain}

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=${db_name}
DB_USER=${db_user}
DB_PASS=${db_pass}
DB_DOMAIN=${db_domain}
DB_EMAIL=${db_email}

NODE_DOMAIN=${node_domain}

ADMIN_USERNAME=${admin_user}
ADMIN_PASSWORD=${admin_pass}
ADMIN_EMAIL=${admin_email}
ADMIN_FIRST_NAME=${admin_first}
ADMIN_LAST_NAME=${admin_last}
ADMIN_WHATSAPP=${admin_wa}

TELEMETRY_ENABLED=${telemetry}
EOF

  chmod 600 "$env_path"
}

switch_env_to_https(){
  local app_root="$1"
  local domain="$2"
  local env_path="${app_root}/.env"
  if [[ -f "$env_path" ]]; then
    sed -i "s|^APP_BASE_URL=http://${domain}\$|APP_BASE_URL=https://${domain}|g" "$env_path" || true
  fi
}

setup_db(){
  local app_root="$1"
  local db_name="$2"
  local db_user="$3"
  local db_pass="$4"
  local schema="${app_root}/config/schema.sql"

  [[ -f "$schema" ]] || err "Schema tidak ditemukan: $schema"

  info "Membuat database & user MariaDB..."
  mysql -e "CREATE DATABASE IF NOT EXISTS \`${db_name}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
  mysql -e "CREATE USER IF NOT EXISTS '${db_user}'@'localhost' IDENTIFIED BY '${db_pass}';"
  mysql -e "GRANT ALL PRIVILEGES ON \`${db_name}\`.* TO '${db_user}'@'localhost'; FLUSH PRIVILEGES;"

  info "Import schema.sql..."
  mysql -u"${db_user}" -p"${db_pass}" "${db_name}" < "$schema"
}

ensure_seed_admin(){
  local app_root="$1"
  local seed="${app_root}/src/cli/seed_admin.php"

  if [[ -f "$seed" ]]; then
    return 0
  fi

  info "seed_admin.php tidak ditemukan, membuat file default..."
  mkdir -p "${app_root}/src/cli"
  cat > "$seed" <<'PHP'
<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';

$args = [];
foreach ($argv as $a) {
    if (str_starts_with($a, '--') && str_contains($a, '=')) {
        [$k, $v] = explode('=', substr($a, 2), 2);
        $args[$k] = $v;
    }
}

$username = trim($args['username'] ?? '');
$password = (string)($args['password'] ?? '');
$email    = trim($args['email'] ?? '');
$first    = trim($args['first'] ?? '');
$last     = trim($args['last'] ?? '');
$wa       = trim($args['wa'] ?? '');
$role     = trim($args['role'] ?? 'admin');
$is_admin = (int)($args['is_admin'] ?? 1);

if ($username==='' || $password==='' || $email==='' || $first==='' || $last==='' || $wa==='') {
    fwrite(STDERR, "Missing required fields.\n");
    exit(1);
}

$validRoles = ['pelanggan','driver','admin'];
if (!in_array($role, $validRoles, true)) $role = 'admin';

$pdo = db();
$pdo->beginTransaction();

try {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username=? OR email=? LIMIT 1");
    $stmt->execute([$username, $email]);
    $exists = $stmt->fetch();

    $hash = password_hash($password, PASSWORD_DEFAULT);

    if ($exists) {
        $id = (int)$exists['id'];
        $stmt = $pdo->prepare("UPDATE users
            SET username=?, first_name=?, last_name=?, email=?, whatsapp=?, password_hash=?, role=?, is_admin=?, is_active=1
            WHERE id=?");
        $stmt->execute([$username,$first,$last,$email,$wa,$hash,$role,$is_admin,$id]);
        echo "Admin updated (id={$id}).\n";
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (username, first_name, last_name, email, whatsapp, password_hash, role, is_admin, is_active)
            VALUES (?,?,?,?,?,?,?,?,1)");
        $stmt->execute([$username,$first,$last,$email,$wa,$hash,$role,$is_admin]);
        $id = (int)$pdo->lastInsertId();
        echo "Admin created (id={$id}).\n";
    }

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "Error: ".$e->getMessage()."\n");
    exit(1);
}
PHP
  chmod 644 "$seed"
}

seed_admin(){
  local app_root="$1"
  local admin_user="$2"
  local admin_pass="$3"
  local admin_email="$4"
  local admin_first="$5"
  local admin_last="$6"
  local admin_wa="$7"
  local is_admin="$8"

  ensure_seed_admin "$app_root"
  info "Seeding admin..."
  php "${app_root}/src/cli/seed_admin.php" \
    --username="${admin_user}" \
    --password="${admin_pass}" \
    --email="${admin_email}" \
    --first="${admin_first}" \
    --last="${admin_last}" \
    --wa="${admin_wa}" \
    --role="admin" \
    --is_admin="${is_admin}"
}

deploy_app(){
  local app_root="$1"
  local repo_url="$2"
  local branch="$3"

  info "Deploy aplikasi ke: ${app_root}"

  if [[ -d "${app_root}/.git" ]]; then
    info "Repo sudah ada, update..."
    (cd "$app_root" && git fetch --all --prune && git reset --hard "origin/${branch}")
  else
    rm -rf "$app_root"
    git clone --depth 1 -b "$branch" "$repo_url" "$app_root"
  fi

  # Permissions: www-data can read/run; .env will be 600
  chown -R www-data:www-data "$app_root"
  find "$app_root" -type d -exec chmod 755 {} \;
  find "$app_root" -type f -exec chmod 644 {} \;
}

setup_https(){
  local domain="$1"
  local email_for_cert="$2"

  info "Setup HTTPS Let's Encrypt untuk ${domain}..."
  apt_install certbot python3-certbot-nginx

  systemctl enable --now nginx

  # use provided email for certbot, fallback to admin@domain
  local email="${email_for_cert:-admin@${domain}}"

  certbot --nginx -d "$domain" --non-interactive --agree-tos -m "$email" || {
    warn "Certbot gagal. Cek DNS A record domain mengarah ke IP VPS dan port 80/443 terbuka."
    return 1
  }

  certbot renew --dry-run || true
  return 0
}

enable_https_headers_on_site(){
  local domain="$1"
  local site_conf="/etc/nginx/sites-available/${domain}.conf"

  # Inject header includes inside server 443 (certbot-managed block)
  if [[ -f "$site_conf" ]] && grep -q "listen 443" "$site_conf"; then
    if ! grep -q "snippets/paserexpress-headers.conf" "$site_conf"; then
      sed -i '/listen 443/a \ \ \ \ include /etc/nginx/snippets/paserexpress-headers.conf;' "$site_conf"
    fi
    if ! grep -q "snippets/paserexpress-hsts.conf" "$site_conf"; then
      sed -i '/listen 443/a \ \ \ \ include /etc/nginx/snippets/paserexpress-hsts.conf;' "$site_conf"
    fi
  fi
}

# =========================
# MAIN
# =========================
need_root
check_os

info "=== PaserExpress Full Installer ==="

WEB_DOMAIN="$(ask_nonempty "Domain website (contoh: a.aziztech.us)")"
DB_DOMAIN="$(ask_nonempty "Domain database (metadata saja)")"
read -rp "Domain Node (opsional, boleh kosong) (default kosong): " NODE_DOMAIN
NODE_DOMAIN="$(trim "${NODE_DOMAIN:-}")"

APP_ROOT="$(ask_nonempty "Path deploy di server" "/var/www/paserexpress")"

DEFAULT_APP_REPO="https://github.com/azizcool1998/paserexpress.git"
APP_REPO_URL="$(ask_nonempty "Git repo URL untuk source app" "$DEFAULT_APP_REPO")"
APP_REPO_BRANCH="$(ask_nonempty "Branch repo app" "main")"

DB_NAME="$(ask_nonempty "Nama database")"
DB_USER="$(ask_nonempty "Username database")"
DB_PASS="$(ask_secret "Password database")"
DB_EMAIL="$(ask_nonempty "Email database (metadata)")"

ADMIN_USERNAME="$(ask_nonempty "Username admin")"
ADMIN_FIRST="$(ask_nonempty "First name admin")"
ADMIN_LAST="$(ask_nonempty "Last name admin")"
ADMIN_EMAIL="$(ask_nonempty "Email admin")"
ADMIN_WA="$(ask_nonempty "Nomor WhatsApp admin (format 62xxxx)")"
ADMIN_PASS="$(ask_secret "Password admin")"

ADMIN_FLAG="$(ask_yesno_word "Administrator? (yes/no)" "yes")"
IS_ADMIN=1
[[ "${ADMIN_FLAG,,}" == "no" ]] && IS_ADMIN=0

AUTO_UFW="$(ask_yn "Auto configure UFW (firewall) untuk website sesuai konfigurasi?" "y")"
AUTO_HTTPS="$(ask_yn "Auto configure HTTPS using Let's Encrypt?" "y")"
TELEMETRY="$(ask_yesno_word "Enable sending anonymous telemetry data?" "yes")"

ASSUME_SSL="false"
if [[ "$AUTO_UFW" == "y" && "$AUTO_HTTPS" == "y" ]]; then
  ASSUME_SSL="false"
fi

info "=== REVIEW KONFIGURASI ==="
cat <<EOF
Domain website        : $WEB_DOMAIN
Domain database       : $DB_DOMAIN
Domain node (opsional): ${NODE_DOMAIN:-"(kosong)"}

Deploy path           : $APP_ROOT
Repo app              : $APP_REPO_URL
Branch                : $APP_REPO_BRANCH

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

CONTINUE="$(ask_yn "Konfirmasi: continue installation?" "n")"
[[ "$CONTINUE" == "y" ]] || err "Dibatalkan oleh user."

info "Install paket inti..."
apt_install nginx git curl rsync \
  php8.3-fpm php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip \
  mariadb-server

systemctl enable --now php8.3-fpm
systemctl enable --now mariadb
systemctl enable --now nginx

if [[ "$AUTO_UFW" == "y" ]]; then
  configure_ufw "$AUTO_HTTPS"
fi

# Write header snippets now (used by nginx)
write_headers_snippet
write_hsts_snippet

# Deploy code
deploy_app "$APP_ROOT" "$APP_REPO_URL" "$APP_REPO_BRANCH"

# Write .env HTTP first (assume SSL false at start)
write_env "$APP_ROOT" "$WEB_DOMAIN" "$DB_NAME" "$DB_USER" "$DB_PASS" "$DB_DOMAIN" "$DB_EMAIL" "$NODE_DOMAIN" \
  "$ADMIN_USERNAME" "$ADMIN_PASS" "$ADMIN_EMAIL" "$ADMIN_FIRST" "$ADMIN_LAST" "$ADMIN_WA" "$TELEMETRY"

# DB setup
need_cmd mysql
setup_db "$APP_ROOT" "$DB_NAME" "$DB_USER" "$DB_PASS"

# Seed admin
need_cmd php
seed_admin "$APP_ROOT" "$ADMIN_USERNAME" "$ADMIN_PASS" "$ADMIN_EMAIL" "$ADMIN_FIRST" "$ADMIN_LAST" "$ADMIN_WA" "$IS_ADMIN"

# Nginx HTTP site (clean)
write_nginx_http_site "$WEB_DOMAIN" "$APP_ROOT"
reload_nginx

# Optional HTTPS
if [[ "$AUTO_HTTPS" == "y" ]]; then
  if setup_https "$WEB_DOMAIN" "$ADMIN_EMAIL"; then
    enable_https_headers_on_site "$WEB_DOMAIN"
    reload_nginx

    switch_env_to_https "$APP_ROOT" "$WEB_DOMAIN"
    systemctl reload php8.3-fpm || true
    reload_nginx

    info "HTTPS berhasil diaktifkan + headers/HSTS terpasang."
  else
    warn "HTTPS belum berhasil. Website tetap berjalan di HTTP."
  fi
fi

info "=== SELESAI ==="
echo "Website: https://${WEB_DOMAIN} (jika HTTPS sukses) atau http://${WEB_DOMAIN}"
echo "Login:  https://${WEB_DOMAIN}/?page=login"
echo "Admin:  https://${WEB_DOMAIN}/?page=admin_dashboard"
echo "Nginx error log: /var/log/nginx/paserexpress.error.log"
