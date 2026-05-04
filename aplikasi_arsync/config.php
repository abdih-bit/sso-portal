<?php
// =============================================================
// Konfigurasi Aplikasi ARsync Portal
// Isi nilai di bawah sesuai dengan konfigurasi server Anda.
// =============================================================

// URL internal SSO Portal (server-to-server, tidak perlu HTTPS)
// Gunakan localhost karena PHP dan Node.js berada di server yang sama
define('SSO_INTERNAL_URL', 'http://localhost:3000');

// Client ID & Secret dari SSO Portal
// Di-generate oleh setup-apps.js
define('SSO_CLIENT_ID',     'arsync-client-hqmedan-2025');
define('SSO_CLIENT_SECRET', 'arsync-secret-hqmedan-2025-rT7nQ');

// URL publik SSO Portal (untuk redirect login dari browser)
define('SSO_PUBLIC_URL', 'https://portal.hqmedan.com');

// Slug aplikasi yang didaftarkan di SSO Portal
define('SSO_APP_SLUG', 'arsync');

// Koneksi PostgreSQL — shared dengan SSO Portal
// Format: pgsql:host=HOST;port=PORT;dbname=DBNAME
define('DB_DSN',      'pgsql:host=localhost;port=5433;dbname=sso_portal');
define('DB_USERNAME', 'sso_user');
define('DB_PASSWORD', 'P@ssW0rd!!');

// Konfigurasi session PHP
define('SESSION_NAME',     'ARSYNC_SESSION');
define('SESSION_LIFETIME', 86400); // 24 jam
