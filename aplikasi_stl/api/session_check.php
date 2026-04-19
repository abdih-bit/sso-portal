<?php
// api/session_check.php
// Cek apakah ada sesi PHP aktif; kembalikan info user dan SSO config.

require_once __DIR__ . '/../db_connect.php';

$user = get_session_user();

if ($user) {
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
