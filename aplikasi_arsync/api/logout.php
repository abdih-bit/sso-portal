<?php
// api/logout.php
// Hancurkan session PHP dan kembalikan URL SSO Portal untuk redirect.

require_once __DIR__ . '/../db_connect.php';

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}
session_destroy();

json_response([
    'status'         => 'success',
    'sso_public_url' => SSO_PUBLIC_URL,
]);
