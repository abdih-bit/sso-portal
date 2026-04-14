# Panduan Deploy SSO Portal ke VPS dengan aaPanel

## 📋 Prerequisite
- VPS dengan OS Linux (Ubuntu 20.04+ atau CentOS 8+)
- aaPanel sudah terinstall
- Domain yang sudah pointing ke VPS
- Akses SSH ke VPS

---

## 🔧 Step 1: Persiapan di VPS (via aaPanel)

### 1.1 Buka aaPanel Dashboard
- Akses `http://VPS_IP:8888` atau domain Anda yang sudah dikonfigurasi
- Login dengan kredensial aaPanel

### 1.2 Buat Website Baru
1. Klik **Websites** → **Add Site**
2. Isi form:
   - **Domain**: `portal.hqmedan.com` (atau domain Anda)
   - **Path**: `/www/wwwroot/portal.hqmedan.com` (akan dibuat otomatis)
   - **PHP**: Tidak perlu (Node.js app)
   - Klik **Submit**

### 1.3 Install Node.js Runtime
1. Klik **Supervisor** (atau cari PM2 Manager)
2. Pastikan Node.js sudah terinstall:
   - Klik **Node.js** di menu samping
   - Jika belum, install versi stable (v18+)

### 1.4 Setup Database PostgreSQL
1. Klik **Database** → **Add Database**
2. Isi form:
   - **Database Name**: `sso_portal`
   - **Database User**: `sso_user`
   - **Password**: Generate password yang kuat
   - Simpan credentials ini

3. Atau gunakan SSH untuk membuat database:
```bash
sudo -u postgres createdb sso_portal
sudo -u postgres createuser sso_user
sudo -u postgres psql -c "ALTER USER sso_user WITH PASSWORD 'your-secure-password';"
sudo -u postgres psql -c "ALTER USER sso_user CREATEDB;"
```

---

## 🚀 Step 2: Upload & Setup Project

### 2.1 Upload via Git (Recommended)
SSH ke VPS:
```bash
ssh root@VPS_IP
cd /www/wwwroot/portal.hqmedan.com
git clone https://your-repo-url.git .
# atau jika sudah ada remote:
git pull origin main
```

### 2.2 Upload via SFTP (Alternative)
Jika menggunakan SFTP client:
1. FTP Host: `VPS_IP`
2. Username: `root`
3. Password: SSH password
4. Folder: `/www/wwwroot/portal.hqmedan.com`
5. Upload semua file dari local

### 2.3 Setup Environment Variables
```bash
cd /www/wwwroot/portal.hqmedan.com
cp .env.example .env  # jika ada
# Edit .env:
nano .env
```

**Isi .env dengan:**
```
# Server
NODE_ENV=production
PORT=3000
APP_URL=https://portal.hqmedan.com

# Database
DATABASE_URL=postgresql://sso_user:your-password@localhost:5432/sso_portal

# JWT
JWT_SECRET=generate-random-string-min-32-chars
JWT_EXPIRE=24h

# Email (SMTP)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your-email@gmail.com
SMTP_PASS=your-app-password
SMTP_FROM=noreply@hqmedan.com

# Session
SESSION_SECRET=generate-another-random-string

# Admin Default
ADMIN_EMAIL=admin@hqmedan.com
ADMIN_PASSWORD=temporary-password-change-later
```

**Generate secret strings:**
```bash
node -e "console.log(require('crypto').randomBytes(32).toString('hex'))"
```

---

## 📦 Step 3: Install Dependencies & Database

### 3.1 Install NPM Packages
```bash
cd /www/wwwroot/portal.hqmedan.com
npm install --production
# atau jika ingin dev tools:
npm install
```

### 3.2 Generate Prisma Client
```bash
npx prisma generate
```

### 3.3 Jalankan Database Migration
```bash
npx prisma migrate deploy
```

### 3.4 Seed Database (Optional)
```bash
node src/database/seed.js
```

---

## 🔄 Step 4: Setup Process Manager (PM2 via aaPanel)

### 4.1 Konfigurasi PM2 di aaPanel
1. Klik **Supervisor** di menu aaPanel
2. Klik **Add Process** atau **Create Process**
3. Isi form:
   - **Process Name**: `sso-portal`
   - **Run User**: `www` atau `root`
   - **Working Directory**: `/www/wwwroot/portal.hqmedan.com`
   - **Command**: `npm start` atau `node src/server.js`
   - **Autostart**: ✓ Checked
   - **Autorestart**: ✓ Checked
   - Klik **Submit**

### 4.2 Alternatif: Gunakan Ecosystem Config
Jika file `ecosystem.config.js` sudah ada:
```bash
cd /www/wwwroot/portal.hqmedan.com
pm2 start ecosystem.config.js
pm2 save
pm2 startup
```

---

## 🌐 Step 5: Setup Reverse Proxy (Nginx)

### 5.1 Konfigurasi Nginx via aaPanel
1. Buka **Websites** → Pilih domain Anda
2. Klik **Configuration** (icon pengaturan)
3. Tab **Reverse Proxy**
4. Klik **Add Reverse Proxy**:
   - **Proxy Name**: `node`
   - **Proxy IP**: `127.0.0.1:3000`
   - Klik **Submit**

### 5.2 Manual Setup (Alternative)
Edit nginx config:
```bash
nano /www/server/nginx/conf/vhost/portal.hqmedan.com.conf
```

**Tambahkan dalam block `server`:**
```nginx
location / {
    proxy_pass http://127.0.0.1:3000;
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_read_timeout 3600s;
    proxy_send_timeout 3600s;
}
```

Reload nginx:
```bash
systemctl reload nginx
```

---

## 🔒 Step 6: Setup SSL Certificate

### 6.1 Via aaPanel (Recommended)
1. **Websites** → pilih domain Anda
2. Klik **SSL** → **Add SSL**
3. Pilih **Let's Encrypt**
4. Domain otomatis terdeteksi
5. Klik **Request Certificate**
6. Tunggu sampai berhasil (biasanya 1-2 menit)

### 6.2 Auto Renewal
SSL dari Let's Encrypt via aaPanel sudah auto-renew. Cek di:
- **Websites** → pilih domain → **SSL** → lihat tanggal expire

---

## ✅ Step 7: Testing & Verification

### 7.1 Cek Status Process
```bash
pm2 status
# atau via aaPanel: Supervisor → lihat process list
```

### 7.2 Cek Logs
```bash
pm2 logs sso-portal
# atau
tail -f /www/wwwroot/portal.hqmedan.com/logs/app.log
```

### 7.3 Test Application
```bash
curl http://127.0.0.1:3000
curl -I https://portal.hqmedan.com
```

### 7.4 Test Database Connection
```bash
cd /www/wwwroot/portal.hqmedan.com
node -e "
require('dotenv').config();
const { PrismaClient } = require('@prisma/client');
const prisma = new PrismaClient();
prisma.\$queryRaw\`SELECT 1\`.then(() => {
  console.log('✅ Database connected!');
  process.exit(0);
}).catch(e => {
  console.error('❌ Database error:', e.message);
  process.exit(1);
});
"
```

---

## 🔐 Step 8: Security Hardening

### 8.1 Firewall Configuration
```bash
# SSH
ufw allow 22/tcp
# HTTP
ufw allow 80/tcp
# HTTPS
ufw allow 443/tcp
# aaPanel (optional, block public)
# ufw allow from YOUR_IP to any port 8888

ufw enable
```

### 8.2 File Permissions
```bash
cd /www/wwwroot/portal.hqmedan.com
chmod 755 .
chmod 644 package.json
chmod 644 .env
chmod 755 src
find . -name "*.js" -exec chmod 644 {} \;
```

### 8.3 Update .env Permissions
```bash
chmod 600 /www/wwwroot/portal.hqmedan.com/.env
chown www:www /www/wwwroot/portal.hqmedan.com/.env
```

---

## 📊 Step 9: Monitoring & Maintenance

### 9.1 Setup Log Rotation
```bash
nano /etc/logrotate.d/sso-portal
```

**Isi:**
```
/www/wwwroot/portal.hqmedan.com/logs/*.log {
    daily
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www www
    sharedscripts
    postrotate
        pm2 restart sso-portal > /dev/null 2>&1 || true
    endscript
}
```

### 9.2 Health Check Endpoint (Optional)
Tambah di `src/routes/portal.routes.js`:
```javascript
router.get('/health', (req, res) => {
  res.json({ status: 'OK', timestamp: new Date() });
});
```

Test:
```bash
curl https://portal.hqmedan.com/health
```

### 9.3 Backup Database
```bash
# Manual backup
pg_dump -U sso_user sso_portal > backup_$(date +%Y%m%d).sql

# Via cron (setiap jam 2 pagi)
0 2 * * * pg_dump -U sso_user sso_portal > /backup/sso_portal_$(date +\%Y\%m\%d).sql
```

---

## 🆘 Troubleshooting

### Process tidak jalan
```bash
pm2 logs sso-portal
systemctl status pm2-root
```

### Database connection error
```bash
psql -U sso_user -d sso_portal -c "SELECT 1;"
# cek DATABASE_URL di .env
```

### Nginx 502 Bad Gateway
```bash
# Cek process Node.js
netstat -tulnp | grep 3000
# Cek logs
tail -f /www/server/nginx/logs/error.log
```

### CORS Error
- Edit `src/app.js` - ubah `CORS_ORIGIN` sesuai kebutuhan
- Restart aplikasi: `pm2 restart sso-portal`

### SSL Certificate Error
- Re-request certificate di aaPanel
- Atau manual: `certbot renew --force-renewal`

---

## 📝 Checklist Deployment

- [ ] VPS & aaPanel siap
- [ ] Domain pointing ke VPS
- [ ] Website dibuat di aaPanel
- [ ] Database dibuat dan credentials tersimpan
- [ ] Project di-upload
- [ ] `.env` file dikonfigurasi dengan benar
- [ ] NPM packages installed
- [ ] Prisma migration dijalankan
- [ ] Process Manager (PM2) dikonfigurasi
- [ ] Nginx reverse proxy diatur
- [ ] SSL certificate aktif
- [ ] Application berjalan (test via curl/browser)
- [ ] Log files berjalan normal
- [ ] Firewall dikonfigurasi
- [ ] File permissions benar
- [ ] Backup strategy diterapkan

---

## 📞 Support & Resources

- **aaPanel Docs**: https://www.aapanel.com/
- **Prisma Docs**: https://www.prisma.io/docs/
- **Express.js**: https://expressjs.com/
- **PM2**: https://pm2.keymetrics.io/

---

## 🎯 Next Steps

Setelah deployment berhasil:
1. Lakukan testing semua fitur SSO
2. Integrasikan aplikasi lain ke portal
3. Setup monitoring (aaPanel sudah built-in)
4. Schedule backup database
5. Monitor logs dan performance

**Status**: ✅ Ready to Deploy!
