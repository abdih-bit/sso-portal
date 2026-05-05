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
