// PM2 Ecosystem Config
// Jalankan dengan: pm2 start ecosystem.config.js --env production

module.exports = {
  apps: [
    {
      name: 'sso-portal',
      script: './src/server.js',
      instances: 'max',          // Gunakan semua CPU core
      exec_mode: 'cluster',      // Cluster mode untuk multi-core
      watch: false,
      max_memory_restart: '500M',
      env: {
        NODE_ENV: 'development',
        PORT: 3000,
      },
      env_production: {
        NODE_ENV: 'production',
        PORT: 3000,
      },
      // Logging
      log_file: './logs/combined.log',
      out_file: './logs/out.log',
      error_file: './logs/error.log',
      log_date_format: 'YYYY-MM-DD HH:mm:ss',
      merge_logs: true,
      // Auto restart
      autorestart: true,
      restart_delay: 5000,
      max_restarts: 10,
    }
  ]
};
