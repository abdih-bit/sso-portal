# 🚀 Panduan Setup SSO Portal di aaPanel untuk domain portal.hqmedan.com

## 📋 Persiapan Awal

Sebelum memulai, pastikan Anda punya:
- ✅ VPS dengan aaPanel sudah terinstall
- ✅ Domain `portal.hqmedan.com` sudah pointing ke IP VPS
- ✅ Akses SSH ke VPS
- ✅ Akses ke aaPanel Dashboard

---

## 🎯 Step 1: Verifikasi Domain Pointing

### 1.1 Cek Nameserver Domain
1. Masuk ke registrar domain Anda (GoDaddy, Niagahoster, dll)
2. Ubah nameserver ke:
   - DNS 1: `8.8.8.8` (Google DNS)
   - DNS 2: `8.8.4.4` (Google DNS)
   - Atau sesuaikan dengan DNS VPS provider Anda

### 1.2 Test Domain Pointing
```bash
# SSH ke VPS
ssh root@YOUR_VPS_IP

# Test domain resolve ke IP VPS
nslookup portal.hqmedan.com
# Seharusnya return: Your_VPS_IP_Address

# Atau test dengan ping
ping portal.hqmedan.com
```

**Tunggu 24-48 jam untuk propagasi DNS jika baru diubah!**

---

## 🔧 Step 2: Persiapan di aaPanel Dashboard

### 2.1 Login ke aaPanel
```
URL: http://YOUR_VPS_IP:8888
atau
URL: http://portal.hqmedan.com:8888 (jika domain sudah aktif)
```

Masukkan username dan password aaPanel Anda.

### 2.2 Verifikasi Node.js Terinstall

1. **Di Dashboard aaPanel**, klik menu **App Store** (atau **Software**)
2. Cari **Node.js**
3. Pastikan status: **Installed** ✅

Jika belum terinstall:
- Klik **Install**
- Pilih versi stable (v18+ recommended)
- Tunggu proses selesai

---

## 🌐 Step 3: Buat Website Baru di aaPanel

### 3.1 Buka Websites Menu
1. **Dashboard aaPanel** → Klik **Websites**
2. Klik tombol **Add Site** (atau **+ Add**)

### 3.2 Isi Form Pembuatan Website

**Form akan muncul dengan field:**

| Field | Nilai |
|-------|-------|
| Domain | `portal.hqmedan.com` |
| Subdomain | (kosongkan) |
| Path | `/www/wwwroot/portal.hqmedan.com` (auto-generate) |
| FTP User | `portal` (auto-generate) |
| Database | `sso_portal` (buat baru) |
| Database User | `sso_user` (auto-generate) |
| PHP | ❌ **Tidak perlu** (unchecked) |

**Klik tombol "Submit"**

✅ Website baru berhasil dibuat!

---

## 🗄️ Step 4: Setup Database PostgreSQL

### 4.1 Via aaPanel GUI (Recommended)
1. **Dashboard aaPanel** → **Database**
2. Klik **+ Add Database** atau **Add PostgreSQL**
3. Isi form:
   ```
   Database Name: sso_portal
   Database User: sso_user
   Password: (generate password yang kuat - simpan baik-baik!)
   Database Type: PostgreSQL
   ```
4. Klik **Submit**

### 4.2 Verifikasi Database Berhasil
```bash
# SSH ke VPS
ssh root@YOUR_VPS_IP

# Test koneksi database
psql -U sso_user -d sso_portal -h localhost -c "SELECT 1;"

# Output harus: 1
```

---

## 📤 Step 5: Upload Project ke VPS

Ada 2 cara: **Git** (recommended) atau **SFTP**

### **Opsi A: Upload via Git (Recommended)**

#### Di Local Machine (Windows):
```powershell
cd "d:\SSO Portal"

# 1. Inisialisasi git jika belum ada
git init

# 2. Tambah remote repository
git remote add origin https://github.com/YOUR_USERNAME/sso-portal.git

# 3. Push ke GitHub
git add .
git commit -m "Pre-deployment version"
git push -u origin main
```

#### Di VPS:
```bash
# SSH ke VPS
ssh root@YOUR_VPS_IP

# Masuk ke folder website
cd /www/wwwroot/portal.hqmedan.com

# Clone dari GitHub
git clone https://github.com/YOUR_USERNAME/sso-portal.git .

# Atau jika ingin update versi existing:
git pull origin main
```

### **Opsi B: Upload via SFTP (Alternative)**

Gunakan aplikasi SFTP seperti **WinSCP**, **FileZilla**, atau **PuTTY PSCP**:

1. **Host**: `YOUR_VPS_IP`
2. **Port**: `22` (SSH)
3. **Username**: `root`
4. **Password**: SSH password
5. **Remote Path**: `/www/wwwroot/portal.hqmedan.com`
6. Upload semua file

**File penting yang HARUS ada:**
- ✅ `package.json`
- ✅ `package-lock.json`
- ✅ `src/` (folder)
- ✅ `prisma/` (folder)
- ✅ `public/` (folder)
- ✅ `.env.example` atau `.env`

---

## ⚙️ Step 6: Konfigurasi Environment Variables

### 6.1 Create & Edit .env File
```bash
# SSH ke VPS
ssh root@YOUR_VPS_IP

# Masuk ke folder project
cd /www/wwwroot/portal.hqmedan.com

# Copy file contoh
cp .env.example .env

# Edit .env dengan text editor
nano .env
```

### 6.2 Isi Konfigurasi .env

**Tekan `i` untuk edit, lalu isi:**

```bash
# ===========================
# SERVER CONFIGURATION
# ===========================
NODE_ENV=production
PORT=3000
APP_URL=https://portal.hqmedan.com

# ===========================
# DATABASE CONFIGURATION
# ===========================
DATABASE_URL="postgresql://sso_user:YOUR_DB_PASSWORD@localhost:5432/sso_portal?schema=public"
# Ganti YOUR_DB_PASSWORD dengan password yang Anda buat di Step 4

# ===========================
# JWT CONFIGURATION
# ===========================
JWT_SECRET=your-secret-key-min-32-chars-change-this
JWT_EXPIRES_IN=24h
JWT_REFRESH_SECRET=your-refresh-secret-change-this
JWT_REFRESH_EXPIRES_IN=7d

# ===========================
# SESSION CONFIGURATION
# ===========================
SESSION_SECRET=your-session-secret-change-this
SESSION_MAX_AGE=86400000

# ===========================
# SSO TOKEN CONFIGURATION
# ===========================
SSO_TOKEN_EXPIRES=300

# ===========================
# EMAIL CONFIGURATION (Optional)
# ===========================
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your-email@gmail.com
SMTP_PASS=your-app-password
SMTP_FROM=noreply@hqmedan.com
```

**Generate secret keys (jalankan di VPS):**
```bash
node -e "console.log(require('crypto').randomBytes(32).toString('hex'))"
```

**Simpan file:**
- Tekan `Ctrl+X`
- Tekan `Y` (Yes)
- Tekan `Enter`

### 6.3 Set File Permissions
```bash
cd /www/wwwroot/portal.hqmedan.com

# Buat .env readable hanya untuk owner
chmod 600 .env

# Set ownership ke www user
chown www:www .env
```

---

## 📦 Step 7: Install Dependencies & Setup Database

### 7.1 Install NPM Packages
```bash
cd /www/wwwroot/portal.hqmedan.com

# Install dependencies (production only)
npm install --omit=dev

# atau jika ingin development tools:
npm install
```

**Tunggu hingga selesai (bisa 5-10 menit tergantung koneksi).**

### 7.2 Generate Prisma Client
```bash
npx prisma generate
```

### 7.3 Jalankan Database Migration
```bash
npx prisma migrate deploy
```

**Output harus seperti:**
```
✅ Generated Prisma Client
✅ 5 migrations applied successfully
```

### 7.4 Seed Database (Optional - untuk data awal)
```bash
node src/database/seed.js
```

---

## ▶️ Step 8: Setup Process Manager (PM2)

### 8.1 Buka aaPanel Supervisor Menu
1. **Dashboard aaPanel** → **Supervisor** (atau cari **Process Manager**)
2. Klik **Add Process** atau **Create Supervised Process**

### 8.2 Isi Form Process Configuration

| Field | Nilai |
|-------|-------|
| **Process Name** | `sso-portal` |
| **Run User** | `www` |
| **Working Directory** | `/www/wwwroot/portal.hqmedan.com` |
| **Command** | `npm start` |
| **Autostart** | ✅ Checked |
| **Autorestart** | ✅ Checked |

**Klik Submit**

✅ Process berhasil dibuat!

### 8.3 Verifikasi Process Berjalan
```bash
# SSH ke VPS
ssh root@YOUR_VPS_IP

# Cek status PM2
pm2 status

# Seharusnya ada `sso-portal` dengan status `online`
```

---

## 🌐 Step 9: Setup Reverse Proxy Nginx

### 9.1 Via aaPanel (Recommended)

1. **Dashboard aaPanel** → **Websites**
2. Klik website **portal.hqmedan.com**
3. Klik icon **Configuration** (⚙️)
4. Tab **Reverse Proxy**
5. Klik **Add Reverse Proxy**

**Isi form:**
```
Proxy Name: node
Proxy IP: 127.0.0.1:3000
```

**Klik Submit**

✅ Reverse proxy berhasil dikonfigurasi!

### 9.2 Verifikasi Konfigurasi
```bash
# SSH ke VPS
ssh root@YOUR_VPS_IP

# Cek config nginx
cat /www/server/nginx/conf/vhost/portal.hqmedan.com.conf | grep -A 10 "proxy_pass"

# Seharusnya ada: proxy_pass http://127.0.0.1:3000;
```

### 9.3 Reload Nginx
```bash
systemctl reload nginx
```

---

## 🔒 Step 10: Setup SSL Certificate (HTTPS)

### 10.1 Via aaPanel (Recommended)
1. **Dashboard aaPanel** → **Websites**
2. Klik website **portal.hqmedan.com**
3. Tab **SSL**
4. Klik **Add SSL Certificate**
5. Pilih **Let's Encrypt** (Free)
6. Domain otomatis terdeteksi: `portal.hqmedan.com`
7. Klik **Request Certificate**

**Tunggu 1-2 menit...**

✅ SSL certificate berhasil diinstall!

### 10.2 Verifikasi HTTPS
```bash
# Test HTTPS
curl -I https://portal.hqmedan.com

# Seharusnya return: HTTP/2 200 atau HTTP/1.1 200
```

---

## ✅ Step 11: Testing Aplikasi

### 11.1 Akses Aplikasi via Browser

**Buka di browser:**
```
https://portal.hqmedan.com
```

Anda seharusnya melihat halaman login SSO Portal dengan HTTPS! 🎉

### 11.2 Test Database Connection
```bash
ssh root@YOUR_VPS_IP
cd /www/wwwroot/portal.hqmedan.com

node -e "
require('dotenv').config();
const { PrismaClient } = require('@prisma/client');
const prisma = new PrismaClient();
prisma.\$queryRaw\`SELECT 1\`.then(() => {
  console.log('✅ Database connected successfully!');
  process.exit(0);
}).catch(e => {
  console.error('❌ Database error:', e.message);
  process.exit(1);
});
"
```

### 11.3 Test API Endpoints
```bash
# Test login endpoint
curl -X POST https://portal.hqmedan.com/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@hqmedan.com","password":"password"}'
```

### 11.4 Lihat Logs Aplikasi
```bash
pm2 logs sso-portal

# Atau via aaPanel: Supervisor → pilih sso-portal → View Logs
```

---

## 🔧 Step 12: Konfigurasi Lanjutan (Optional)

### 12.1 Setup Auto Backup Database
```bash
# SSH ke VPS
ssh root@YOUR_VPS_IP

# Buat script backup
cat > /home/backup-db.sh << 'EOF'
#!/bin/bash
BACKUP_DIR="/backup"
DB_NAME="sso_portal"
DB_USER="sso_user"
DATE=$(date +%Y%m%d_%H%M%S)

mkdir -p $BACKUP_DIR

pg_dump -U $DB_USER $DB_NAME > $BACKUP_DIR/sso_portal_$DATE.sql

# Hapus backup yang lebih dari 30 hari
find $BACKUP_DIR -name "sso_portal_*.sql" -mtime +30 -delete

echo "Backup completed: $BACKUP_DIR/sso_portal_$DATE.sql"
EOF

# Set permission
chmod +x /home/backup-db.sh

# Jalankan setiap hari jam 2 pagi
# Edit crontab:
crontab -e

# Tambahkan baris:
# 0 2 * * * /home/backup-db.sh >> /var/log/backup.log 2>&1
```

### 12.2 Monitoring Resource Usage
1. **Dashboard aaPanel** → **Status**
2. Monitor CPU, Memory, Disk usage
3. Sesuaikan jika diperlukan

### 12.3 Setup Rate Limiting (untuk security)
```bash
# SSH ke VPS
ssh root@YOUR_VPS_IP

# Edit nginx config
nano /www/server/nginx/conf/vhost/portal.hqmedan.com.conf

# Tambahkan di awal block server:
limit_req_zone $binary_remote_addr zone=api_limit:10m rate=10r/s;
limit_req_status 429;

# Di location /api, tambahkan:
limit_req zone=api_limit burst=20 nodelay;
```

---

## 🛑 Troubleshooting

### ❌ Error: "Cannot connect to domain.com"

**Solusi:**
```bash
# 1. Cek DNS propagation
nslookup portal.hqmedan.com

# 2. Cek Apache/Nginx running
systemctl status nginx

# 3. Cek firewall
ufw status
ufw allow 80
ufw allow 443
```

### ❌ Error: "502 Bad Gateway"

**Solusi:**
```bash
# 1. Cek process Node.js
pm2 status

# 2. Restart process
pm2 restart sso-portal

# 3. Cek logs
pm2 logs sso-portal

# 4. Verifikasi reverse proxy
netstat -tulnp | grep 3000
```

### ❌ Error: "Database connection failed"

**Solusi:**
```bash
# 1. Cek DATABASE_URL di .env
cat /www/wwwroot/portal.hqmedan.com/.env | grep DATABASE_URL

# 2. Test database connection
psql -U sso_user -d sso_portal -c "SELECT 1;"

# 3. Restart aplikasi
pm2 restart sso-portal
```

### ❌ Error: "Cannot read package.json"

**Solusi:**
```bash
# 1. Cek file ada
ls -la /www/wwwroot/portal.hqmedan.com/package.json

# 2. Jika tidak ada, re-upload project dengan Git:
cd /www/wwwroot/portal.hqmedan.com
git pull origin main
```

### ❌ Error: "npm ENOENT"

**Solusi:**
```bash
# 1. Cek npm installed
npm --version

# 2. Install Node.js via aaPanel App Store

# 3. Reinstall dependencies
cd /www/wwwroot/portal.hqmedan.com
npm install --omit=dev
```

---

## 📝 Checklist Deployment Lengkap

### ✅ Setup Awal
- [ ] Domain `portal.hqmedan.com` pointing ke VPS IP
- [ ] aaPanel sudah terinstall di VPS
- [ ] Node.js terinstall di aaPanel
- [ ] Bisa akses aaPanel Dashboard

### ✅ Website & Database
- [ ] Website `portal.hqmedan.com` dibuat di aaPanel
- [ ] Database `sso_portal` dibuat
- [ ] Database user `sso_user` dibuat dengan password

### ✅ Project Upload
- [ ] Project di-upload ke `/www/wwwroot/portal.hqmedan.com`
- [ ] Semua file ada (package.json, src/, prisma/, dll)
- [ ] `.env` file dibuat dan dikonfigurasi dengan benar

### ✅ Dependencies & Database
- [ ] NPM packages installed (`npm install --omit=dev`)
- [ ] Prisma generated (`npx prisma generate`)
- [ ] Database migration dijalankan (`npx prisma migrate deploy`)
- [ ] Database test connection berhasil

### ✅ Process & Web Server
- [ ] PM2 process `sso-portal` dibuat di aaPanel
- [ ] Process status `online` ✅
- [ ] Reverse proxy Nginx dikonfigurasi
- [ ] Nginx sudah reload

### ✅ SSL & Security
- [ ] SSL certificate (Let's Encrypt) installed
- [ ] HTTPS berfungsi
- [ ] Firewall dikonfigurasi (port 22, 80, 443)
- [ ] File permissions sudah benar

### ✅ Testing
- [ ] Bisa akses https://portal.hqmedan.com
- [ ] Halaman login muncul
- [ ] Database terhubung
- [ ] Logs tidak ada error

---

## 🎯 Command Referensi Cepat

```bash
# ========== SSH ke VPS ==========
ssh root@YOUR_VPS_IP

# ========== Project Directory ==========
cd /www/wwwroot/portal.hqmedan.com

# ========== NPM Commands ==========
npm install --omit=dev          # Install dependencies
npm start                        # Start aplikasi (manual)
npx prisma generate             # Generate Prisma Client
npx prisma migrate deploy        # Run migrations
node src/database/seed.js        # Seed database

# ========== PM2 Commands ==========
pm2 status                       # Lihat status process
pm2 logs sso-portal              # Lihat logs
pm2 restart sso-portal           # Restart process
pm2 stop sso-portal              # Stop process
pm2 start sso-portal             # Start process

# ========== Database Commands ==========
psql -U sso_user -d sso_portal -c "SELECT 1;"  # Test connection
pg_dump -U sso_user sso_portal > backup.sql    # Backup database

# ========== Nginx Commands ==========
systemctl status nginx           # Cek status
systemctl reload nginx           # Reload config
systemctl restart nginx          # Restart nginx

# ========== File Commands ==========
cat /www/wwwroot/portal.hqmedan.com/.env       # Lihat .env
nano /www/wwwroot/portal.hqmedan.com/.env      # Edit .env
ls -la /www/wwwroot/portal.hqmedan.com         # List files
```

---

## 📞 Support & Resources

- **aaPanel Official**: https://www.aapanel.com/
- **Node.js Docs**: https://nodejs.org/docs/
- **PM2 Docs**: https://pm2.keymetrics.io/
- **Prisma Docs**: https://www.prisma.io/docs/
- **Let's Encrypt**: https://letsencrypt.org/
- **Nginx Docs**: https://nginx.org/en/docs/

---

## 🎉 Selesai!

Aplikasi SSO Portal Anda sekarang berjalan di `https://portal.hqmedan.com` dengan SSL certificate! 

**Next Steps:**
1. Login dengan credentials admin
2. Test semua fitur SSO
3. Setup monitoring di aaPanel
4. Configure backup otomatis
5. Integrasikan aplikasi lain dengan portal

---

**Status**: ✅ **Ready for Production!**

Jika ada pertanyaan atau masalah, refer ke bagian **Troubleshooting** di atas.
