<?php
require_once __DIR__ . '/../db_connect.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $batch_id      = (int)($data['batch_id'] ?? 0);
    $barcode       = $data['barcode']    ?? '';
    $status        = $data['status']     ?? '';
    $scanned_by    = $data['scanned_by'] ?? '';
    $scan_data_json = json_encode($data['data'] ?? []);

    // Cek status yang sudah ada di database
    $check = $pdo->prepare("SELECT status FROM arsync_scan_data WHERE batch_id = :batch_id AND barcode = :barcode");
    $check->execute([':batch_id' => $batch_id, ':barcode' => $barcode]);
    $existing = $check->fetch();

    if ($existing) {
        $existing_status = $existing['status'];
        if ($existing_status === 'Confirmed' || $existing_status === 'Paid') {
            json_response(['success' => false, 'message' => 'Dokumen ini sudah terkonfirmasi dan tidak dapat dipindai ulang.'], 409);
        }
    }

    // INSERT ... ON CONFLICT DO UPDATE (PostgreSQL idiom untuk UPSERT)
    $stmt = $pdo->prepare(
        "INSERT INTO arsync_scan_data (batch_id, barcode, status, scanned_by, scan_data, updated_at)
         VALUES (:batch_id, :barcode, :status, :scanned_by, :scan_data, NOW())
         ON CONFLICT (batch_id, barcode) DO UPDATE
           SET status     = EXCLUDED.status,
               scanned_by = EXCLUDED.scanned_by,
               scan_data  = EXCLUDED.scan_data,
               updated_at = NOW()"
    );
    $stmt->execute([
        ':batch_id'  => $batch_id,
        ':barcode'   => $barcode,
        ':status'    => $status,
        ':scanned_by'=> $scanned_by,
        ':scan_data' => $scan_data_json,
    ]);

    echo json_encode(['success' => true, 'message' => 'Scan saved or updated.']);

} elseif ($method === 'GET' && isset($_GET['batch_id'])) {
    $batch_id = (int)$_GET['batch_id'];

    $stmt = $pdo->prepare("SELECT barcode, status, scanned_by, scan_data FROM arsync_scan_data WHERE batch_id = :batch_id");
    $stmt->execute([':batch_id' => $batch_id]);
    echo json_encode($stmt->fetchAll());
}
?>

    // Cek status yang sudah ada di database terlebih dahulu
    $check_stmt = $conn->prepare("SELECT status FROM scan_data WHERE batch_id = ? AND barcode = ?");
    $check_stmt->bind_param("is", $batch_id, $barcode);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $existing_status = $row['status'];
        // Tolak pemindaian ulang jika status sudah final (Confirmed atau Paid)
        if ($existing_status === 'Confirmed' || $existing_status === 'Paid') {
            http_response_code(409); // 409 Conflict adalah status yang tepat untuk ini
            echo json_encode(['success' => false, 'message' => 'Dokumen ini sudah terkonfirmasi dan tidak dapat dipindai ulang.']);
            $check_stmt->close();
            $conn->close();
            exit;
        }
    }
    $check_stmt->close();

    // Jika belum ada atau statusnya bisa diubah, lanjutkan dengan INSERT atau UPDATE
    $stmt = $conn->prepare("
        INSERT INTO scan_data (batch_id, barcode, status, scanned_by, scan_data) 
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        status = VALUES(status), 
        scanned_by = VALUES(scanned_by), 
        scan_data = VALUES(scan_data)
    ");
    
    if ($stmt === false) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal mempersiapkan statement: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param("issss", $batch_id, $barcode, $status, $scanned_by, $scan_data_json);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Scan saved or updated.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan pindaian: ' . $stmt->error]);
    }
    $stmt->close();

} elseif ($method === 'GET' && isset($_GET['batch_id'])) {
    $batch_id = $_GET['batch_id'];
    
    $scans = [];
    $stmt = $conn->prepare("SELECT barcode, status, scanned_by, scan_data FROM scan_data WHERE batch_id = ?");
    $stmt->bind_param("i", $batch_id);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while($row = $result->fetch_assoc()) {
            $scans[] = $row;
        }
        echo json_encode($scans);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to fetch scans.']);
    }
    $stmt->close();
}

$conn->close();
?>

