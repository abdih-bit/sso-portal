<?php
// api/logout.php
// Hancurkan session PHP dan redirect ke logout SSO Portal.

require_once __DIR__ . '/../db_connect.php';

session_destroy();

json_response(['status' => 'success', 'message' => 'Logout berhasil.']);
