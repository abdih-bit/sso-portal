<?php
require_once __DIR__ . '/../db_connect.php';

$method = $_SERVER['REQUEST_METHOD'];

// GET tanpa ?id → list batch aktif (belum difinalisasi), filter opsional by area name (dari SSO)
if ($method === 'GET' && !isset($_GET['id'])) {
    // area_name: nama area dari SSO (mis. "Sibolga", "Siantar"), bukan kode
    $areaName = isset($_GET['area_name']) ? trim($_GET['area_name']) : '';

    $where  = ['b.is_finalized = 0'];
    $params = [];

    if ($areaName !== '') {
        // Filter: batch yang business_area_name = areaName ATAU sales_office_name = areaName
        // Menggunakan OR karena satu DC bisa jadi business area atau sales office dari batch
        $where[] = '(ba.business_area_name = :area_name OR so.sales_office_name = :area_name2)';
        $params[':area_name']  = $areaName;
        $params[':area_name2'] = $areaName;
    }

    $sql = "SELECT b.id, b.petugas, b.business_area, b.sales_office, b.cutoff_date,
                   b.excel_filename, b.created_at,
                   ba.business_area_name, so.sales_office_name,
                   json_array_length(b.excel_data::json) AS total_excel_rows,
                   SUM(CASE WHEN sd.status = 'Confirmed'   THEN 1 ELSE 0 END) AS confirmed_count,
                   SUM(CASE WHEN sd.status = 'Unconfirmed' THEN 1 ELSE 0 END) AS unconfirmed_count,
                   SUM(CASE WHEN sd.status = 'Paid'        THEN 1 ELSE 0 END) AS paid_count
            FROM arsync_batches b
            LEFT JOIN arsync_business_areas ba ON ba.area_name   = b.business_area
            LEFT JOIN arsync_sales_offices  so ON so.office_name = b.sales_office
            LEFT JOIN arsync_scan_data      sd ON sd.batch_id    = b.id
            WHERE " . implode(' AND ', $where) . "
            GROUP BY b.id, b.petugas, b.business_area, b.sales_office, b.cutoff_date,
                     b.excel_filename, b.created_at, ba.business_area_name, so.sales_office_name
            ORDER BY b.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $batches = $stmt->fetchAll();
    echo json_encode(['success' => true, 'batches' => $batches]);
    exit;
}

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
