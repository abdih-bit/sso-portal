# 📚 SSO Portal Deployment Documentation

> **Panduan lengkap untuk mendeploy SSO Portal ke VPS menggunakan aaPanel**

---

## 📖 Daftar Lengkap File Dokumentasi

### 1. **QUICK_DEPLOY_GUIDE.md** ⭐ START HERE!
   - **Untuk**: Deployment cepat dalam 11 langkah sederhana
   - **Waktu**: 30-45 menit
   - **Isi**:
     - Langkah-langkah deployment yang singkat dan jelas
     - Pre-deployment checklist
     - Troubleshooting cepat
     - Useful commands
   - **Mulai dengan**: Baca file ini dulu!

### 2. **DEPLOYMENT_GUIDE.md** 📖 LENGKAP & DETAIL
   - **Untuk**: Pemahaman mendalam tentang deployment
   - **Isi**:
     - Step-by-step dari awal hingga akhir (9 step)
     - Konfigurasi di aaPanel
     - Database setup
     - Nginx reverse proxy
     - SSL certificate
     - Security hardening
     - Monitoring & maintenance
   - **Baca**: Setelah QUICK_DEPLOY_GUIDE untuk pemahaman lebih dalam

### 3. **QUICK_DEPLOY_GUIDE.md** + **deploy-aapanel.sh** 🚀 OTOMASI
   - **Untuk**: Deployment otomatis (minimal manual)
   - **Cara pakai**:
     ```bash
     ssh root@VPS_IP
     chmod +x deployment/deploy-aapanel.sh
     ./deployment/deploy-aapanel.sh portal.hqmedan.com main
     ```
   - **Otomatis setup**: Git, dependencies, database, PM2, permissions

### 4. **TROUBLESHOOTING.md** 🔧 ISSUE SOLVING
   - **Untuk**: Mengatasi masalah umum
   - **Isi**: 10 masalah paling umum dengan solusi lengkap
     - 502 Bad Gateway
     - Database connection error
     - Port already in use
     - SSL certificate issues
     - Memory/CPU issues
     - CORS errors
     - Email/SMTP problems
     - Dan lainnya...

### 5. **POSTGRESQL_SETUP.md** 🗄️ DATABASE
   - **Untuk**: Setup & maintenance PostgreSQL
   - **Isi**:
     - Installation & setup
     - Database & user creation
     - Backup & restore procedures
     - Performance tuning
     - Security configuration
     - Monitoring

### 6. **deployment/aapanel-nginx-config.conf** ⚙️ CONFIG
   - **Untuk**: Konfigurasi Nginx siap pakai
   - **Cara pakai**:
     - Copy ke `/www/server/nginx/conf/vhost/portal.hqmedan.com.conf`
     - Sesuaikan domain
     - Reload Nginx

### 7. **deployment/.env.production.example** 🔐 ENVIRONMENT
   - **Untuk**: Template file environment
   - **Isi**: Semua variable yang perlu dikonfigurasi
   - **Cara pakai**: Copy ke `.env` dan edit sesuai kebutuhan

---

## 🎯 Recommended Reading Order

### Untuk User Baru (First Time Deployment)

1. ✅ **Baca**: QUICK_DEPLOY_GUIDE.md (5 menit)
   - Pahami overview deployment

2. 📖 **Baca**: DEPLOYMENT_GUIDE.md Step 1-4 (10 menit)
   - Pahami persiapan VPS dan aaPanel

3. 🖥️ **Lakukan**: Semua langkah di QUICK_DEPLOY_GUIDE.md (30 menit)
   - Jalankan deployment manual

4. ✔️ **Verifikasi**: Testing & verification section (5 menit)
   - Test aplikasi berjalan

5. 🔒 **Setup**: Security hardening (10 menit)
   - Konfigurasi firewall & permissions

### Untuk User Experienced

1. 🚀 **Gunakan**: deploy-aapanel.sh script (10 menit)
   - Deployment otomatis

2. 📖 **Baca**: DEPLOYMENT_GUIDE.md sections yang relevan saja
   - Reference jika ada pertanyaan

3. 🔧 **Bookmark**: TROUBLESHOOTING.md
   - Untuk reference cepat jika ada issue

---

## 🚀 Quick Start (3 Steps)

### Step 1: Prepare
```bash
# Di local machine
git add .
git commit -m "Ready to deploy"
git push origin main
```

### Step 2: Deploy via Script
```bash
# SSH ke VPS
ssh root@VPS_IP

# Clone & setup otomatis
cd /www/wwwroot
git clone https://github.com/your-username/sso-portal.git portal.hqmedan.com
cd portal.hqmedan.com
chmod +x deployment/deploy-aapanel.sh
./deployment/deploy-aapanel.sh portal.hqmedan.com main
```

### Step 3: Configure
```bash
# Via aaPanel GUI:
# 1. Websites → Select domain → SSL → Add Let's Encrypt
# 2. Websites → Select domain → Configuration → Reverse Proxy → 127.0.0.1:3000
# 3. Test: https://portal.hqmedan.com
```

---

## 📋 Deployment Checklist

```
PRE-DEPLOYMENT:
☐ VPS & aaPanel siap
☐ Domain pointing ke VPS
☐ SSH access tested
☐ Repository siap (main branch clean)
☐ .env.production.example sudah dibuat

DEPLOYMENT:
☐ Repository di-clone
☐ .env dikonfigurasi lengkap
☐ Database dibuat & migration sukses
☐ npm install selesai
☐ PM2 process running

POST-DEPLOYMENT:
☐ Nginx reverse proxy configured
☐ SSL certificate aktif
☐ HTTPS test berhasil
☐ Admin login berhasil
☐ Database connection OK
☐ Logs tidak ada error
☐ Firewall configured
☐ Backup strategy ditentukan
```

---

## 🔗 File Locations di VPS

```
/www/wwwroot/portal.hqmedan.com/
├── .env                              # Environment variables (RAHASIA!)
├── src/
│   ├── server.js                     # Entry point
│   ├── app.js                        # Express configuration
│   ├── controllers/
│   ├── routes/
│   ├── middleware/
│   ├── database/
│   └── utils/
├── prisma/
│   ├── schema.prisma                 # Database schema
│   └── migrations/                   # Migration files
├── public/                           # Static files
├── logs/
│   ├── pm2.log                       # PM2 logs
│   ├── pm2-error.log                 # PM2 errors
│   ├── access.log                    # Nginx access
│   └── error.log                     # Nginx errors
├── node_modules/                     # Dependencies
├── package.json
└── README.md

/www/server/nginx/conf/vhost/
└── portal.hqmedan.com.conf           # Nginx configuration

/etc/postgresql/14/main/
├── postgresql.conf                   # PostgreSQL main config
└── pg_hba.conf                       # Connection authentication

/backup/
└── sso_portal_YYYYMMDD.sql.gz       # Database backups
```

---

## 🆘 Troubleshooting Quick Links

- **502 Bad Gateway?** → Lihat TROUBLESHOOTING.md #1
- **Database error?** → Lihat TROUBLESHOOTING.md #2
- **Port 3000 error?** → Lihat TROUBLESHOOTING.md #3
- **SSL issues?** → Lihat TROUBLESHOOTING.md #4
- **Database migration?** → Lihat TROUBLESHOOTING.md #5
- **Process crash?** → Lihat TROUBLESHOOTING.md #7
- **High CPU?** → Lihat TROUBLESHOOTING.md #8
- **CORS error?** → Lihat TROUBLESHOOTING.md #9
- **Email not working?** → Lihat TROUBLESHOOTING.md #10

---

## 📞 Quick Commands

```bash
# SSH ke VPS
ssh root@VPS_IP

# View logs
pm2 logs sso-portal
tail -100 /www/wwwroot/portal.hqmedan.com/logs/pm2.log
tail -50 /www/server/nginx/logs/error.log

# Restart aplikasi
pm2 restart sso-portal

# Check status
pm2 status
systemctl status postgresql
systemctl status nginx

# Test API
curl https://portal.hqmedan.com

# Database backup
pg_dump -U sso_user -d sso_portal | gzip > backup_$(date +%Y%m%d).sql.gz

# View environment
cat /www/wwwroot/portal.hqmedan.com/.env
```

---

## 🎓 Learning Resources

### aaPanel
- Official: https://www.aapanel.com/
- Docs: https://blog.aapanel.com/

### Node.js & Express
- Express.js: https://expressjs.com/
- PM2: https://pm2.keymetrics.io/
- Node.js Best Practices: https://github.com/goldbergyoni/nodebestpractices

### Database
- PostgreSQL Docs: https://www.postgresql.org/docs/
- Prisma ORM: https://www.prisma.io/docs/
- pg (Node driver): https://node-postgres.com/

### Security
- OWASP Top 10: https://owasp.org/www-project-top-ten/
- Let's Encrypt: https://letsencrypt.org/
- Nginx Security: https://www.nginx.com/blog/security/

### Monitoring & Logging
- PM2 Monitoring: https://pm2.io/
- ELK Stack: https://www.elastic.co/what-is/elk-stack
- Prometheus: https://prometheus.io/

---

## 📊 Performance Tips

1. **Optimize Node.js**
   ```bash
   pm2 start ... --max-memory-restart 500M
   ```

2. **Enable Gzip compression**
   - Already configured in Nginx config

3. **Caching**
   - Static files cache 7 days
   - API response caching (if applicable)

4. **Database optimization**
   - Baca POSTGRESQL_SETUP.md #Performance Tuning

5. **CDN untuk static files** (optional)
   - Bisa integrasikan CloudFlare atau Bunny CDN

---

## 🔐 Security Checklist

- [ ] Firewall rules configured
- [ ] SSH key-based auth setup
- [ ] .env file permissions correct (600)
- [ ] Database user memiliki limited permissions
- [ ] SSL certificate active (HTTPS only)
- [ ] Admin password sudah diganti
- [ ] Regular backups scheduled
- [ ] Logs di-monitor untuk suspicious activity
- [ ] Rate limiting configured
- [ ] CORS properly configured

---

## 🔄 Update & Maintenance

### Regular Tasks
```bash
# Update aplikasi
cd /www/wwwroot/portal.hqmedan.com
git pull origin main
npm install --production
npx prisma migrate deploy
pm2 restart sso-portal

# Update dependencies
npm outdated
npm update

# Database maintenance
vacuum analyze;
reindex database;

# Check for security updates
npm audit
npm audit fix
```

---

## 📝 Notes

- **Backup database regularly** - Lihat POSTGRESQL_SETUP.md #Backup
- **Monitor logs** - Aktifkan monitoring untuk alerts
- **Keep dependencies updated** - Jalankan `npm update` monthly
- **Test backup restore** - Pastikan backup bisa di-restore
- **Document custom changes** - Catat semua perubahan dari template

---

## 🎯 Next Steps After Deployment

1. **Configure Email** - Setup SMTP untuk notification & password reset
2. **Setup Monitoring** - Integrasikan dengan monitoring system
3. **Create Admin Accounts** - Buat akun admin permanent
4. **Integrate Other Apps** - Integrate aplikasi lain ke SSO
5. **Setup Analytics** - Track user activity (optional)
6. **Configure Backups** - Setup automated database backups
7. **Performance Monitoring** - Setup APM (Application Performance Monitoring)

---

## 💡 Tips & Tricks

- **Jangan commit .env ke repository!**
- **Generate strong passwords** untuk database & JWT
- **Test di staging dulu** sebelum production
- **Monitor memory usage** terutama di small VPS
- **Keep logs tidy** dengan log rotation
- **Regular security updates** sangat penting
- **Document everything** untuk future reference

---

## 🎉 Selamat!

Jika Anda sudah sampai di sini dan aplikasi running dengan baik, selamat! 🎊

Untuk pertanyaan atau masalah, lihat TROUBLESHOOTING.md atau setup health check script untuk monitoring.

---

**Last Updated**: April 14, 2026
**Version**: 1.0.0
**Maintained by**: Your Team

Semoga deployment lancar! 🚀
