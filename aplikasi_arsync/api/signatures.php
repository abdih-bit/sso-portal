<?php
require_once __DIR__ . '/../db_connect.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->query("SELECT position, name, title FROM arsync_signatures");
    $rows = $stmt->fetchAll();
    $signatures = [];
    foreach ($rows as $row) {
        $signatures[$row['position']] = ['name' => $row['name'], 'title' => $row['title']];
    }
    echo json_encode(['success' => true, 'data' => $signatures]);

} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        json_response(['success' => false, 'message' => 'Invalid JSON received.'], 400);
    }

    $stmt = $pdo->prepare(
        "INSERT INTO arsync_signatures (position, name, title)
         VALUES (:position, :name, :title)
         ON CONFLICT (position) DO UPDATE SET name = EXCLUDED.name, title = EXCLUDED.title"
    );

    $pdo->beginTransaction();
    try {
        foreach (['dibuat_oleh', 'diperiksa_oleh', 'disetujui_oleh'] as $position) {
            if (isset($data[$position])) {
                $stmt->execute([
                    ':position' => $position,
                    ':name'     => $data[$position]['name'] ?? '',
                    ':title'    => $data[$position]['title'] ?? '',
                ]);
            }
        }
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Data berhasil disimpan.']);
    } catch (PDOException $e) {
        $pdo->rollBack();
        json_response(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
    }
}
