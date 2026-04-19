<?php
// =============================================================
// Konfigurasi Aplikasi Serah Terima Laporan (STL)
// Nilai diambil dari environment variable (diset via Nginx fastcgi_param).
// Fallback ke nilai default untuk development lokal.
// =============================================================

// URL internal SSO Portal (server-to-server, tidak perlu HTTPS)
define('SSO_INTERNAL_URL', getenv('SSO_PORTAL_URL') ?: 'http://localhost:3000');

// Client ID & Secret — harus cocok dengan entri di tabel applications SSO Portal
define('SSO_CLIENT_ID',     getenv('SSO_STL_CLIENT_ID') ?: 'serah-terima-client');
define('SSO_CLIENT_SECRET', getenv('SSO_STL_CLIENT_SECRET') ?: 'serah-terima-secret-CHANGE-ME');

// URL publik SSO Portal (untuk redirect login dari browser)
define('SSO_PUBLIC_URL', getenv('SSO_PUBLIC_URL') ?: 'https://portal.hqmedan.com');

// Slug aplikasi yang didaftarkan di SSO Portal
define('SSO_APP_SLUG', 'serah-terima');

// Koneksi PostgreSQL — database yang sama dengan SSO Portal
define('DB_DSN',      getenv('STL_DB_DSN') ?: 'pgsql:host=localhost;port=5432;dbname=sso_portal');
define('DB_USERNAME', getenv('STL_DB_USER') ?: 'SSO_Project');
define('DB_PASSWORD', getenv('STL_DB_PASS') ?: 'P@ssw0rd!!');

// Konfigurasi session PHP
define('SESSION_NAME',     'STL_SESSION');
define('SESSION_LIFETIME', 86400); // 24 jam
