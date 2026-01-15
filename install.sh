#!/usr/bin/env bash
set -euo pipefail

# =========================
# PaserExpress Full Installer (single-file)
# =========================

info(){ echo -e "\n[INFO] $*"; }
warn(){ echo -e "\n[WARN] $*"; }
err(){ echo -e "\n[ERR ] $*" >&2; exit 1; }
trim(){ echo -n "$1" | xargs; }

need_root(){
  if [[ "${EUID:-$(id -u)}" -ne 0 ]]; then
    err "Jalankan sebagai root!"
  fi
}

need_cmd(){ command -v "$1" >/dev/null 2>&1 || err "Command not found: $1"; }

# =========================
# Validasi REGEX DOMAIN
# =========================
validate_domain() {
  local domain="$1"
  if [[ "$domain" =~ ^[A-Za-z0-9]([A-Za-z0-9-]{0,61}[A-Za-z0-9])?(\\.[A-Za-z]{2,})+$ ]]; then
    return 0
  else
    return 1
  fi
}

ask_domain() {
  local prompt="$1"
  local d=""
  while true; do
    read -rp "${prompt}: " d
    d="$(trim "$d")"
    if [[ -z "$d" ]]; then
      warn "Domain tidak boleh kosong!"
      continue
    fi
    if validate_domain "$d"; then
      echo "$d"
      return 0
    else
      warn "Format domain tidak valid. Contoh benar: a.aziztech.us"
    fi
  done
}

# =========================
# Validasi PORT
# =========================
ask_port() {
  local prompt="$1"
  local default="${2:-}"
  local port=""
  while true; do
    if [[ -n "$default" ]]; then
      read -rp "${prompt} (default ${default}): " port
      port="${port:-$default}"
    else
      read -rp "${prompt}: " port
    fi
    port="$(trim "$port")"

    if [[ "$port" =~ ^[0-9]{2,5}$ ]] && (( port >= 1024 && port <= 65535 )); then
      echo "$port"
      return 0
    else
      warn "Port harus angka range 1024–65535."
    fi
  done
}

# =========================
# Backup helper
# =========================
backup_file(){
  local f="$1"
  [[ -f "$f" ]] && cp -a "$f" "${f}.bak.$(date +%F_%H%M%S)"
}

# =========================
# OS Check
# =========================
check_os(){
  if [[ -f /etc/os-release ]]; then
    . /etc/os-release
    if [[ "${ID:-}" != "ubuntu" ]]; then
      warn "OS bukan Ubuntu. Script tetap mencoba jalan."
    fi
  fi
}
# =========================
# INPUT SSH PORTS — 3 PORT (22, 9898, custom)
# =========================
configure_ssh_ports_input() {
  info "=== SSH PORT SETUP ==="

  SSH_P1="22"
  SSH_P2="9898"
  SSH_P3="$(ask_port "Masukkan port SSH cadangan ke-3" "9922")"

  if [[ "$SSH_P3" == "$SSH_P1" || "$SSH_P3" == "$SSH_P2" ]]; then
    err "Port SSH cadangan tidak boleh sama dengan 22 atau 9898!"
  fi

  export SSH_P1 SSH_P2 SSH_P3
}


# =========================
# INPUT DOMAIN (Wajib, Tidak Boleh Kosong)
# =========================
input_domains() {
  info "=== DOMAIN SETUP ==="

  WEB_DOMAIN="$(ask_domain 'Domain Website (contoh: a.aziztech.us)')"
  DB_DOMAIN="$(ask_domain 'Domain Database (contoh: db.aziztech.us)')"
  NODE_DOMAIN="$(ask_domain 'Domain Node (contoh: node.aziztech.us)')"

  export WEB_DOMAIN DB_DOMAIN NODE_DOMAIN
}


# =========================
# INPUT VARIABEL DEPLOY
# =========================
input_app_settings() {
  APP_ROOT="/var/www/paserexpress"

  DEFAULT_REPO="https://github.com/azizcool1998/paserexpress.git"

  APP_ROOT="$(ask_nonempty 'Path deploy di server' "$APP_ROOT")"
  APP_REPO_URL="$(ask_nonempty 'Git repo URL untuk source app' "$DEFAULT_REPO")"
  APP_REPO_BRANCH="$(ask_nonempty 'Branch repo app' 'main')"

  export APP_ROOT APP_REPO_URL APP_REPO_BRANCH
}


# =========================
# INPUT DATABASE
# =========================
input_db_settings() {
  info "=== DATABASE SETUP ==="

  DB_NAME="$(ask_nonempty 'Nama database')"
  DB_USER="$(ask_nonempty 'Username database')"
  DB_PASS="$(ask_secret   'Password database')"
  DB_EMAIL="$(ask_nonempty 'Email database (metadata)')"

  export DB_NAME DB_USER DB_PASS DB_EMAIL
}


# =========================
# INPUT ADMIN
# =========================
input_admin_settings() {
  info "=== ADMIN SETUP ==="

  ADMIN_USERNAME="$(ask_nonempty 'Username admin')"
  ADMIN_FIRST="$(ask_nonempty 'First name admin')"
  ADMIN_LAST="$(ask_nonempty 'Last name admin')"
  ADMIN_EMAIL="$(ask_nonempty 'Email admin')"
  ADMIN_WA="$(ask_nonempty 'Nomor WhatsApp admin (format 62xxxx)')"
  ADMIN_PASS="$(ask_secret   'Password admin')"

  ADMIN_FLAG="$(ask_yesno_word "Administrator? (yes/no)" "yes")"
  IS_ADMIN=1
  [[ "${ADMIN_FLAG,,}" == "no" ]] && IS_ADMIN=0

  export ADMIN_USERNAME ADMIN_FIRST ADMIN_LAST ADMIN_EMAIL ADMIN_WA ADMIN_PASS IS_ADMIN
}


# =========================
# INPUT FEATURE FLAGS
# =========================
input_features() {
  AUTO_UFW="$(ask_yn 'Auto Configure Firewall UFW?' 'y')"
  AUTO_HTTPS="$(ask_yn 'Auto Configure HTTPS LetsEncrypt?' 'y')"
  TELEMETRY="$(ask_yesno_word 'Enable anonymous telemetry?' 'yes')"

  ASSUME_SSL="false"

  export AUTO_UFW AUTO_HTTPS TELEMETRY ASSUME_SSL
}
# =========================
# REVIEW SEMUA INPUT
# =========================
review_configuration() {
  info "=== REVIEW KONFIGURASI ==="

  cat <<EOF

================== FINAL CONFIG ==================

SSH Ports:
  • Primary SSH      : $SSH_P1
  • Backup SSH       : $SSH_P2
  • Custom SSH       : $SSH_P3

Domains (Semua Wajib Terisi):
  • Website Domain   : $WEB_DOMAIN
  • Database Domain  : $DB_DOMAIN
  • Node Domain      : $NODE_DOMAIN

Deployment:
  • Deployment Path  : $APP_ROOT
  • App Repo URL     : $APP_REPO_URL
  • Repo Branch      : $APP_REPO_BRANCH

Database:
  • DB Name          : $DB_NAME
  • DB User          : $DB_USER
  • DB Pass          : (hidden)
  • DB Email         : $DB_EMAIL

Administrator:
  • Username         : $ADMIN_USERNAME
  • Full Name        : $ADMIN_FIRST $ADMIN_LAST
  • Email            : $ADMIN_EMAIL
  • WhatsApp         : $ADMIN_WA
  • Password         : (hidden)
  • Is Admin         : $ADMIN_FLAG

Features:
  • Auto Firewall UFW: $AUTO_UFW
  • Auto HTTPS (LE)  : $AUTO_HTTPS
  • Telemetry        : $TELEMETRY
  • Assume SSL       : $ASSUME_SSL

===================================================

EOF

  CONFIRM_CONTINUE="$(ask_yn 'Konfirmasi: lanjutkan instalasi?' 'n')"
  [[ "$CONFIRM_CONTINUE" == "y" ]] || err "Dibatalkan oleh user."
}
# =========================
# MULAI INSTALASI SISTEM
# =========================
install_core_packages() {
  info "Install paket inti..."

  apt_install nginx git curl rsync \
    openssh-server iproute2 \
    php8.3-fpm php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip \
    mariadb-server

  systemctl enable --now ssh 2>/dev/null || systemctl enable --now sshd 2>/dev/null || true
  systemctl enable --now php8.3-fpm
  systemctl enable --now mariadb
  systemctl enable --now nginx
}

# =========================
# DEPLOY KODE APLIKASI
# =========================
deploy_application() {
  deploy_app "$APP_ROOT" "$APP_REPO_URL" "$APP_REPO_BRANCH"
}

# =========================
# TULIS FILE .ENV
# =========================
write_environment() {
  write_env "$APP_ROOT" "$WEB_DOMAIN" "$DB_NAME" "$DB_USER" "$DB_PASS" \
            "$DB_DOMAIN" "$DB_EMAIL" "$NODE_DOMAIN" \
            "$ADMIN_USERNAME" "$ADMIN_PASS" "$ADMIN_EMAIL" \
            "$ADMIN_FIRST" "$ADMIN_LAST" "$ADMIN_WA" \
            "$TELEMETRY"
}

# =========================
# BUAT DATABASE & IMPORT SCHEMA
# =========================
setup_database_all() {
  need_cmd mysql
  setup_db "$APP_ROOT" "$DB_NAME" "$DB_USER" "$DB_PASS"
}

# =========================
# BUAT / UPDATE ADMIN
# =========================
run_seed_admin() {
  need_cmd php
  seed_admin "$APP_ROOT" "$ADMIN_USERNAME" "$ADMIN_PASS" \
             "$ADMIN_EMAIL" "$ADMIN_FIRST" "$ADMIN_LAST" \
             "$ADMIN_WA" "$IS_ADMIN"
}

# =========================
# NGINX SITE + HTTP
# =========================
setup_nginx_http() {
  write_nginx_http_site "$WEB_DOMAIN" "$APP_ROOT"
  reload_nginx
}

# =========================
# HTTPS OPSIONAL
# =========================
setup_nginx_https_optional() {
  if [[ "$AUTO_HTTPS" == "y" ]]; then
    if setup_https "$WEB_DOMAIN" "$ADMIN_EMAIL"; then
      enable_https_headers_on_site "$WEB_DOMAIN"
      reload_nginx

      switch_env_to_https "$APP_ROOT" "$WEB_DOMAIN"
      systemctl reload php8.3-fpm || true
      reload_nginx

      info "HTTPS berhasil diaktifkan + HSTS + security headers."
    else
      warn "HTTPS gagal. Website tetap di HTTP."
    fi
  fi
}

# =========================
# JALANKAN SELURUH PROSES
# =========================
run_full_installation() {
  install_core_packages
  configure_sshd_ports "$SSH_P1" "$SSH_P2" "$SSH_P3"

  if [[ "$AUTO_UFW" == "y" ]]; then
    configure_ufw "$AUTO_HTTPS" "$SSH_P1" "$SSH_P2" "$SSH_P3"
  fi

  write_headers_snippet
  write_hsts_snippet

  deploy_application
  write_environment
  setup_database_all
  run_seed_admin

  setup_nginx_http
  setup_nginx_https_optional
}

# Jalankan semua
review_configuration
run_full_installation

# =========================
#  FINAL OUTPUT
# =========================

info "=== SELESAI ==="

# Cek apakah HTTPS sukses
if [[ "$AUTO_HTTPS" == "y" ]]; then
  if [[ -f "/etc/letsencrypt/live/${WEB_DOMAIN}/fullchain.pem" ]]; then
    USE_PROTO="https"
  else
    USE_PROTO="http"
  fi
else
  USE_PROTO="http"
fi

echo ""
echo "==============================================="
echo "         PASEREXPRESS INSTALLED SUCCESS         "
echo "==============================================="
echo ""
echo "➡ Website          : ${USE_PROTO}://${WEB_DOMAIN}"
echo "➡ Login Page       : ${USE_PROTO}://${WEB_DOMAIN}/?page=login"
echo "➡ Admin Dashboard  : ${USE_PROTO}://${WEB_DOMAIN}/?page=admin_dashboard"
echo ""
echo "➡ App Root         : ${APP_ROOT}"
echo "➡ Nginx Logs       : /var/log/nginx/paserexpress.error.log"
echo "                    : /var/log/nginx/paserexpress.access.log"
echo ""
echo "➡ Database Info"
echo "    Host           : 127.0.0.1"
echo "    Port           : 3306"
echo "    DB Name        : ${DB_NAME}"
echo "    DB User        : ${DB_USER}"
echo "    DB Pass        : (disembunyikan)"
echo ""
echo "➡ Admin Account"
echo "    Username       : ${ADMIN_USERNAME}"
echo "    Email          : ${ADMIN_EMAIL}"
echo ""
echo "➡ SSH Ports aktif:"
echo "    - ${SSH_P1}  (primary)"
echo "    - ${SSH_P2}  (backup)"
echo "    - ${SSH_P3}  (custom)"
echo ""
echo "Cara login SSH:"
echo "    ssh user@${WEB_DOMAIN}"
echo "    ssh -p ${SSH_P2} user@${WEB_DOMAIN}"
echo "    ssh -p ${SSH_P3} user@${WEB_DOMAIN}"
echo ""
echo "==============================================="
echo "  INSTALLER SELESAI • PaserExpress by AzizTech "
echo "==============================================="
echo ""
echo " DONE BOSS KU"
sleep 10
exit 0
