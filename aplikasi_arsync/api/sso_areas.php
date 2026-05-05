<?php
// api/sso_areas.php
// Ambil daftar Area/DC dan Sales Office dari tabel master SSO Portal.
// Tabel ini di-share dalam database yang sama dengan arsync.

require_once __DIR__ . '/../db_connect.php';

// Hanya user yang sudah login yang boleh mengakses
$currentUser = get_session_user();
if (!$currentUser) {
    json_response(['error' => 'Unauthorized'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['error' => 'Method not allowed'], 405);
}

try {
    $areas = $pdo->query(
        "SELECT id, name, pt, is_ho FROM areas ORDER BY name ASC"
    )->fetchAll(PDO::FETCH_ASSOC);

    $salesOffices = $pdo->query(
        "SELECT s.id, s.name, a.name AS area_name
         FROM sales_offices s
         JOIN areas a ON s.area_id = a.id
         ORDER BY a.name ASC, s.name ASC"
    )->fetchAll(PDO::FETCH_ASSOC);

    json_response([
        'areas'         => $areas,
        'sales_offices' => $salesOffices,
    ]);
} catch (Exception $e) {
    json_response(['error' => 'Gagal memuat data area dari SSO Portal.'], 500);
}
