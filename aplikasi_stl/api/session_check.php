<?php
// api/session_check.php
// Cek apakah ada sesi PHP aktif; kembalikan info user dan SSO config.

require_once __DIR__ . '/../db_connect.php';

$user = get_session_user();

if ($user) {
    // Cari HO area untuk PT user (untuk Two-Way area tujuan)
    if (!empty($user['pt'])) {
        try {
            // Cari HO area berdasarkan nama PT dalam area_name
            $stmtHo = $pdo->prepare(
                "SELECT area_id, area_name FROM stl_areas
                 WHERE is_ho = TRUE AND area_name ILIKE ?
                 LIMIT 1"
            );
            $stmtHo->execute(['%' . $user['pt'] . '%']);
            $hoArea = $stmtHo->fetch();
            if ($hoArea) {
                $user['ho_area_id']   = $hoArea['area_id'];
                $user['ho_area_name'] = $hoArea['area_name'];
            }
        } catch (PDOException $e) { /* non-critical */ }
    }
    // Fallback: cari HO via parent_ho_id area user
    if (empty($user['ho_area_id']) && !empty($user['area_id'])) {
        try {
            $stmtHo2 = $pdo->prepare(
                "SELECT ho.area_id, ho.area_name
                 FROM stl_areas my_area
                 JOIN stl_areas ho ON ho.area_id = my_area.parent_ho_id
                 WHERE my_area.area_id = ? AND ho.is_ho = TRUE
                 LIMIT 1"
            );
            $stmtHo2->execute([$user['area_id']]);
            $hoArea2 = $stmtHo2->fetch();
            if ($hoArea2) {
                $user['ho_area_id']   = $hoArea2['area_id'];
                $user['ho_area_name'] = $hoArea2['area_name'];
            }
        } catch (PDOException $e) { /* non-critical */ }
    }

    json_response([
        'status'         => 'success',
        'user'           => $user,
        'sso_public_url' => SSO_PUBLIC_URL,
        'sso_app_slug'   => SSO_APP_SLUG,
    ]);
} else {
    json_response([
        'status'         => 'unauthenticated',
        'sso_public_url' => SSO_PUBLIC_URL,
        'sso_app_slug'   => SSO_APP_SLUG,
    ], 401);
}
