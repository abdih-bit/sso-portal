<?php
// Proxy ke bootstrap utama di folder induk.
// File ini hanya untuk kompatibilitas backward — semua logika ada di ../db_connect.php
require_once __DIR__ . '/../db_connect.php';

// Ubah galat PHP menjadi eksepsi agar bisa ditangkap oleh exception handler
set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        // Kode galat ini tidak termasuk dalam error_reporting
        return;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Laporkan semua jenis galat
error_reporting(E_ALL);
@ini_set('display_errors', 0); // Jangan tampilkan galat ke pengguna, biarkan handler yang bekerja

// Tingkatkan batas memori dan ukuran unggahan untuk menangani file besar
@ini_set('memory_limit', '512M');
@ini_set('post_max_size', '512M');
@ini_set('upload_max_filesize', '512M');

// Izinkan akses dari origin manapun (CORS Headers)
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');    // cache for 1 day
}

// Access-Control headers diterima saat request OPTIONS
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE");
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    exit(0);
}

// Pengaturan untuk koneksi ke database MySQL di XAMPP
$servername = "localhost";
$username = "root"; // Username default untuk XAMPP
$password = "";     // Password default untuk XAMPP adalah kosong
$dbname = "arsync_db";

// Membuat koneksi
$conn = new mysqli($servername, $username, $password, $dbname);

// Memeriksa koneksi
if ($conn->connect_error) {
  // Menghentikan eksekusi dan memicu galat jika koneksi gagal
  throw new Exception("Koneksi ke database gagal: " . $conn->connect_error);
}

// Mengatur header untuk memastikan output adalah JSON
header('Content-Type: application/json');
?>

