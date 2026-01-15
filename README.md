# Paser Express (Full)

Stack:
- Ubuntu 24.04.3
- Nginx
- Node.js (Express backend)
- Next.js frontend
- MariaDB
- Leaflet + OpenStreetMap (tanpa API key)

Fitur utama:
- Publik: landing, checkout, upload bukti pembayaran (manual review admin)
- Customer: register/login, dashboard, wajib simpan titik rumah (maps), topup saldo + bukti, riwayat transaksi, list order, tracking driver (live map polling)
- Driver: register/login (bisa login meski belum approve), saldo/topup, cocolan ambil order manual, filter radius dekat pickup/tujuan, realtime GPS ke server, status pengantaran ala Maxim
- Admin: multi admin/staff, approve driver, theme editor, payment setting (gopay + qris image), layanan CRUD, review pembayaran order, review topup, edit saldo manual (SET/ADD/SUB), lihat lokasi driver

## Install (Ubuntu)
1) Upload folder ini ke server
2) Jalankan:
```bash
cd PaserExpress
chmod +x install.sh
sudo bash install.sh
```

Installer tanya:
- domain, timezone, dll
- backend/frontend port (default 8081, 3001) => aman tidak tabrakan pterodactyl
- database name/user/pass
- admin pertama
- UFW + SSL certbot optional

## Service status
```bash
systemctl status paserexpress-backend
systemctl status paserexpress-frontend
journalctl -u paserexpress-backend -f
journalctl -u paserexpress-frontend -f
nginx -t
```

## Notes
- Checkout bisa dibayar MANUAL (GoPay/QRIS) => wajib upload bukti => admin confirm
- Checkout bisa dibayar WALLET (saldo) jika customer login => saldo terpotong otomatis => order langsung PAID_CONFIRMED
- Customer tracking driver: /akun/order/{id} (jika order milik customer)
