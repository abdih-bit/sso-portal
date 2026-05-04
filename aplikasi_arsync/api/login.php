<?php
// Login lokal dinonaktifkan.
// Autentikasi dilakukan melalui SSO Portal.
http_response_code(403);
header('Content-Type: application/json');
echo json_encode([
    'status'  => 'error',
    'message' => 'Login lokal dinonaktifkan. Silakan login melalui SSO Portal.',
]);


