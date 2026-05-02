<?php
// api/session_check.php
// Cek apakah ada sesi PHP aktif; kembalikan info user dan SSO config.

require_once __DIR__ . '/../db_connect.php';

$user = get_session_user();

if ($user) {
    // Cari area Head AR dalam grup PT yang sama (untuk Two-Way area tujuan)
    if (!empty($user['area_id'])) {
        try {
            $stmt = $pdo->prepare(
                "SELECT u.area_id AS head_ar_area_id, a.area_name AS head_ar_area_name
                 FROM stl_users u
                 JOIN stl_areas a ON u.area_id = a.area_id
                 JOIN stl_areas my_area ON my_area.area_id = ?
                 WHERE u.jabatan = 'Head AR'
                   AND a.parent_ho_id = my_area.parent_ho_id
                 LIMIT 1"
            );
            $stmt->execute([$user['area_id']]);
            $headAr = $stmt->fetch();
            if ($headAr) {
                $user['head_ar_area_id']   = $headAr['head_ar_area_id'];
                $user['head_ar_area_name'] = $headAr['head_ar_area_name'];
            }
        } catch (PDOException $e) {
            // Non-critical; lanjut tanpa head_ar_area
        }
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
