#!/usr/bin/env bash
set -euo pipefail

# ======================================================
# PASEREXPRESS INSTALLER — ULTRA STABLE FINAL EDITION
# ======================================================

# ---------- BASIC HELPERS ----------
info(){ echo -e "\n[INFO] $*"; }
warn(){ echo -e "\n[WARN] $*"; }
err(){ echo -e "\n[ERR ] $*" >&2; exit 1; }
trim(){ echo -n "$1" | xargs; }

need_root(){
    if [[ "${EUID:-$(id -u)}" -ne 0 ]]; then
        err "Harus dijalankan sebagai ROOT."
    fi
}

need_cmd(){ command -v "$1" >/dev/null 2>&1 || err "Command missing: $1"; }

# ---------- YES/NO ----------
ask_yn(){
    local prompt="$1"
    local default="${2:-y}"
    local ans
    while true; do
        read -rp "${prompt} (y/n, default: $default): " ans
        ans="$(trim "${ans,,}")"
        [[ -z "$ans" ]] && echo "$default" && return
        case "$ans" in
            y|yes|1|true) echo y; return;;
            n|no|0|false) echo n; return;;
            *) warn "Input salah, gunakan y/n.";;
        esac
    done
}

ask_secret(){
    local prompt="$1" v
    while true; do
        read -rsp "${prompt}: " v; echo
        v="$(trim "$v")"
        [[ -n "$v" ]] && echo "$v" && return
        warn "Tidak boleh kosong."
    done
}

# ---------- ASK PORT ----------
ask_port(){
    local prompt="$1" port=""
    while true; do
        read -rp "${prompt}: " port
        port="$(trim "$port")"
        if [[ "$port" =~ ^[0-9]{4,5}$ ]] && (( port>=1024 && port<=65535 )); then
            echo "$port"
            return
        fi
        warn "Port harus angka 1024–65535."
    done
}

# ---------- ASK DOMAIN ----------
valid_domain_regex='^([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}$'
ask_domain(){
    local prompt="$1" domain=""
    while true; do
        read -rp "${prompt}: " domain
        domain="$(trim "$domain")"
        [[ -z "$domain" ]] && warn "Domain tidak boleh kosong." && continue
        if [[ "$domain" =~ $valid_domain_regex ]]; then
            echo "$domain"
            return
        fi
        warn "Format domain salah!"
        warn "Contoh benar: web.aziztech.us, api.example.com"
    done
}

# ======================================================
# SSH CONFIG
# ======================================================
configure_sshd_ports(){
    local p1="$1" p2="$2" p3="$3"
    mkdir -p /etc/ssh/sshd_config.d
    cat >/etc/ssh/sshd_config.d/99-paserexpress.conf <<EOF
Port ${p1}
Port ${p2}
Port ${p3}
EOF
    sshd -t || err "Konfigurasi SSH salah!"
    systemctl restart ssh || systemctl restart sshd
}

# ======================================================
# FIREWALL (UFW)
# ======================================================
configure_ufw(){
    local https="$1" s1="$2" s2="$3" s3="$4"
    apt-get install -y ufw
    ufw allow "$s1/tcp" || true
    ufw allow "$s2/tcp" || true
    ufw allow "$s3/tcp" || true
    ufw allow 80/tcp || true
    [[ "$https" == "y" ]] && ufw allow 443/tcp || true
    ufw --force enable || true
}

# ======================================================
# NGINX SNIPPETS
# ======================================================
write_headers_snippet(){
cat >/etc/nginx/snippets/paserexpress-headers.conf <<EOF
add_header X-Frame-Options "SAMEORIGIN";
add_header X-Content-Type-Options "nosniff";
add_header Referrer-Policy "strict-origin-when-cross-origin";
EOF
}
write_hsts_snippet(){
cat >/etc/nginx/snippets/paserexpress-hsts.conf <<EOF
add_header Strict-Transport-Security "max-age=31536000" always;
EOF
}

# ======================================================
# NGINX HTTP SITE
# ======================================================
write_nginx_http_site(){
    local domain="$1" app_root="$2" pub="${app_root}/src/public"
cat >/etc/nginx/sites-available/${domain}.conf <<NGINX
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
    ln -sf "/etc/nginx/sites-available/${domain}.conf" "/etc/nginx/sites-enabled/${domain}.conf"
    rm -f /etc/nginx/sites-enabled/default || true
}

# ======================================================
# ENV WRITER
# ======================================================
write_env(){
cat > "$1/.env" <<EOF
APP_NAME="Paser Express"
APP_ENV=production
APP_DEBUG=false
APP_BASE_URL=http://$2

DB_HOST=127.0.0.1
DB_NAME=$3
DB_USER=$4
DB_PASS=$5
DB_DOMAIN=$6
DB_EMAIL=$7

NODE_DOMAIN=$8

ADMIN_USERNAME=$9
ADMIN_PASSWORD=${10}
ADMIN_EMAIL=${11}
ADMIN_FIRST_NAME=${12}
ADMIN_LAST_NAME=${13}
ADMIN_WHATSAPP=${14}

TELEMETRY_ENABLED=${15}
EOF
    chmod 600 "$1/.env"
}

# ======================================================
# GIT DEPLOY
# ======================================================
deploy_app(){
    local root="$1" repo="$2" branch="$3"
    if [[ -d "$root/.git" ]]; then
        (cd "$root" && git fetch --all && git reset --hard "origin/${branch}")
    else
        rm -rf "$root"
        git clone --depth 1 -b "$branch" "$repo" "$root"
    fi
}

# ======================================================
# DATABASE
# ======================================================
setup_db(){
    local app="$1" name="$2" user="$3" pass="$4"

    [[ -f "$app/config/schema.sql" ]] || err "schema.sql tidak ditemukan di $app/config/"

    mysql -e "CREATE DATABASE IF NOT EXISTS \`${name}\`;"
    mysql -e "CREATE USER IF NOT EXISTS '${user}'@'localhost' IDENTIFIED BY '${pass}';"
    mysql -e "GRANT ALL PRIVILEGES ON \`${name}\`.* TO '${user}'@'localhost'; FLUSH PRIVILEGES;"
    mysql -u"$user" -p"$pass" "$name" < "$app/config/schema.sql"
}

# ======================================================
# ADMIN SEED
# ======================================================
seed_admin(){
php "$1/src/cli/seed_admin.php" \
    --username="$2" \
    --password="$3" \
    --email="$4" \
    --first="$5" \
    --last="$6" \
    --wa="$7"
}

# ======================================================
# HTTPS
# ======================================================
setup_https(){
    apt-get install -y certbot python3-certbot-nginx
    certbot --nginx -d "$1" --non-interactive --agree-tos -m "$2" || return 1
}

# ======================================================
# BACKUP SYSTEM
# ======================================================
install_backup_script(){
    mkdir -p /var/backups/paserexpress
    curl -fsSL "https://raw.githubusercontent.com/azizcool1998/paserexpress/main/scripts/paserexpress-backup.sh" \
        -o "/usr/local/bin/paserexpress-backup.sh"
    chmod +x /usr/local/bin/paserexpress-backup.sh
}
verify_backup_script(){
    [[ -f /usr/local/bin/paserexpress-backup.sh ]] || install_backup_script
}
setup_backup_cron(){
cat >/etc/cron.d/paserexpress-backup <<EOF
# Auto-backup NONAKTIF — atur di Admin Panel.
EOF
chmod 644 /etc/cron.d/paserexpress-backup
}

# ======================================================
# MAIN INSTALL LOGIC
# ======================================================
need_root

SSH_P1="22"
SSH_P2="9898"
SSH_P3="$(ask_port 'Port SSH ke-3 (1024–65535)')"

WEB_DOMAIN="$(ask_domain 'Domain WEBSITE')"
DB_DOMAIN="$(ask_domain 'Domain DATABASE')"
NODE_DOMAIN="$(ask_domain 'Domain NODE')"

APP_ROOT="/var/www/paserexpress"
DB_NAME="paserexpress"
DB_USER="paser_user"
DB_PASS="$(ask_secret 'Password Database')"
DB_EMAIL="admin@${DB_DOMAIN}"

ADMIN_USERNAME="admin"
ADMIN_FIRST="Admin"
ADMIN_LAST="Panel"
ADMIN_EMAIL="admin@${WEB_DOMAIN}"
ADMIN_WA="62000000"
ADMIN_PASS="$(ask_secret 'Password Admin')"

AUTO_HTTPS="$(ask_yn 'Aktifkan HTTPS otomatis?' 'y')"
TELEMETRY="yes"

info "Install dependencies..."
apt-get update -y
apt-get install -y nginx git php8.3-fpm php8.3-mysql mariadb-server

configure_sshd_ports "$SSH_P1" "$SSH_P2" "$SSH_P3"
configure_ufw "$AUTO_HTTPS" "$SSH_P1" "$SSH_P2" "$SSH_P3"

write_headers_snippet
write_hsts_snippet

deploy_app "$APP_ROOT" "https://github.com/azizcool1998/paserexpress.git" "main"

write_env "$APP_ROOT" "$WEB_DOMAIN" "$DB_NAME" "$DB_USER" "$DB_PASS" "$DB_DOMAIN" "$DB_EMAIL" "$NODE_DOMAIN" \
    "$ADMIN_USERNAME" "$ADMIN_PASS" "$ADMIN_EMAIL" "$ADMIN_FIRST" "$ADMIN_LAST" "$ADMIN_WA" "$TELEMETRY"

setup_db "$APP_ROOT" "$DB_NAME" "$DB_USER" "$DB_PASS"
seed_admin "$APP_ROOT" "$ADMIN_USERNAME" "$ADMIN_PASS" "$ADMIN_EMAIL" "$ADMIN_FIRST" "$ADMIN_LAST" "$ADMIN_WA"

write_nginx_http_site "$WEB_DOMAIN" "$APP_ROOT"
systemctl reload nginx

[[ "$AUTO_HTTPS" == "y" ]] && setup_https "$WEB_DOMAIN" "$ADMIN_EMAIL" && systemctl reload nginx

install_backup_script
verify_backup_script
setup_backup_cron

info "==== INSTALASI SELESAI ===="
echo "Website  : http://${WEB_DOMAIN}"
echo "Login    : http://${WEB_DOMAIN}/?page=login"
echo "SSH      : 22, 9898, ${SSH_P3}"
