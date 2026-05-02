<?php
// api/logout.php
// Hancurkan session PHP dan kembalikan URL SSO Portal untuk redirect.

require_once __DIR__ . '/../db_connect.php';

session_destroy();

json_response([
    'status'         => 'success',
    'message'        => 'Logout berhasil.',
    'sso_public_url' => SSO_PUBLIC_URL,
]);
