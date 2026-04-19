<?php
require_once __DIR__ . '/../db_connect.php';
$currentUser = require_auth();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->query("SELECT * FROM stl_jasa_ekspedisi ORDER BY nama_jasa ASC");
    json_response($stmt->fetchAll());
} else {
    json_response(['message' => 'Metode tidak diizinkan.'], 405);
}

