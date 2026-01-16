# 🚀 PaserExpress — Full Stack Delivery System (Enterprise Edition)

Modern multi-role delivery system with complete automation, monitoring, backup, auto-deploy, and hardened infrastructure.

---

# 🧩 System Requirements & Dependencies

## 🖥️ OS Support
| Operating System | Status |
|------------------|--------|
| Ubuntu Server **24.04 LTS** | 🟢 Fully Supported |
| Ubuntu Server 22.04 LTS | 🟡 Partial — but works |
| Ubuntu 20.04 LTS | 🔴 Not Recommended |
| Debian / CentOS | 🔴 Not Supported |

> **Direkomendasikan:** Ubuntu 24.04.3 LTS — semua installer & service disesuaikan untuk versi ini.

---

# 📦 Software Dependencies

## 🔧 Core Services
| Software | Required Version | Status |
|----------|------------------|--------|
| **Nginx** | 1.24.x (Ubuntu repo) | 🟢 OK |
| **PHP-FPM** | **8.3.x** | 🟢 Required |
| PHP Extensions | php8.3-fpm, php8.3-mysql, php8.3-mbstring, php8.3-curl, php8.3-xml, php8.3-zip | 🟢 Required |
| **MariaDB Server** | 10.6+ | 🟢 Required |
| **Node.js** (opsional) | 18+ (future features) | 🟡 Optional |
| **Git** | Latest | 🟢 Required |
| **Certbot** | Latest | 🟡 Optional for HTTPS |

---

# 🛠️ System Utilities
| Package | Fungsi |
|---------|--------|
| curl | Mengambil script installer / API |
| rsync | Digunakan backup script |
| ufw | Firewall auto-config |
| openssh-server | Untuk SSH + auto deploy |
| cron | Untuk auto-backup scheduler |

---

# ✨ Fitur Utama

## Publik
- Landing page  
- Tracking map  
- Upload bukti pembayaran  

## Customer
- Register / Login  
- Home location map  
- Dompet + topup bukti transfer  
- Riwayat transaksi  
- Live tracking driver  

## Driver
- Login meski belum approve  
- GPS realtime  
- Ambil order manual  
- Radius filter  
- Status perjalanan seperti Maxim  

## Admin
- Multi admin  
- Approve driver  
- Review bukti pembayaran  
- CRUD layanan  
- Edit saldo manual  
- Lihat lokasi driver  
- **Monitoring PRO**  
- **Backup Automation**  
- **Auto Deploy CI/CD Ready**  

---

# ⚙️ Install (Ubuntu 24.04)

## 1. Clone repository
```bash
git clone https://github.com/azizcool1998/paserexpress.git
cd paserexpress
```

## 2. Jalankan installer
```bash
chmod +x install.sh
sudo bash <(curl -fsSL https://raw.githubusercontent.com/azizcool1998/paserexpress/main/install.sh)
```

Installer akan menanyakan:

- Website domain  
- Database domain  
- Node domain  
- SSH port cadangan  
- Password admin & database  
- Enable HTTPS or not  
- Telemetry  

Installer otomatis:

- Setup PHP-FPM  
- Setup nginx virtualhost  
- Setup SSL (opsional)  
- Setup MariaDB + user  
- Generate `.env`  
- Deploy app via Git  
- Install backup system  
- Enable auto-monitoring  

---

# 🏗️ Struktur Direktori

```
/var/www/paserexpress/
├── src/
│   ├── public/
│   ├── controllers/
│   ├── views/
│   ├── api/
│   ├── includes/
│   └── cli/
├── config/
│   └── schema.sql
└── .env
```

---

# 📈 Monitoring PRO

Realtime monitoring tersedia di Admin Panel:

- CPU Load  
- RAM usage  
- Disk usage  
- Service status  
- System uptime  
- Auto-refresh 15 detik  

Endpoint:
```
?page=api_monitoring
```

---

# 🔄 Auto Backup System

Backup meliputi:

- Database (SQL dump)  
- Source code (tar archive)  
- Upload folder  
- Cron otomatis  

Interval pengaturan:

```
1 menit, 5 menit, 15 menit, 30 menit,
1 jam, 2 jam, 3 jam, 6 jam, 9 jam, 12 jam, 18 jam,
1 hari, 3 hari,
1 minggu, 2 minggu, 3 minggu,
1 bulan, 2 bulan, 3 bulan, 6 bulan, 9 bulan, 1 tahun
```

Lokasi backup:
```
/var/backups/paserexpress/
```

Script Editor:
```
/usr/local/bin/paserexpress-backup.sh
```

---

# 🚀 CI/CD — GitHub Auto Deploy

Auto deploy berjalan setiap kamu push ke `main` via GitHub Actions.

### Dibutuhkan GitHub Secrets:

| Secret | Isi |
|--------|-----|
| VPS_HOST | IP VPS |
| VPS_USER | root |
| VPS_SSH_KEY | PRIVATE KEY |
| TG_TOKEN (opsional) | Telegram bot |
| TG_CHAT (opsional) | Telegram chat ID |
| WA_PHONE (opsional) | WhatsApp |
| WA_KEY (opsional) | WhatsApp API key |

Workflow:

- SSH ke VPS  
- Git pull  
- Restart service  
- Notifikasi sukses/gagal  

---

# 🔐 Setup SSH Key Auto Deploy

## 1. Generate key
```bash
ssh-keygen -t rsa -b 4096
```

## 2. Upload PUB KEY ke VPS
```bash
mkdir -p ~/.ssh
echo "ISI-PUBLIC-KEY-MU" >> ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys
```

## 3. Simpan PRIVATE KEY di GitHub Secrets
```
VPS_SSH_KEY
```

## 4. Simpan IP VPS di GitHub Secrets
```
VPS_HOST
```

Migrasi VPS cukup ganti value — workflow tetap sama.

---

# 🧬 Troubleshooting

### nginx 502
```
systemctl restart php8.3-fpm
systemctl reload nginx
```

### Database access denied
Pastikan `.env` → DB_NAME / DB_USER / DB_PASS benar.

### SSL gagal
Pastikan:
- Port 80 terbuka  
- DNS A record benar  

---

# 🧰 Commands Penting

Status service:
```bash
systemctl status nginx
systemctl status php8.3-fpm
systemctl status mariadb
```

Logs:
```bash
journalctl -u nginx -f
```

Reload nginx:
```bash
nginx -t && systemctl reload nginx
```

---

# 🎉 Selesai — PaserExpress Siap Digunakan

Admin Login:
```
http://your-domain.com/?page=login
```

Jika ingin fitur lanjutan, gunakan perintah:
```
"Lanjutkan fitur super-lanjutan"
```
