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
