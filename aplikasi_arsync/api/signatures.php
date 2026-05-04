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
?>

} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON received']);
        exit;
    }

    $conn->begin_transaction();

    try {
        $positions = ['dibuat_oleh', 'diperiksa_oleh', 'disetujui_oleh'];
        $stmt = $conn->prepare("INSERT INTO signatures (position, name, title) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), title = VALUES(title)");

        foreach ($positions as $position) {
            if (isset($data[$position])) {
                $name = $data[$position]['name'];
                $title = $data[$position]['title'];
                $stmt->bind_param("sss", $position, $name, $title);
                $stmt->execute();
            }
        }
        
        $stmt->close();
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Data berhasil disimpan.']);

    } catch (mysqli_sql_exception $exception) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $exception->getMessage()]);
    }
}

$conn->close();
?>

