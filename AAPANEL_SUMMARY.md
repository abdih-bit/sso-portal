# ✅ RINGKASAN LENGKAP: Deploy SSO Portal ke portal.hqmedan.com via aaPanel

## 🎯 Tujuan
Menjalankan SSO Portal di domain `portal.hqmedan.com` menggunakan aaPanel dengan akses HTTPS dan database PostgreSQL.

---

## 📚 Dokumentasi yang Telah Dibuat

### 1. **AAPANEL_SETUP_GUIDE.md** ← PANDUAN UTAMA
   - Langkah demi langkah setup lengkap
   - Dari domain preparation sampai SSL setup
   - 12 step detail dengan command examples
   - Troubleshooting dan monitoring guide

### 2. **AAPANEL_QUICK_SETUP.md** ← REFERENSI CEPAT
   - Ringkasan command penting
   - Checklist quick reference
   - Common issues & solutions
   - Access points & credentials

### 3. **AAPANEL_VISUAL_FLOW.md** ← VISUALISASI
   - Arsitektur sistem diagram
   - Deployment timeline (35 menit)
   - Interactive checklist dengan status
   - Workflow diagram & success indicators

### 4. **AAPANEL_TROUBLESHOOTING.md** ← PROBLEM SOLVING
   - 9 masalah paling umum
   - Diagnosis & detailed solutions
   - Emergency recovery checklist
   - Support resources

---

## 🚀 Quick Start (5 Menit Untuk Memulai)

### Langkah 1: Di VPS (SSH)
```bash
ssh root@YOUR_VPS_IP
cd /www/wwwroot/portal.hqmedan.com
git clone https://github.com/YOUR_USERNAME/sso-portal.git .
```

### Langkah 2: Setup Environment
```bash
cp .env.example .env
nano .env
# Edit dengan:
# DATABASE_URL=postgresql://sso_user:PASSWORD@localhost:5432/sso_portal
# NODE_ENV=production
# APP_URL=https://portal.hqmedan.com
```

### Langkah 3: Install & Setup DB
```bash
npm install --omit=dev
npx prisma generate
npx prisma migrate deploy
```

### Langkah 4: Via aaPanel
- Setup PM2 Process → Command: `npm start`
- Setup Nginx Reverse Proxy → 127.0.0.1:3000
- Request SSL Certificate → Let's Encrypt

### Langkah 5: Test
```bash
curl https://portal.hqmedan.com
# Seharusnya return login page atau valid HTTP response
```

---

## 📋 Sistem Requirements

**Hardware:**
- Minimum 512 MB RAM (recommended 1 GB+)
- 5 GB disk space
- 1 core processor

**Software:**
- Linux OS (Ubuntu 20.04+ atau CentOS 8+)
- aaPanel installed
- Node.js v18+
- PostgreSQL 12+
- Nginx

**Network:**
- Domain `portal.hqmedan.com` pointing ke VPS
- Port 22 (SSH) open
- Port 80 (HTTP) open
- Port 443 (HTTPS) open
- Port 3000 (Node.js) internal only

---

## 🎯 Arsitektur Sistem

```
Internet
  ↓
Domain: portal.hqmedan.com (HTTPS)
  ↓
Nginx Reverse Proxy (127.0.0.1:80/443)
  ↓
Node.js Application (127.0.0.1:3000)
  ├─ Express.js Server
  ├─ SSO Portal Logic
  └─ API Routes
  ↓
PostgreSQL Database (127.0.0.1:5432)
  └─ sso_portal database
```

---

## 📊 Deployment Phases (Total ~35 minutes)

| Phase | Time | What |
|-------|------|------|
| 1. Persiapan | 5 min | Domain DNS, aaPanel setup |
| 2. Infrastruktur | 10 min | Website, DB, project upload |
| 3. Konfigurasi | 10 min | .env, npm install, migrations |
| 4. Launching | 5 min | PM2, Nginx, SSL |
| 5. Verifikasi | 5 min | Testing & confirmation |

---

## ✅ Success Criteria

Deployment sukses jika:

```
✅ Bisa akses https://portal.hqmedan.com
✅ Login page tampil dengan HTTPS (green padlock)
✅ Database terhubung (check logs: "✅ Database connected")
✅ PM2 process status "online"
✅ No error messages di pm2 logs
✅ Bisa login dengan admin credentials
```

---

## 🔑 Important Credentials to Save

```
VPS Root Password        : [YOUR_PASSWORD]
aaPanel Username         : [YOUR_USERNAME]
aaPanel Password         : [YOUR_PASSWORD]
Database Name            : sso_portal
Database User            : sso_user
Database Password        : [YOUR_PASSWORD]
Domain                   : portal.hqmedan.com
VPS IP                   : [YOUR_IP]
aaPanel URL              : http://[YOUR_IP]:8888
Application URL          : https://portal.hqmedan.com
```

**💾 SAVE THESE IN SAFE PLACE!**

---

## 🛠️ Common Commands Reference

```bash
# === SSH ke VPS ===
ssh root@YOUR_VPS_IP

# === Project Management ===
cd /www/wwwroot/portal.hqmedan.com
git pull origin main
npm install --omit=dev

# === Database ===
psql -U sso_user -d sso_portal -c "SELECT 1;"
npx prisma migrate deploy
npx prisma studio

# === Process Management ===
pm2 status
pm2 logs sso-portal
pm2 restart sso-portal

# === Web Server ===
systemctl status nginx
systemctl reload nginx
systemctl restart nginx

# === Monitoring ===
pm2 monit
pm2 logs sso-portal --follow
tail -f /www/server/nginx/logs/error.log
```

---

## 🎓 Learning Resources

Jika ingin memahami lebih dalam:

1. **aaPanel Official Documentation**
   - https://www.aapanel.com/

2. **Node.js Best Practices**
   - https://nodejs.org/en/docs/

3. **Express.js Guide**
   - https://expressjs.com/

4. **PostgreSQL Basics**
   - https://www.postgresql.org/docs/

5. **Nginx Configuration**
   - https://nginx.org/en/docs/

6. **PM2 Process Manager**
   - https://pm2.keymetrics.io/

---

## 🚨 Emergency Procedures

### If Application Down:
```bash
# 1. Check status
pm2 status

# 2. View logs
pm2 logs sso-portal

# 3. Restart
pm2 restart sso-portal

# 4. If still down, check database
psql -U sso_user -d sso_portal -c "SELECT 1;"

# 5. If all else fails, restore from backup
git reset --hard origin/main
npm install --omit=dev
pm2 restart sso-portal
```

### If Database Down:
```bash
# 1. Check PostgreSQL
systemctl status postgresql

# 2. Start if not running
systemctl start postgresql

# 3. Test connection
psql -U sso_user -d sso_portal -c "SELECT 1;"

# 4. If corrupted, restore from backup
dropdb -U sso_user sso_portal
psql -U sso_user sso_portal < /backup/sso_portal_backup.sql
```

### If Domain Not Accessible:
```bash
# 1. Check DNS
nslookup portal.hqmedan.com

# 2. Check Nginx
systemctl status nginx

# 3. Check firewall
ufw status
ufw allow 80
ufw allow 443

# 4. Reload Nginx
systemctl reload nginx
```

---

## 📞 Support & Help

**Jika mengalami masalah:**

1. **Refer ke dokumentasi yang ada:**
   - `AAPANEL_TROUBLESHOOTING.md` untuk problem solving
   - `AAPANEL_QUICK_SETUP.md` untuk quick reference
   - `AAPANEL_SETUP_GUIDE.md` untuk detail lengkap

2. **Check Logs:**
   ```bash
   pm2 logs sso-portal
   tail -f /www/server/nginx/logs/error.log
   psql -U sso_user -d sso_portal -c "SELECT 1;"
   ```

3. **Community Support:**
   - aaPanel: https://www.aapanel.com/
   - Node.js: https://nodejs.org/community
   - PostgreSQL: https://www.postgresql.org/support

---

## 📈 Next Steps Setelah Deployment

1. **Setup Monitoring**
   - Check aaPanel monitoring dashboard
   - Setup log rotation
   - Monitor database performance

2. **Backup Strategy**
   - Automatic database backup (daily)
   - Application backup via Git
   - Keep offline backups

3. **Security Hardening**
   - Regular security updates
   - Monitor SSL certificate expiry
   - Update firewall rules

4. **Performance Optimization**
   - Monitor CPU/Memory usage
   - Optimize database queries
   - Cache configuration

5. **Application Testing**
   - Test all SSO features
   - Integrate external apps
   - Load testing

6. **Documentation**
   - Document custom configs
   - Keep deployment notes
   - Record credentials securely

---

## 🎉 Congratulations!

Jika Anda sudah sampai di sini dan aplikasi running, berarti:

✅ Portal sudah accessible di `https://portal.hqmedan.com`
✅ Database tersetup dengan baik
✅ SSL/HTTPS sudah aktif
✅ Process management terconfig
✅ Application running smoothly

**Sekarang siap untuk:**
- Production usage
- User management
- Application integration
- Monitoring & scaling

---

## 📝 File Documentation Map

```
SSO Portal/
├── AAPANEL_SETUP_GUIDE.md          ← Panduan Utama (12 steps)
├── AAPANEL_QUICK_SETUP.md          ← Quick Reference (commands)
├── AAPANEL_VISUAL_FLOW.md          ← Visualisasi (diagrams)
├── AAPANEL_TROUBLESHOOTING.md      ← Problem Solving (solutions)
├── DEPLOYMENT_GUIDE.md             ← Original deployment guide
├── QUICK_DEPLOY_GUIDE.md           ← Quick deployment steps
├── TROUBLESHOOTING.md              ← General troubleshooting
└── README.md                       ← Project info
```

**Rekomendasi Membaca:**
1. Start with: `AAPANEL_SETUP_GUIDE.md`
2. Reference: `AAPANEL_QUICK_SETUP.md`
3. Visual: `AAPANEL_VISUAL_FLOW.md`
4. Problems: `AAPANEL_TROUBLESHOOTING.md`

---

## 🔄 Version History

| Date | Change | Status |
|------|--------|--------|
| 2026-04-14 | Created complete documentation | ✅ Current |
| | Added quick reference | ✅ |
| | Added visual flow diagrams | ✅ |
| | Added troubleshooting guide | ✅ |

---

## ⚠️ Important Notes

1. **Never commit `.env` to Git** - Keep secrets safe
2. **Always backup before updates** - Database recovery is critical
3. **Monitor logs regularly** - Catch issues early
4. **Keep system updated** - Security patches important
5. **Document changes** - For future reference

---

## 💡 Pro Tips

- Use `pm2 monit` to watch resources in real-time
- Keep at least weekly database backups
- Monitor SSL certificate expiry (Let's Encrypt: 90 days)
- Use staging environment for testing before production
- Implement rate limiting for API endpoints
- Keep application logs for 30+ days

---

**Last Updated:** April 14, 2026  
**Status:** ✅ Production Ready  
**Version:** 1.0.0

---

**Selamat! Portal Anda sudah siap di production! 🎉**
