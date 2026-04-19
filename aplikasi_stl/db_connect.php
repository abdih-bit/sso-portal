<?php
// =============================================================
// Bootstrap — konfigurasi, session, koneksi PDO/PostgreSQL
// Dipanggil oleh semua endpoint API di folder api/
// =============================================================

error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/config.php';

// --- SESSION ---
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'secure'   => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// --- CORS ---
$allowed_origins = [SSO_INTERNAL_URL, SSO_PUBLIC_URL];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
} else {
    header("Access-Control-Allow-Origin: " . SSO_PUBLIC_URL);
}
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// --- KONEKSI PDO PostgreSQL ---
try {
    $pdo = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Koneksi database gagal.']);
    exit();
}

// --- HELPER FUNCTIONS ---

/** Kirim response JSON dan stop eksekusi */
function json_response($data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data);
    exit();
}

/** Pastikan ada session aktif; kembalikan data user atau kirim 401 */
function require_auth(): array {
    if (!isset($_SESSION['stl_user'])) {
        json_response(['status' => 'error', 'message' => 'Sesi tidak ditemukan. Silakan login melalui portal.'], 401);
    }
    return $_SESSION['stl_user'];
}

/** Ambil data user dari session (nullable) */
function get_session_user(): ?array {
    return $_SESSION['stl_user'] ?? null;
}

