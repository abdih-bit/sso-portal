<?php
require_once __DIR__ . '/../db_connect.php';
$currentUser = require_auth();

$start_date  = $_GET['start_date'] ?? null;
$end_date    = $_GET['end_date']   ?? null;
$area_id     = isset($_GET['area_id']) ? (int)$_GET['area_id'] : null;

$params = [];
$where  = [];

// Base JOIN
$join = " FROM stl_documents d
          LEFT JOIN stl_users sender ON d.sender_user_id = sender.user_id
          LEFT JOIN areas sender_area ON sender.area_id = sender_area.id";

// Filter berdasarkan area HO vs DC
if ($area_id) {
    $stmtArea = $pdo->prepare("SELECT is_ho FROM areas WHERE id = ?");
    $stmtArea->execute([$area_id]);
    $areaData = $stmtArea->fetch();

    if ($areaData) {
        if ($areaData['is_ho']) {
            // HO: tampilkan semua dokumen DC yang satu PT
            $where[]  = 'sender_area.pt = (SELECT pt FROM areas WHERE id = ?)';
            $params[] = $area_id;
        } else {
            $where[]  = 'sender.area_id = ?';
            $params[] = $area_id;
        }
    }
}

if ($start_date) { $where[] = 'DATE(d.created_at) >= ?'; $params[] = $start_date; }
if ($end_date)   { $where[] = 'DATE(d.created_at) <= ?'; $params[] = $end_date; }

$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

// 1. Total dokumen
$stmt = $pdo->prepare("SELECT COUNT(*) AS total $join $whereSql");
$stmt->execute($params);
$total_dokumen = $stmt->fetchColumn();

// 2. Hitung per status
$stmtStatus = $pdo->prepare("SELECT d.status, COUNT(*) AS count $join $whereSql GROUP BY d.status");
$stmtStatus->execute($params);

$status_counts = [
    'dikirim_ho'      => 0,
    'diterima_ho'     => 0,
    'dokumen_cek'     => 0,
    'dikembalikan_dc' => 0,
    'selesai_dc'      => 0,
];

while ($row = $stmtStatus->fetch()) {
    switch ($row['status']) {
        case 'Dikirim ke HO':        $status_counts['dikirim_ho']      += $row['count']; break;
        case 'Diterima di HO':       $status_counts['diterima_ho']     += $row['count']; break;
        case 'Sedang Dicek':         $status_counts['dokumen_cek']     += $row['count']; break;
        case 'Dikembalikan ke DC':   $status_counts['dikembalikan_dc'] += $row['count']; break;
        case 'Diterima di DC':       $status_counts['selesai_dc']      += $row['count']; break;
    }
}

// 3. Aktivitas terbaru (10 terakhir)
$stmtRecent = $pdo->prepare(
    "SELECT d.barcode_id, d.status, d.created_at,
            sender.full_name AS sender_name,
            sender_area.name AS sender_area_name
     $join $whereSql
     ORDER BY d.created_at DESC LIMIT 10"
);
$stmtRecent->execute($params);
$recent_activity = $stmtRecent->fetchAll();

json_response([
    'total_dokumen'  => (int)$total_dokumen,
    // Flat keys for KPI cards
    'dikirim_ho'      => $status_counts['dikirim_ho'],
    'diterima_ho'     => $status_counts['diterima_ho'],
    'dokumen_cek'     => $status_counts['dokumen_cek'],
    'dikembalikan_dc' => $status_counts['dikembalikan_dc'],
    'selesai_dc'      => $status_counts['selesai_dc'],
    // Keyed by status name for bar chart
    'chart_data' => [
        'Dikirim ke HO'      => $status_counts['dikirim_ho'],
        'Diterima di HO'     => $status_counts['diterima_ho'],
        'Sedang Dicek'       => $status_counts['dokumen_cek'],
        'Dikembalikan ke DC' => $status_counts['dikembalikan_dc'],
        'Diterima di DC'     => $status_counts['selesai_dc'],
    ],
    'status_counts'  => $status_counts,
    'recent_activity'=> $recent_activity,
]);


// Menangani preflight request (OPTIONS) untuk CORS
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    http_response_code(200);
    exit();
}

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Ambil parameter filter
$start_date = isset($_GET['start_date']) && !empty($_GET['start_date']) ? $conn->real_escape_string($_GET['start_date']) : null;
$end_date = isset($_GET['end_date']) && !empty($_GET['end_date']) ? $conn->real_escape_string($_GET['end_date']) : null;
$area_id = isset($_GET['area_id']) && !empty($_GET['area_id']) ? intval($_GET['area_id']) : null;

$where_clauses = [];
// Join ke tabel users (pengirim) dan areas (area pengirim) untuk filter lokasi
$join_clause = " FROM documents d
                 LEFT JOIN users sender ON d.sender_user_id = sender.user_id
                 LEFT JOIN areas sender_area ON sender.area_id = sender_area.area_id";

// --- Logika Filter HO vs DC ---
if ($area_id) {
    // Cek apakah area_id yang dikirim adalah HO atau DC
    $area_check_stmt = $conn->prepare("SELECT is_ho FROM areas WHERE area_id = ?");
    if ($area_check_stmt) {
        $area_check_stmt->bind_param("i", $area_id);
        $area_check_stmt->execute();
        $area_result = $area_check_stmt->get_result();
        
        if ($area_result->num_rows > 0) {
            $area_data = $area_result->fetch_assoc();
            
            if ($area_data['is_ho'] == 1) {
                // JIKA USER ADALAH HO: Filter berdasarkan parent_ho_id dari area pengirim (DC)
                $where_clauses[] = "sender_area.parent_ho_id = $area_id";
            } else {
                // JIKA USER ADALAH DC: Filter berdasarkan area_id pengirim
                $where_clauses[] = "sender.area_id = $area_id";
            }
        }
        $area_check_stmt->close();
    }
}

// Filter tanggal
if ($start_date) {
    $where_clauses[] = "DATE(d.created_at) >= '$start_date'";
}
if ($end_date) {
    $where_clauses[] = "DATE(d.created_at) <= '$end_date'";
}

$where_sql = "";
if (count($where_clauses) > 0) {
    $where_sql = " WHERE " . implode(' AND ', $where_clauses);
}

// 1. Hitung Total Dokumen (Gunakan COUNT(*) agar lebih aman)
$total_dokumen_sql = "SELECT COUNT(*) as total" . $join_clause . $where_sql;
$total_result = $conn->query($total_dokumen_sql);

if (!$total_result) {
    // Kirim JSON error jika query gagal
    echo json_encode([
        "status" => "error", 
        "message" => "Query Error (Total): " . $conn->error
    ]);
    exit();
}

$total_dokumen = $total_result->fetch_assoc()['total'] ?? 0;

// 2. Hitung berdasarkan Status (Gunakan COUNT(*))
$status_counts_sql = "SELECT d.status, COUNT(*) as count" . $join_clause . $where_sql . " GROUP BY d.status";
$status_result = $conn->query($status_counts_sql);

if (!$status_result) {
    // Kirim JSON error jika query gagal
    echo json_encode([
        "status" => "error", 
        "message" => "Query Error (Status): " . $conn->error
    ]);
    exit();
}

// Inisialisasi nilai default 0
$status_counts = [
    'dikirim_ho' => 0,
    'diterima_ho' => 0,
    'dokumen_cek' => 0,
    'dikembalikan_dc' => 0,
    'selesai_dc' => 0,
];

$chart_data = [];

if ($status_result) {
    while($row = $status_result->fetch_assoc()) {
        // Ubah status dari DB menjadi lowercase untuk pencocokan yang lebih aman
        $status_db = strtolower(trim($row['status']));
        $count = intval($row['count']);

        // Simpan data asli untuk chart
        $chart_data[$row['status']] = $count;

        // Mapping Status Database ke Variabel Hitungan
        if ($status_db == 'dikirim ke ho') {
            $status_counts['dikirim_ho'] = $count;
        } elseif ($status_db == 'diterima ho') {
            $status_counts['diterima_ho'] = $count;
        } elseif ($status_db == 'dokumen cek') {
            $status_counts['dokumen_cek'] = $count;
        } elseif ($status_db == 'dikembalikan ke dc') {
            $status_counts['dikembalikan_dc'] = $count;
        } elseif ($status_db == 'selesai') {
            $status_counts['selesai_dc'] = $count;
        }
    }
}

// 3. Susun data JSON untuk output
$output = [
    'total_dokumen' => $total_dokumen,
    'dikirim_ho' => $status_counts['dikirim_ho'],
    'diterima_ho' => $status_counts['diterima_ho'],
    'dokumen_cek' => $status_counts['dokumen_cek'],
    'dikembalikan_dc' => $status_counts['dikembalikan_dc'],
    'selesai_dc' => $status_counts['selesai_dc'],
    'chart_data' => $chart_data
];

echo json_encode($output);

$conn->close();
?>