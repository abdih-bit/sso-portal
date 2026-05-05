<?php
require_once __DIR__ . '/../db_connect.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    // Hanya Head Admin yang boleh finalisasi
    if (!isset($data['currentUserJabatan']) || $data['currentUserJabatan'] !== 'Head Admin') {
        json_response(['success' => false, 'message' => 'Anda tidak memiliki izin untuk finalisasi batch. Hanya Head Admin yang diizinkan.'], 403);
    }

    // Konversi format tanggal dari dd/mm/yyyy ke yyyy-mm-dd
    $parts = explode('/', $data['creation_date']);
    $creation_date_sql = sprintf('%s-%s-%s', $parts[2], $parts[1], $parts[0]);

    $stmt = $pdo->prepare(
        "INSERT INTO arsync_berita_acara
            (batch_id, nomor_ba, creation_date, business_area, business_area_name,
             sales_office, sales_office_name, petugas, cutoff_date,
             system_qty, opname_qty, difference_qty,
             system_amount, opname_amount, difference_amount, is_finalized)
         VALUES
            (:batch_id, :nomor_ba, :creation_date, :business_area, :business_area_name,
             :sales_office, :sales_office_name, :petugas, :cutoff_date,
             :system_qty, :opname_qty, :difference_qty,
             :system_amount, :opname_amount, :difference_amount, :is_finalized)"
    );
    $stmt->execute([
        ':batch_id'           => (int)$data['batch_id'],
        ':nomor_ba'           => $data['nomor_ba'],
        ':creation_date'      => $creation_date_sql,
        ':business_area'      => $data['business_area'],
        ':business_area_name' => $data['business_area_name'] ?? '',
        ':sales_office'       => $data['sales_office'],
        ':sales_office_name'  => $data['sales_office_name'] ?? '',
        ':petugas'            => $data['petugas'],
        ':cutoff_date'        => $data['cutoff_date'],
        ':system_qty'         => (int)$data['system_qty'],
        ':opname_qty'         => (int)$data['opname_qty'],
        ':difference_qty'     => (int)$data['difference_qty'],
        ':system_amount'      => (float)$data['system_amount'],
        ':opname_amount'      => (float)$data['opname_amount'],
        ':difference_amount'  => (float)$data['difference_amount'],
        ':is_finalized'       => (int)$data['is_finalized'],
    ]);

    $ba_id = (int)$pdo->lastInsertId();

    // Finalisasi batch terkait
    $upd = $pdo->prepare("UPDATE arsync_batches SET is_finalized = 1 WHERE id = :id");
    $upd->execute([':id' => (int)$data['batch_id']]);

    echo json_encode(['success' => true, 'id' => $ba_id]);

} elseif ($method === 'GET') {
    $stmt = $pdo->query("SELECT * FROM arsync_berita_acara ORDER BY creation_date DESC, id DESC");
    echo json_encode($stmt->fetchAll());
}
