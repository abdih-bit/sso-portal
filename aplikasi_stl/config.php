<?php
// =============================================================
// Konfigurasi Aplikasi Serah Terima Laporan (STL)
// Isi nilai di bawah sesuai dengan konfigurasi server Anda.
// =============================================================

// URL internal SSO Portal (server-to-server, tidak perlu HTTPS)
// Gunakan localhost karena PHP dan Node.js berada di server yang sama
define('SSO_INTERNAL_URL', 'http://localhost:3000');

// Client ID & Secret dari SSO Portal
// Di-generate oleh setup-apps.js
define('SSO_CLIENT_ID',     'b3f1a2e4-7c8d-4e5f-9a0b-1c2d3e4f5a6b');
define('SSO_CLIENT_SECRET', 'e7f8a9b0-1c2d-4e5f-8a7b-6c5d4e3f2a1b');

// URL publik SSO Portal (untuk redirect login dari browser)
define('SSO_PUBLIC_URL', 'https://portal.hqmedan.com');

// Slug aplikasi yang didaftarkan di SSO Portal
define('SSO_APP_SLUG', 'serah-terima');

// Koneksi PostgreSQL — sama dengan yang digunakan SSO Portal
// Format: pgsql:host=HOST;port=PORT;dbname=DBNAME
define('DB_DSN',      'pgsql:host=localhost;port=5433;dbname=sso_portal');
define('DB_USERNAME', 'sso_user');
define('DB_PASSWORD', 'P@ssW0rd!!');

// Konfigurasi session PHP
define('SESSION_NAME',     'STL_SESSION');
define('SESSION_LIFETIME', 86400); // 24 jam
