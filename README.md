# 🚀 PASEREXPRESS — ORACLE MODE DOCUMENTATION  
**Enterprise-Grade Logistics Platform for VPS**

PaserExpress adalah platform logistik mandiri yang dibangun untuk VPS kecil, namun memiliki fondasi arsitektur enterprise: stabil, aman, modular, dan mampu auto-repair.

Dokumentasi ini adalah versi **Oracle-Mode**, mencakup:

- Executive Summary  
- System Architecture  
- Dependencies & Requirements  
- Cara Install Step-By-Step  
- Auto Backup (Local / Remote)  
- Auto-Heal Engine  
- Monitoring System  
- CI/CD Deployment  
- SSH Key Guide  
- Developer Handbook  

---

# 🜁 1. EXECUTIVE SUMMARY  
**“Platform logistik stabil untuk ribuan transaksi, namun tetap ringan.”**

Platform mencakup:

- **Website pelanggan**
- **Dashboard admin modern (Bootstrap 5)**
- **Dashboard driver**
- **Realtime map (Leaflet)**
- **Auto backup engine**
- **Monitoring PRO internal**
- **Installer otomatis 1x klik**
- **Node domain (future expansion)**

---

# 🧩 2. SYSTEM ARCHITECTURE

```
Client → Nginx → PHP-FPM → MVC Backend → MariaDB

Optional:
Node Domain → Realtime microservices (future)
```

### Layer:
| Layer | Teknologi | Deskripsi |
|------|-----------|-----------|
| Edge Proxy | Nginx | SSL, routing, security |
| Backend PHP | PHP 8.3-FPM | API, MVC microkernel |
| Database | MariaDB 10.6+ | Data master |
| Monitoring | PHP module + Bash | Internal system metrics |
| Backup Engine | Bash | Auto backup: DB + code |
| Auto-Heal | Bash | Repair DB, PHP-FPM, Nginx |
| Installer | Bash | Fully automated |

---

# 🛠 3. DEPENDENCY REQUIREMENT

## ✔ Supported OS
| OS | Status |
|----|--------|
| Ubuntu 24.04.3 | 🟢 Fully Supported |
| Ubuntu 22.04 | 🟢 Supported |
| Debian 12 | 🟡 Partial (manual PHP repo) |

## ✔ Required Packages
| Component | Version |
|----------|---------|
| **Nginx** | 1.24+ |
| **PHP-FPM** | 8.3 |
| **MariaDB** | 10.6+ |
| **cURL** | latest |
| **Git** | latest |
| **Certbot** | optional |

## ✔ Hardware Minimum
- **1 CPU**
- **1 GB RAM (min), 2 GB recommended**
- **10 GB storage**

---

# 📦 4. INSTALLATION (STEP-BY-STEP)

### **0. Update VPS**
```bash
sudo apt update && sudo apt upgrade -y
```

---

### **1. Install via 1 perintah**
```bash
sudo bash <(curl -fsSL https://raw.githubusercontent.com/azizcool1998/paserexpress/main/install.sh)
```

Installer menanyakan:

- Domain website  
- Domain database  
- Domain node  
- Password database  
- Password admin  
- HTTPS enable? (Certbot)  
- Semua otomatis  

---

### **2. Akses Website**
```
http://domainmu.com/?page=login
```

---

# 🧭 5. PROJECT STRUCTURE

```
/var/www/paserexpress
 ├─ src
 │   ├─ controllers
 │   ├─ views
 │   ├─ api
 │   ├─ cli
 │   └─ includes
 ├─ config/schema.sql
 └─ .env
```

---

# 📊 6. MONITORING SYSTEM (INTERNAL)

Endpoint:
```
?page=api_monitoring
```

Data yang dikumpulkan:

- CPU load (1m, 5m, 15m)
- RAM (used/total/free)
- Disk usage
- Service status:
  - Nginx
  - PHP-FPM
  - MariaDB
- System uptime
- Timestamp ISO

Admin panel menampilkan monitoring dengan UI modern.

---

# 🔄 7. AUTO BACKUP ENGINE

File script:
```
/usr/local/bin/paserexpress-backup.sh
```

Backup berisi:
- Database dump
- Full source code tar.gz
- Metadata JSON

Folder backup:
```
/var/backups/paserexpress/
```

### Cron (nonaktif default, aktif via admin panel)
Contoh interval:

| Interval | Cron Format |
|----------|--------------|
| 1 menit | `*/1 * * * *` |
| 5 menit | `*/5 * * * *` |
| 15 menit | `*/15 * * * *` |
| 1 jam | `0 */1 * * *` |
| 6 jam | `0 */6 * * *` |
| 1 hari | `0 0 * * *` |
| 1 minggu | `0 0 * * 0` |

---

# 💚 8. AUTO HEAL SYSTEM

Script:
```
healthcheck.sh
healthfix.sh
healthfix_pro.sh
```

Memeriksa:
- PHP-FPM mati → restart
- MariaDB mati → restart
- Nginx mati → restart
- Permission invalid → fix
- .env rusak → auto-rebuild
- missing backup script → redownload

---

# 🔑 9. SSH KEY DEPLOYMENT (RECOMMENDED)

### 1. Generate SSH key di localhost/laptop
```bash
ssh-keygen -t ed25519 -C "paserexpress"
```

File muncul:
```
~/.ssh/id_ed25519        (private key)
~/.ssh/id_ed25519.pub    (public key)
```

### 2. Upload public key ke VPS
```bash
ssh-copy-id -i ~/.ssh/id_ed25519.pub root@IP-VPS
```

### 3. Simpan private key di GitHub Secrets:
Masuk:
```
GitHub → Settings → Secrets → Actions
```
Tambahkan:
```
SSH_PRIVATE_KEY
SSH_HOST
SSH_USER
```

---

# 🎯 10. CI/CD FULL DEPLOY (GitHub Actions)

File:
```
.github/workflows/deploy.yml
```

Dipicu setiap push ke branch `main`.

Pipeline:

1. Checkout repo
2. Setup SSH
3. SCP deploy ke VPS
4. Restart PHP-FPM
5. Reload Nginx

---

# 📚 11. DEVELOPER GUIDE (MVC MODE)

Controller path:
```
src/controllers/
```

View path:
```
src/views/
```

DB access:
```
db() → PDO instance
```

Sanitizer global:
```
sanitize($x)
```

---

# 🛡 12. SECURITY BASELINE

- Strict domain validation (regex)
- Admin-only routes
- Role-based routing
- Hidden admin seed
- HTTPS optional
- `.env` permission 600
- Protected file extensions via nginx

---

# 🧪 13. TESTING CHECKLIST

| Test | Status |
|------|--------|
| Website load | ✔ |
| Login admin | ✔ |
| Monitoring PRO | ✔ |
| Database connect | ✔ |
| Backup script | ✔ |
| Cron job | ✔ |
| SSL | ✔ |
| Auto-heal | ✔ |

---

# 🏁 14. UNINSTALL (Opsional)
```
sudo bash uninstaller.sh
```

---

# 🐉 15. END OF ORACLE README  
Dokumentasi ini adalah versi paling lengkap dan siap pakai untuk produksi.

