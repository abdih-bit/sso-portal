<?php
require_once __DIR__ . '/../db_connect.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    // Hanya KA Admin (role: admin) yang boleh finalisasi
    $currentUser  = require_auth();
    $currentRole  = $currentUser['role'] ?? '';
    $allowedRoles = ['admin', 'superadmin'];
    if (!in_array($currentRole, $allowedRoles, true)) {
        json_response(['success' => false, 'message' => 'Anda tidak memiliki izin untuk finalisasi batch. Hanya KA Admin yang diizinkan.'], 403);
    }

    // Konversi format tanggal dari dd/mm/yyyy ke yyyy-mm-dd
    // Fallback ke tanggal hari ini jika format tidak valid
    $rawDate = $data['creation_date'] ?? '';
    $parts = explode('/', $rawDate);
    if (count($parts) === 3 && strlen($parts[2]) === 4) {
        $creation_date_sql = sprintf('%s-%s-%s', $parts[2], $parts[1], $parts[0]);
    } else {
        $creation_date_sql = date('Y-m-d'); // fallback ke tanggal server
    }

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
    $currentUser = require_auth();
    $role        = $currentUser['role'] ?? '';
    $userArea    = trim($currentUser['area'] ?? '');
    $userPt      = trim($currentUser['pt']   ?? '');

    if ($role === 'superadmin') {
        // Superadmin: semua riwayat
        $stmt = $pdo->query("SELECT * FROM arsync_berita_acara ORDER BY creation_date DESC, id DESC");
        echo json_encode($stmt->fetchAll());

    } elseif ($role === 'head_ar') {
        // Head AR: semua area/DC dalam PT-nya
        // Ambil semua nama area dan sales_office yang ter-PT sama
        $stmtAreas = $pdo->prepare(
            "SELECT a.name AS aname, s.name AS sname
             FROM areas a
             LEFT JOIN sales_offices s ON s.area_id = a.id
             WHERE a.pt = :pt"
        );
        $stmtAreas->execute([':pt' => $userPt]);
        $rows = $stmtAreas->fetchAll();

        $names = [];
        foreach ($rows as $r) {
            if ($r['aname']) $names[] = $r['aname'];
            if ($r['sname']) $names[] = $r['sname'];
        }
        $names = array_unique(array_filter($names));

        if (empty($names)) {
            // PT tidak diset atau tidak ada area — kembalikan array kosong
            echo json_encode([]);
        } else {
            $ph = implode(',', array_fill(0, count($names), '?'));
            $valsPairs = array_merge(array_values($names), array_values($names));
            $stmt = $pdo->prepare(
                "SELECT * FROM arsync_berita_acara
                 WHERE business_area_name IN ($ph) OR sales_office_name IN ($ph)
                 ORDER BY creation_date DESC, id DESC"
            );
            $stmt->execute($valsPairs);
            echo json_encode($stmt->fetchAll());
        }

    } else {
        // admin / petugas: hanya area DC mereka sendiri
        if ($userArea === '') {
            echo json_encode([]);
        } else {
            $stmt = $pdo->prepare(
                "SELECT * FROM arsync_berita_acara
                 WHERE business_area_name = :area OR sales_office_name = :area
                 ORDER BY creation_date DESC, id DESC"
            );
            $stmt->execute([':area' => $userArea]);
            echo json_encode($stmt->fetchAll());
        }
    }
}
