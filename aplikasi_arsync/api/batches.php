<?php
require_once __DIR__ . '/../db_connect.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (json_last_error() !== JSON_ERROR_NONE || !isset($data['batchInfo'], $data['excelData'], $data['fileName'])) {
        json_response(['success' => false, 'message' => 'Data tidak valid.'], 400);
    }

    $batchInfo     = $data['batchInfo'];
    $excel_data_json = json_encode($data['excelData']);

    if ($excel_data_json === false) {
        json_response(['success' => false, 'message' => 'Gagal meng-encode data Excel.'], 500);
    }

    $stmt = $pdo->prepare(
        "INSERT INTO arsync_batches (petugas, business_area, sales_office, cutoff_date, excel_data, excel_filename)
         VALUES (:petugas, :business_area, :sales_office, :cutoff_date, :excel_data, :filename)"
    );
    $stmt->execute([
        ':petugas'        => $batchInfo['nama'],
        ':business_area'  => $batchInfo['businessArea'],
        ':sales_office'   => $batchInfo['salesOffice'],
        ':cutoff_date'    => $batchInfo['cutoffDate'],
        ':excel_data'     => $excel_data_json,
        ':filename'       => $data['fileName'],
    ]);

    $batch_id = (int)$pdo->lastInsertId();
    echo json_encode(['success' => true, 'batch_id' => $batch_id]);

} elseif ($method === 'GET' && isset($_GET['id'])) {
    $batch_id = (int)$_GET['id'];

    $stmt = $pdo->prepare("SELECT * FROM arsync_batches WHERE id = :id");
    $stmt->execute([':id' => $batch_id]);
    $batch = $stmt->fetch();

    if (!$batch) {
        json_response(['success' => false, 'message' => 'Batch tidak ditemukan.'], 404);
    }

    if ((int)$batch['is_finalized'] === 1) {
        json_response(['success' => false, 'message' => 'Batch ini sudah difinalisasi.', 'finalized' => true], 410);
    }

    $stmt2 = $pdo->prepare("SELECT barcode, status, scanned_by, scan_data FROM arsync_scan_data WHERE batch_id = :batch_id");
    $stmt2->execute([':batch_id' => $batch_id]);
    $scans = $stmt2->fetchAll();

    $batch['scans'] = $scans;
    echo json_encode(['success' => true, 'batch' => $batch]);
}
?>

    // Validasi data
    if (json_last_error() !== JSON_ERROR_NONE || !isset($data['batchInfo'], $data['excelData'], $data['fileName'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Data tidak valid.']);
        exit;
    }
    
    $batchInfo = $data['batchInfo'];
    $excelData = $data['excelData'];
    $fileName = $data['fileName'];
    
    $excel_data_json = json_encode($excelData);
    
    if ($excel_data_json === false) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal meng-encode data Excel.']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO batches (petugas, business_area, sales_office, cutoff_date, excel_data, excel_filename) VALUES (?, ?, ?, ?, ?, ?)");
    
    if ($stmt === false) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: Gagal mempersiapkan statement. ' . $conn->error]);
        exit;
    }
    
    $stmt->bind_param(
        "ssssss", 
        $batchInfo['nama'], 
        $batchInfo['businessArea'], 
        $batchInfo['salesOffice'], 
        $batchInfo['cutoffDate'], 
        $excel_data_json, 
        $fileName
    );

    if ($stmt->execute()) {
        $batch_id = $conn->insert_id;
        echo json_encode(['success' => true, 'batch_id' => $batch_id]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal membuat batch: ' . $stmt->error]);
    }
    $stmt->close();

} elseif ($method === 'GET' && isset($_GET['id'])) {
    $batch_id = intval($_GET['id']);
    
    $batch = null;
    $scans = [];

    $stmt_batch = $conn->prepare("SELECT * FROM batches WHERE id = ?");
    $stmt_batch->bind_param("i", $batch_id);
    if ($stmt_batch->execute()) {
        $result = $stmt_batch->get_result();
        if ($result->num_rows > 0) {
            $batch = $result->fetch_assoc();
        }
    }
    $stmt_batch->close();

    if ($batch === null) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Batch tidak ditemukan.']);
        exit;
    }

    // FIX: Jika batch sudah difinalisasi, jangan lanjutkan
    if ($batch['is_finalized'] == 1) {
        http_response_code(410); // 410 Gone
        echo json_encode(['success' => false, 'message' => 'Batch ini sudah difinalisasi.', 'finalized' => true]);
        exit;
    }

    $stmt_scans = $conn->prepare("SELECT barcode, status, scanned_by, scan_data FROM scan_data WHERE batch_id = ?");
    $stmt_scans->bind_param("i", $batch_id);
    if ($stmt_scans->execute()) {
        $result = $stmt_scans->get_result();
        while($row = $result->fetch_assoc()) {
            $scans[] = $row;
        }
    }
    $stmt_scans->close();

    $batch['scans'] = $scans;
    echo json_encode(['success' => true, 'batch' => $batch]);
}

$conn->close();
?>

