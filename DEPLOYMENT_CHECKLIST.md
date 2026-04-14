# ✅ SSO PORTAL DEPLOYMENT CHECKLIST

> **Use this checklist untuk memastikan semua setup dengan benar**

---

## 📋 PRE-DEPLOYMENT PHASE

### 🖥️ Infrastructure Setup
- [ ] VPS sudah ready (Ubuntu 20.04+ atau equivalent)
- [ ] aaPanel sudah terinstall dan accessible
- [ ] Domain sudah registered dan DNS pointing ke VPS IP
- [ ] SSH access tested dan working
- [ ] Firewall already memblokir unnecessary ports
- [ ] Enough disk space (20GB minimum available)
- [ ] RAM sufficient (512MB minimum, 2GB recommended)

### 📱 Application Preparation
- [ ] Git repository ready dan clean
- [ ] .env.production.example file sudah dibuat
- [ ] package.json semua dependencies correct
- [ ] Prisma schema final dan tested locally
- [ ] Database migration files in order
- [ ] No uncommitted changes
- [ ] All branches merged ke main/master

### 📚 Documentation Review
- [ ] Read START_HERE.md
- [ ] Choose deployment method (quick/detailed)
- [ ] Skim relevant sections
- [ ] Bookmark TROUBLESHOOTING.md

---

## 🚀 DEPLOYMENT PHASE

### Step 1: SSH & Repository
- [ ] SSH ke VPS berhasil
- [ ] Git clone berjalan lancar
- [ ] Repository di `/www/wwwroot/portal.hqmedan.com`
- [ ] All files present (check `ls -la`)

**Command:**
```bash
ssh root@VPS_IP
cd /www/wwwroot
git clone https://repo-url.git portal.hqmedan.com
cd portal.hqmedan.com
```

### Step 2: Environment Configuration
- [ ] Copy .env template: `cp deployment/.env.production.example .env`
- [ ] Edit .env dengan nano/vi
- [ ] DATABASE_URL correctly configured
- [ ] JWT_SECRET generated (min 32 chars)
- [ ] SESSION_SECRET generated (min 32 chars)
- [ ] APP_URL set to HTTPS domain
- [ ] SMTP credentials filled
- [ ] ADMIN_EMAIL & ADMIN_PASSWORD set
- [ ] Permissions correct: `chmod 600 .env`
- [ ] Ownership correct: `chown www:www .env`

**Verify:**
```bash
cat /www/wwwroot/portal.hqmedan.com/.env | head -20
```

### Step 3: Database Setup
- [ ] PostgreSQL running: `systemctl status postgresql`
- [ ] Database `sso_portal` created
- [ ] User `sso_user` created
- [ ] Password set correctly
- [ ] Permissions granted
- [ ] Connection testable:
  ```bash
  psql -U sso_user -d sso_portal -c "SELECT 1;"
  ```

### Step 4: Dependencies Installation
- [ ] Node.js v18+ installed: `node -v`
- [ ] npm installed: `npm -v`
- [ ] npm install complete: `npm install --production`
- [ ] No errors during installation
- [ ] Prisma client generated: `npx prisma generate`
- [ ] node_modules folder exists

**Check:**
```bash
npm list 2>/dev/null | head -10
```

### Step 5: Database Migration
- [ ] Check migration status: `npx prisma migrate status`
- [ ] All pending migrations listed
- [ ] Run migrations: `npx prisma migrate deploy`
- [ ] No errors during migration
- [ ] Database tables created
- [ ] (Optional) Seed database: `node src/database/seed.js`

**Verify:**
```bash
psql -U sso_user -d sso_portal -c "\dt"
```

### Step 6: PM2 Process Manager
- [ ] npm install -g pm2 complete
- [ ] pm2 startup successful
- [ ] Start application: `pm2 start src/server.js --name sso-portal`
- [ ] Process status: `pm2 status` shows "online"
- [ ] No error in logs: `pm2 logs sso-portal`
- [ ] Save PM2: `pm2 save`

**Verify:**
```bash
pm2 status
pm2 logs sso-portal | tail -20
```

### Step 7: Nginx Reverse Proxy
- [ ] aaPanel website created for domain
- [ ] Nginx config accessible
- [ ] Reverse proxy added: 127.0.0.1:3000
- [ ] Config syntax valid: `nginx -t`
- [ ] Nginx reloaded: `systemctl reload nginx`

**Verify:**
```bash
curl http://127.0.0.1:3000
# Should return HTML or JSON, not connection error
```

### Step 8: SSL Certificate
- [ ] Domain accessible via HTTP (redirect working)
- [ ] Let's Encrypt certificate requested via aaPanel
- [ ] Certificate generation successful (wait 2-3 min)
- [ ] HTTPS domain accessible
- [ ] No SSL errors in browser
- [ ] Certificate auto-renewal configured

**Verify:**
```bash
curl -I https://portal.hqmedan.com
# Should show HTTP/2 200, not 502 or SSL errors
```

### Step 9: Application Testing
- [ ] API accessible: `curl https://portal.hqmedan.com`
- [ ] Database connected (check logs)
- [ ] Admin login page loads
- [ ] Login dengan admin credentials works
- [ ] Dashboard loads setelah login
- [ ] User profile visible
- [ ] No console errors

**Verify:**
```bash
curl -I https://portal.hqmedan.com
# HTTP/2 200 OK
pm2 logs sso-portal | grep -i error
# Should be empty or only info logs
```

---

## 🔒 SECURITY HARDENING

### Firewall Configuration
- [ ] Port 22 (SSH) allowed from trusted IPs only
- [ ] Port 80 (HTTP) allowed (for redirect)
- [ ] Port 443 (HTTPS) allowed for all
- [ ] Other ports blocked
- [ ] UFW enabled: `ufw status`

**Commands:**
```bash
ufw allow 22/tcp from 192.168.1.0/24
ufw allow 80/tcp
ufw allow 443/tcp
ufw enable
```

### File & Directory Permissions
- [ ] `.env` permission: 600
- [ ] `.env` owner: www:www
- [ ] Application directory: 755
- [ ] Logs directory writable by www user
- [ ] No world-readable secrets

**Verify:**
```bash
ls -la /www/wwwroot/portal.hqmedan.com/.env
# -rw------- 1 www www
```

### Database Security
- [ ] Postgres user has limited permissions
- [ ] Password is strong (min 16 chars)
- [ ] Public schema permissions restricted
- [ ] No unnecessary user accounts
- [ ] Local socket connections default

### SSL/TLS Security
- [ ] HTTPS enforced (HTTP redirects)
- [ ] HSTS header enabled (Nginx config)
- [ ] Certificate valid (not self-signed)
- [ ] Auto-renewal working
- [ ] No mixed content warnings

---

## 📊 MONITORING & LOGS

### Log Setup
- [ ] PM2 logs accessible: `pm2 logs sso-portal`
- [ ] Nginx access logs exist
- [ ] Nginx error logs exist
- [ ] Application logs directory: `/www/wwwroot/.../logs/`
- [ ] Log rotation configured

**Check:**
```bash
ls -la /www/wwwroot/portal.hqmedan.com/logs/
```

### Health Monitoring
- [ ] PM2 process monitored
- [ ] Memory usage acceptable
- [ ] CPU usage normal (idle ~0%)
- [ ] Disk space available
- [ ] No OOM (Out of Memory) errors

**Monitor:**
```bash
pm2 monit
free -h
df -h
```

### Performance Check
- [ ] Page load time reasonable (<2s)
- [ ] No N+1 queries in logs
- [ ] Database queries performing
- [ ] No memory leaks (check after 1 hour)

---

## 💾 BACKUP SETUP

### Database Backup
- [ ] Manual backup working:
  ```bash
  pg_dump -U sso_user -d sso_portal | gzip > backup_$(date +%Y%m%d).sql.gz
  ```
- [ ] Backup file created
- [ ] Size reasonable (several MB)
- [ ] Stored safely (not on same disk if possible)

### Automated Backup (Cron)
- [ ] Cron job configured:
  ```bash
  0 2 * * * /usr/local/bin/backup_sso_portal.sh
  ```
- [ ] Script executable
- [ ] Backup runs daily
- [ ] Backup success logged

### Restore Test (Important!)
- [ ] Tested restore from backup
- [ ] Data intact after restore
- [ ] Restore procedure documented

---

## 🔗 APPLICATION VERIFICATION

### Functionality Test
- [ ] Login page loads
- [ ] Login with admin works
- [ ] User dashboard accessible
- [ ] Profile page shows user data
- [ ] Database queries working
- [ ] Email sending working (if configured)
- [ ] Password reset flow working
- [ ] Logout working

### API Endpoints Test
- [ ] GET / returns dashboard
- [ ] GET /health returns 200 (if exists)
- [ ] API endpoints accessible
- [ ] CORS headers correct (for integrations)

**Test:**
```bash
curl -I https://portal.hqmedan.com
curl https://portal.hqmedan.com | head -50
```

### Integration Ready
- [ ] SSO API endpoints working
- [ ] Token generation working
- [ ] Client ID/Secret configured
- [ ] Callback URL registered
- [ ] Ready for app integration

---

## 📈 PERFORMANCE OPTIMIZATION

### Already Configured
- [x] Nginx reverse proxy active
- [x] Gzip compression enabled
- [x] Static file caching headers set
- [x] Database connection pooling ready

### Performance Checks
- [ ] First load time acceptable
- [ ] Subsequent loads faster (caching)
- [ ] No unnecessary requests
- [ ] Images optimized
- [ ] CSS/JS minified

---

## 🆘 TROUBLESHOOTING READINESS

### Ready for Issues
- [ ] TROUBLESHOOTING.md bookmarked
- [ ] Know log locations
- [ ] Know how to restart services
- [ ] Have SSH access
- [ ] Can read error messages

### If Issues Occur
1. [ ] Check logs first: `pm2 logs sso-portal`
2. [ ] Search TROUBLESHOOTING.md
3. [ ] Try suggested solution
4. [ ] Verify fix works
5. [ ] Document what was fixed

---

## 📝 DOCUMENTATION

### Completed
- [x] All deployments documented
- [x] Configuration saved
- [x] Credentials stored safely
- [x] Admin credentials noted
- [x] Database credentials saved
- [x] Backup procedure documented

### To Do
- [ ] Create runbooks for team
- [ ] Document custom configurations
- [ ] Create on-call procedures
- [ ] Share documentation with team

---

## 🎯 GO-LIVE CHECKLIST

### Final Verification
- [ ] All checkboxes above completed
- [ ] No critical errors in logs
- [ ] Performance acceptable
- [ ] Backup working
- [ ] Team notified and trained

### Pre-Launch
- [ ] Announce maintenance window (if needed)
- [ ] Final backup taken
- [ ] Team on standby
- [ ] Monitoring active

### Launch
- [ ] Go ahead signal received
- [ ] Users can access
- [ ] Monitor closely for 1 hour
- [ ] Check logs regularly

### Post-Launch (24 Hours)
- [ ] No errors in logs
- [ ] Performance stable
- [ ] All features working
- [ ] Users happy
- [ ] Mark as production stable

---

## 📋 SIGN-OFF

```
Deployment Date: _______________
Deployed By: _______________
Reviewed By: _______________
Status: _______________
Notes: 
_________________________________
_________________________________
_________________________________
```

---

## 🎉 COMPLETION

When you've checked all items:

✅ **Congratulations! SSO Portal is successfully deployed!**

- 📱 Application is live
- 🔒 Security is hardened
- 💾 Backups are working
- 📊 Monitoring is active
- 📚 Documentation is complete

---

## 🔄 REGULAR MAINTENANCE

### Daily (Automated)
- [ ] Backups running
- [ ] Logs being rotated
- [ ] Monitoring alerts active

### Weekly
- [ ] Review error logs
- [ ] Check disk space
- [ ] Verify backup integrity

### Monthly
- [ ] Update dependencies
- [ ] Review security
- [ ] Performance analysis

### Quarterly
- [ ] Security audit
- [ ] Capacity planning
- [ ] Documentation update

---

**Congratulations on your successful deployment! 🚀**

For any issues, refer to TROUBLESHOOTING.md.
For integration help, refer to SSO_INTEGRATION_GUIDE.md.

---

**Last Updated**: April 14, 2026
**Version**: 1.0.0
**Status**: ✅ Production Ready
