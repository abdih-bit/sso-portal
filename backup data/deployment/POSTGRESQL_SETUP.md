# 🗄️ PostgreSQL Setup Guide untuk SSO Portal

## 📋 Daftar Isi
1. [Installation](#installation)
2. [Initial Setup](#initial-setup)
3. [Database & User Creation](#database--user-creation)
4. [Backup & Restore](#backup--restore)
5. [Performance Tuning](#performance-tuning)
6. [Security](#security)

---

## Installation

### Ubuntu 20.04 / 22.04

```bash
# Update packages
sudo apt update
sudo apt upgrade -y

# Install PostgreSQL
sudo apt install -y postgresql postgresql-contrib

# Verify installation
psql --version
sudo systemctl status postgresql
```

### CentOS / RHEL 8

```bash
# Install PostgreSQL
sudo dnf install -y postgresql-server postgresql-contrib

# Initialize database
sudo postgresql-setup initdb

# Start service
sudo systemctl start postgresql
sudo systemctl enable postgresql
```

---

## Initial Setup

### Connect to PostgreSQL

```bash
# As root
sudo -u postgres psql

# Inside psql, you'll see: postgres=#
```

### Change postgres user password

```bash
# Inside psql:
ALTER USER postgres WITH PASSWORD 'your-secure-password';
\q

# Or via command line:
sudo -u postgres psql -c "ALTER USER postgres WITH PASSWORD 'your-secure-password';"
```

---

## Database & User Creation

### Method 1: Via Command Line (Recommended)

```bash
# Create user with password
sudo -u postgres createuser sso_user
sudo -u postgres psql -c "ALTER USER sso_user WITH PASSWORD 'your-secure-password';"

# Create database with owner
sudo -u postgres createdb -O sso_user sso_portal

# Grant all privileges
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE sso_portal TO sso_user;"
sudo -u postgres psql -d sso_portal -c "GRANT ALL ON SCHEMA public TO sso_user;"
```

### Method 2: Via Interactive psql

```bash
# Connect as postgres user
sudo -u postgres psql

# Inside psql:
CREATE USER sso_user WITH PASSWORD 'your-secure-password';
CREATE DATABASE sso_portal OWNER sso_user;
GRANT ALL PRIVILEGES ON DATABASE sso_portal TO sso_user;
GRANT ALL ON SCHEMA public TO sso_portal TO sso_user;
\q
```

### Verify Setup

```bash
# List users
sudo -u postgres psql -c "\du"

# List databases
sudo -u postgres psql -c "\l"

# Test connection as sso_user
psql -U sso_user -d sso_portal -c "SELECT 1;"
```

---

## Connection Configuration

### PostgreSQL Configuration File

Location: `/etc/postgresql/14/main/postgresql.conf`

Enable remote connections (if needed):

```bash
sudo nano /etc/postgresql/14/main/postgresql.conf

# Find and change:
# listen_addresses = 'localhost'
listen_addresses = '*'  # Accept all IPs (be careful!)
# or
listen_addresses = '127.0.0.1,192.168.x.x'  # Specific IPs
```

### Edit pg_hba.conf for authentication

Location: `/etc/postgresql/14/main/pg_hba.conf`

```bash
sudo nano /etc/postgresql/14/main/pg_hba.conf

# For local socket connections:
local   sso_portal      sso_user                                    md5

# For TCP connections (replace 192.168.x.x with your network):
host    sso_portal      sso_user    127.0.0.1/32                   md5
host    sso_portal      sso_user    192.168.1.0/24                 md5
```

### Reload configuration

```bash
sudo systemctl reload postgresql
```

---

## Backup & Restore

### Single Database Backup

```bash
# Plain SQL format
pg_dump -U sso_user -d sso_portal > backup_sso_portal.sql

# Compressed format (recommended)
pg_dump -U sso_user -d sso_portal | gzip > backup_sso_portal.sql.gz

# With timestamp
pg_dump -U sso_user -d sso_portal > backup_sso_portal_$(date +%Y%m%d_%H%M%S).sql.gz
```

### Backup with password file

```bash
# Create .pgpass file
echo "localhost:5432:sso_portal:sso_user:your-password" > ~/.pgpass
chmod 600 ~/.pgpass

# Now backup without password prompt
pg_dump -h localhost -U sso_user -d sso_portal | gzip > backup.sql.gz
```

### Full cluster backup

```bash
# Backup all databases
pg_dumpall -U postgres | gzip > backup_all_$(date +%Y%m%d).sql.gz

# Backup directory format (faster restore, better compression)
pg_dump -U sso_user -d sso_portal -Fd -f /path/to/backup_dir/
```

### Restore from backup

```bash
# From plain SQL
psql -U sso_user -d sso_portal < backup_sso_portal.sql

# From gzip compressed
gunzip -c backup_sso_portal.sql.gz | psql -U sso_user -d sso_portal

# From directory format
pg_restore -U sso_user -d sso_portal /path/to/backup_dir/
```

### Automated daily backup (cron)

```bash
# Create backup script
sudo nano /usr/local/bin/backup_sso_portal.sh
```

**Content:**
```bash
#!/bin/bash
BACKUP_DIR="/backup/postgresql"
DATE=$(date +%Y%m%d_%H%M%S)
RETENTION_DAYS=30

# Create backup directory
mkdir -p $BACKUP_DIR

# Backup database
pg_dump -U sso_user -d sso_portal | gzip > $BACKUP_DIR/sso_portal_$DATE.sql.gz

# Keep only last N days of backups
find $BACKUP_DIR -name "sso_portal_*.sql.gz" -mtime +$RETENTION_DAYS -delete

# Log
echo "[$(date)] Backup completed: sso_portal_$DATE.sql.gz" >> /var/log/sso_portal_backup.log
```

**Make executable:**
```bash
sudo chmod +x /usr/local/bin/backup_sso_portal.sh

# Add to crontab (run at 2 AM daily)
sudo crontab -e

# Add line:
0 2 * * * /usr/local/bin/backup_sso_portal.sh
```

### Remote backup (to another server)

```bash
# Backup and send to remote server via SSH
pg_dump -U sso_user -d sso_portal | gzip | \
  ssh user@backup-server "cat > /backup/sso_portal_$(date +%Y%m%d).sql.gz"
```

---

## Performance Tuning

### Check current settings

```bash
# View memory settings
sudo -u postgres psql -c "SHOW shared_buffers;"
sudo -u postgres psql -c "SHOW effective_cache_size;"
sudo -u postgres psql -c "SHOW work_mem;"
```

### Optimize for small VPS (512MB-2GB RAM)

```bash
sudo nano /etc/postgresql/14/main/postgresql.conf

# Settings:
shared_buffers = 256MB          # 25% of RAM
effective_cache_size = 1GB      # 50-75% of RAM
maintenance_work_mem = 64MB
work_mem = 16MB
random_page_cost = 1.1          # For SSD, use 1.1

# WAL settings
wal_buffers = 16MB
default_statistics_target = 100

# Connection pooling (if using PgBouncer)
max_connections = 200
```

### Optimize for medium VPS (4GB-8GB RAM)

```bash
shared_buffers = 1GB            # 25% of RAM
effective_cache_size = 4GB      # 50% of RAM
maintenance_work_mem = 256MB
work_mem = 64MB
```

### Reload after changes

```bash
sudo systemctl reload postgresql

# Verify changes
sudo -u postgres psql -c "SHOW shared_buffers;"
```

### Analyze query performance

```bash
# Connect to database
psql -U sso_user -d sso_portal

# Inside psql, analyze slow queries:
EXPLAIN ANALYZE SELECT * FROM users WHERE email = 'test@example.com';

# See actual execution time:
\timing on
SELECT * FROM users;
```

---

## Security

### Create role with limited permissions

```bash
# Read-only user
sudo -u postgres psql -c "CREATE USER app_reader WITH PASSWORD 'password';"
sudo -u postgres psql -d sso_portal -c "GRANT CONNECT ON DATABASE sso_portal TO app_reader;"
sudo -u postgres psql -d sso_portal -c "GRANT USAGE ON SCHEMA public TO app_reader;"
sudo -u postgres psql -d sso_portal -c "GRANT SELECT ON ALL TABLES IN SCHEMA public TO app_reader;"
```

### Remove public schema permissions

```bash
sudo -u postgres psql -d sso_portal -c "REVOKE ALL ON SCHEMA public FROM PUBLIC;"
sudo -u postgres psql -d sso_portal -c "GRANT USAGE ON SCHEMA public TO sso_user;"
```

### Enable SSL for connections

```bash
# Generate self-signed certificate (if not using Let's Encrypt)
sudo openssl req -new -x509 -days 365 -nodes \
  -out /etc/postgresql/server.crt \
  -keyout /etc/postgresql/server.key
sudo chown postgres:postgres /etc/postgresql/server.*
sudo chmod 600 /etc/postgresql/server.key

# Enable SSL in postgresql.conf
sudo nano /etc/postgresql/14/main/postgresql.conf
# ssl = on
# ssl_cert_file = '/etc/postgresql/server.crt'
# ssl_key_file = '/etc/postgresql/server.key'

# Reload
sudo systemctl reload postgresql

# Connect with SSL
psql -U sso_user -d sso_portal "sslmode=require"
```

### Monitor connections

```bash
# See current connections
sudo -u postgres psql -c "SELECT * FROM pg_stat_activity;"

# Kill idle connections
sudo -u postgres psql -c "
  SELECT pg_terminate_backend(pid)
  FROM pg_stat_activity
  WHERE state = 'idle' 
    AND query_start < now() - interval '30 minutes';
"
```

---

## Monitoring

### Check database size

```bash
# Size of specific database
sudo -u postgres psql -c "SELECT pg_size_pretty(pg_database_size('sso_portal'));"

# Size of all databases
sudo -u postgres psql -c "SELECT datname, pg_size_pretty(pg_database_size(datname)) FROM pg_database ORDER BY pg_database_size(datname) DESC;"

# Size of specific table
sudo -u postgres psql -d sso_portal -c "SELECT schemaname, tablename, pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) FROM pg_tables WHERE schemaname='public' ORDER BY pg_total_relation_size(schemaname||'.'||tablename) DESC;"
```

### Check table statistics

```bash
psql -U sso_user -d sso_portal

# Inside psql:
\dt+                  -- Show tables with sizes
\di+                  -- Show indexes with sizes
SELECT * FROM pg_stat_user_tables;
```

### Vacuum and analyze

```bash
# Manual vacuum (removes dead rows)
sudo -u postgres psql -d sso_portal -c "VACUUM ANALYZE;"

# Or specific table:
sudo -u postgres psql -d sso_portal -c "VACUUM ANALYZE users;"

# Autovacuum status:
sudo -u postgres psql -d sso_portal -c "SHOW autovacuum;"
```

### Enable query logging for slow queries

```bash
# Edit postgresql.conf
sudo nano /etc/postgresql/14/main/postgresql.conf

# Uncomment and adjust:
log_min_duration_statement = 1000  # Log queries taking > 1 second
log_statement = 'all'              # Log all statements
log_duration = on

# Reload
sudo systemctl reload postgresql

# View logs
tail -f /var/log/postgresql/postgresql-14-main.log
```

---

## Troubleshooting

### psql: error: could not connect to server

```bash
# Check if PostgreSQL is running
sudo systemctl status postgresql

# Start if not running
sudo systemctl start postgresql

# Check if listening
sudo netstat -tulnp | grep postgres
```

### "password authentication failed"

```bash
# Reset password
sudo -u postgres psql -c "ALTER USER sso_user WITH PASSWORD 'new_password';"

# Or edit pg_hba.conf to use trust method temporarily:
sudo nano /etc/postgresql/14/main/pg_hba.conf
# Change md5 to trust for sso_user

# Reload
sudo systemctl reload postgresql

# Change password via psql
sudo -u postgres psql
ALTER USER sso_user WITH PASSWORD 'new_password';

# Change pg_hba.conf back to md5
# Reload again
```

### "permission denied for schema public"

```bash
# Grant permissions
sudo -u postgres psql -d sso_portal -c "GRANT ALL ON SCHEMA public TO sso_user;"
```

### Disk space issues

```bash
# Check disk usage
df -h

# Find large objects in PostgreSQL
sudo -u postgres psql -c "
  SELECT datname, pg_size_pretty(pg_database_size(datname)) 
  FROM pg_database 
  ORDER BY pg_database_size(datname) DESC LIMIT 10;
"

# Cleanup old WAL files
sudo -u postgres /usr/lib/postgresql/14/bin/pg_controldata /var/lib/postgresql/14/main | grep "Latest checkpoint location"
```

---

## Quick Commands Reference

```bash
# Connect as default user
psql -U sso_user -d sso_portal

# List databases
\l

# List tables
\dt

# List users
\du

# Describe table
\d users

# Show table size
SELECT schemaname, tablename, pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) FROM pg_tables WHERE schemaname='public';

# Count rows in all tables
SELECT schemaname, tablename, n_live_tup FROM pg_stat_user_tables;

# Exit psql
\q
```

---

**Status**: ✅ PostgreSQL Setup Guide Complete!

Semoga panduan ini membantu dalam setup PostgreSQL untuk SSO Portal! 🚀
