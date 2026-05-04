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
?>

    // Pengecekan keamanan: Hanya Head Admin yang boleh finalisasi
    if (!isset($data['currentUserJabatan']) || $data['currentUserJabatan'] !== 'Head Admin') {
        http_response_code(403); // Forbidden
        echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki izin untuk finalisasi batch. Hanya Head Admin yang diizinkan.']);
        exit;
    }

    // Konversi format tanggal dari dd/mm/yyyy ke yyyy-mm-dd
    $creation_date_parts = explode('/', $data['creation_date']);
    $creation_date_sql = $creation_date_parts[2] . '-' . $creation_date_parts[1] . '-' . $creation_date_parts[0];

    $stmt = $conn->prepare("INSERT INTO berita_acara (batch_id, nomor_ba, creation_date, business_area, business_area_name, sales_office, sales_office_name, petugas, cutoff_date, system_qty, opname_qty, difference_qty, system_amount, opname_amount, difference_amount, is_finalized) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssssssiiddddi", 
        $data['batch_id'],
        $data['nomor_ba'], 
        $creation_date_sql, 
        $data['business_area'], 
        $data['business_area_name'],
        $data['sales_office'], 
        $data['sales_office_name'],
        $data['petugas'], 
        $data['cutoff_date'], 
        $data['system_qty'], 
        $data['opname_qty'], 
        $data['difference_qty'], 
        $data['system_amount'], 
        $data['opname_amount'], 
        $data['difference_amount'], 
        $data['is_finalized']
    );
    
    if ($stmt->execute()) {
        // Jika BA berhasil disimpan, finalisasi batch terkait
        $batch_id = $data['batch_id'];
        $update_stmt = $conn->prepare("UPDATE batches SET is_finalized = 1 WHERE id = ?");
        $update_stmt->bind_param("i", $batch_id);
        $update_stmt->execute();
        $update_stmt->close();

        echo json_encode(['success' => true, 'id' => $stmt->insert_id]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan Berita Acara: ' . $stmt->error]);
    }
    $stmt->close();

} elseif ($method === 'GET') {
    $history = [];
    $result = $conn->query("SELECT * FROM berita_acara ORDER BY creation_date DESC, id DESC");
    while($row = $result->fetch_assoc()) {
        $history[] = $row;
    }
    echo json_encode($history);
}

$conn->close();
?>

