# 🚀 Quick Reference: Deploy SSO Portal ke VPS dengan aaPanel

## ⏱️ Waktu Estimasi: 30-45 menit

---

## 📋 PRE-DEPLOYMENT (Sebelum Deploy)

### Di Local Machine:
- [ ] Git repository sudah siap
- [ ] `.env.example` atau `.env.production.example` sudah dibuat
- [ ] `package.json` dan dependencies sudah lengkap
- [ ] Prisma schema sudah final
- [ ] Database migration sudah tested

### Di VPS:
- [ ] aaPanel sudah terinstall
- [ ] Domain sudah DNS pointing ke VPS IP
- [ ] SSH access sudah siap
- [ ] PostgreSQL sudah terinstall

---

## 🔑 LANGKAH-LANGKAH DEPLOYMENT

### **1️⃣ LOGIN KE VPS VIA SSH**
```bash
ssh root@VPS_IP
# atau
ssh root@domain.com
```

### **2️⃣ CLONE REPOSITORY**
```bash
cd /www/wwwroot
git clone https://github.com/your-username/sso-portal.git portal.hqmedan.com
cd portal.hqmedan.com
```

**Alternatif**: Upload via SFTP jika tidak menggunakan Git

### **3️⃣ BUAT DATABASE**
Option A: Via Command Line
```bash
sudo -u postgres psql <<EOF
CREATE USER sso_user WITH PASSWORD 'your-secure-password';
CREATE DATABASE sso_portal OWNER sso_user;
GRANT ALL PRIVILEGES ON DATABASE sso_portal TO sso_user;
EOF
```

Option B: Via aaPanel
- Buka aaPanel Dashboard → Database → Add Database
- Catat credentials: username, password, database name

### **4️⃣ SETUP ENVIRONMENT (.env)**
```bash
cd /www/wwwroot/portal.hqmedan.com
cp deployment/.env.production.example .env
nano .env
```

**Harus diisi:**
```
DATABASE_URL=postgresql://sso_user:password@localhost:5432/sso_portal
JWT_SECRET=random-string-min-32-chars
SESSION_SECRET=random-string-min-32-chars
APP_URL=https://portal.hqmedan.com
SMTP_HOST=smtp.gmail.com
SMTP_USER=your-email@gmail.com
SMTP_PASS=your-app-password
ADMIN_EMAIL=admin@hqmedan.com
ADMIN_PASSWORD=temporary-password
```

**Generate secret:**
```bash
node -e "console.log(require('crypto').randomBytes(32).toString('hex'))"
```

**Simpan permissions:**
```bash
chmod 600 .env
chown www:www .env
```

### **5️⃣ INSTALL DEPENDENCIES**
```bash
cd /www/wwwroot/portal.hqmedan.com
npm install --production
npx prisma generate
```

### **6️⃣ SETUP DATABASE**
```bash
npx prisma migrate deploy
# (Optional) node src/database/seed.js
```

### **7️⃣ INSTALL PM2 GLOBALLY**
```bash
npm install -g pm2
pm2 startup
```

### **8️⃣ START APPLICATION VIA PM2**
```bash
cd /www/wwwroot/portal.hqmedan.com
pm2 start src/server.js --name sso-portal
pm2 save
```

**Verify:**
```bash
pm2 status
pm2 logs sso-portal
```

### **9️⃣ SETUP NGINX REVERSE PROXY (Via aaPanel)**
1. aaPanel Dashboard → Websites → Select `portal.hqmedan.com`
2. Click Settings (gear icon)
3. Tab: **Reverse Proxy**
4. Click **Add Reverse Proxy**:
   - Name: `node`
   - Target: `127.0.0.1:3000`
   - Save

**Verify Nginx config:**
```bash
nginx -t
systemctl reload nginx
```

### **🔟 SETUP SSL CERTIFICATE (Via aaPanel)**
1. Websites → Select domain → SSL tab
2. Click **Add SSL**
3. Select **Let's Encrypt**
4. Click **Request Certificate**
5. Wait for success message (~2 minutes)

**Manual test:**
```bash
curl -I https://portal.hqmedan.com
```

### **1️⃣1️⃣ VERIFY DEPLOYMENT**
```bash
# Check PM2 process
pm2 status

# Check logs
pm2 logs sso-portal

# Test database
psql -U sso_user -d sso_portal -c "SELECT 1;"

# Test API
curl http://127.0.0.1:3000
curl https://portal.hqmedan.com

# Check Nginx
curl -I https://portal.hqmedan.com
```

---

## 🔒 SECURITY SETUP

### Firewall
```bash
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

### File Permissions
```bash
cd /www/wwwroot/portal.hqmedan.com
chown -R www:www .
chmod 755 .
chmod 600 .env
```

### Backup .env
```bash
cp .env .env.backup
chmod 600 .env.backup
```

---

## 📊 MONITORING & MAINTENANCE

### View Logs
```bash
# Real-time logs
pm2 logs sso-portal

# Last N lines
tail -100 /www/wwwroot/portal.hqmedan.com/logs/pm2.log

# Nginx error
tail -50 /www/server/nginx/logs/error.log
```

### Restart Application
```bash
pm2 restart sso-portal
# atau
pm2 restart all
```

### Update Code
```bash
cd /www/wwwroot/portal.hqmedan.com
git pull origin main
npm install --production
npx prisma migrate deploy
pm2 restart sso-portal
```

### Database Backup
```bash
pg_dump -U sso_user sso_portal > backup_$(date +%Y%m%d).sql
# atau
pg_dump -U sso_user sso_portal | gzip > backup_$(date +%Y%m%d).sql.gz
```

### Check Disk Space
```bash
df -h
du -sh /www/wwwroot/portal.hqmedan.com
```

---

## 🆘 TROUBLESHOOTING

### PM2 Process tidak jalan
```bash
pm2 logs sso-portal
pm2 delete sso-portal
pm2 start /www/wwwroot/portal.hqmedan.com/src/server.js --name sso-portal
```

### 502 Bad Gateway
```bash
# Cek process
netstat -tulnp | grep 3000
# Cek Nginx config
nginx -t
# Restart Nginx
systemctl restart nginx
```

### Database Connection Error
```bash
# Check .env DATABASE_URL
cat /www/wwwroot/portal.hqmedan.com/.env | grep DATABASE_URL

# Test connection
psql -U sso_user -d sso_portal -c "SELECT 1;"

# Check PostgreSQL status
systemctl status postgresql
```

### Port 3000 Sudah Digunakan
```bash
lsof -i :3000
kill -9 <PID>
# atau gunakan port berbeda di .env PORT=3001
```

### CORS/SSL Issues
- Edit `.env` sesuaikan `APP_URL` dan `CORS_ORIGIN`
- Restart: `pm2 restart sso-portal`

### Out of Memory
```bash
# Check memory usage
free -h
# Increase swap jika perlu
```

---

## ✅ DEPLOYMENT CHECKLIST

- [ ] Repository di-clone di `/www/wwwroot/portal.hqmedan.com`
- [ ] `.env` file dibuat dengan konfigurasi lengkap
- [ ] Database dibuat dan migration berhasil
- [ ] `npm install` selesai
- [ ] `npm install -g pm2` selesai
- [ ] PM2 process running (`pm2 status`)
- [ ] Nginx reverse proxy configured
- [ ] SSL certificate aktif (HTTPS)
- [ ] Test akses https://domain.com → tidak ada error
- [ ] Admin login berhasil
- [ ] Database connection OK
- [ ] Logs tidak ada error
- [ ] Firewall configured
- [ ] Backup strategy ditentukan

---

## 📞 HELPFUL RESOURCES

- **aaPanel Docs**: https://www.aapanel.com/
- **Prisma Docs**: https://www.prisma.io/docs/
- **PM2 Docs**: https://pm2.keymetrics.io/
- **Nginx Proxy**: https://nginx.org/en/docs/http/ngx_http_proxy_module.html

---

## 🎯 AUTOMATED DEPLOYMENT

Jika ingin faster deployment, gunakan script:

```bash
# SSH ke VPS
ssh root@VPS_IP

# Download dan jalankan script
cd /root
wget https://raw.githubusercontent.com/your-username/sso-portal/main/deployment/deploy-aapanel.sh
chmod +x deploy-aapanel.sh
./deploy-aapanel.sh portal.hqmedan.com main
```

Script ini otomatis:
✓ Clone repository
✓ Install dependencies
✓ Setup database
✓ Configure PM2
✓ Setup permissions
✓ Run tests
✓ Display status

---

**Status**: ✅ READY TO DEPLOY!

Selamat deploy! 🚀
