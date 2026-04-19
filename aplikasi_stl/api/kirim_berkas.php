<?php
require_once __DIR__ . '/../db_connect.php';
$currentUser = require_auth();

$method = $_SERVER['REQUEST_METHOD'];
$data   = json_decode(file_get_contents('php://input'), true) ?? [];

switch ($method) {
    case 'GET':
        $params = [];
        $where  = [];

        $sql = "SELECT b.barcode_id,
                       u_sender.full_name AS sender_name,
                       a_sender.area_name AS sender_area_name,
                       b.jenis_berkas,
                       b.created_at,
                       b.notes,
                       b.received_at,
                       u_receiver.full_name AS receiver_name,
                       a_receiver.area_name AS receiver_area_name,
                       b.status,
                       a_sender.parent_ho_id AS sender_parent_ho_id,
                       a_receiver.parent_ho_id AS receiver_parent_ho_id
                FROM stl_berkas_satu_arah b
                JOIN stl_users u_sender ON b.sender_user_id = u_sender.user_id
                JOIN stl_areas a_sender ON u_sender.area_id = a_sender.area_id
                LEFT JOIN stl_users u_receiver ON b.receiver_user_id = u_receiver.user_id
                LEFT JOIN stl_areas a_receiver ON b.receiver_area_id = a_receiver.area_id";

        if (!empty($_GET['start_date']))     { $where[] = 'DATE(b.created_at) >= ?'; $params[] = $_GET['start_date']; }
        if (!empty($_GET['end_date']))       { $where[] = 'DATE(b.created_at) <= ?'; $params[] = $_GET['end_date']; }
        if (!empty($_GET['doc_type']))       { $where[] = 'b.jenis_berkas = ?';       $params[] = $_GET['doc_type']; }
        if (!empty($_GET['status']))         { $where[] = 'b.status = ?';             $params[] = $_GET['status']; }
        if (!empty($_GET['area_id_sender'])) { $where[] = 'a_sender.area_id = ?';    $params[] = (int)$_GET['area_id_sender']; }
        if (!empty($_GET['area_id_receiver'])){ $where[] = 'b.receiver_area_id = ?'; $params[] = (int)$_GET['area_id_receiver']; }

        if (!empty($_GET['ho_area_id'])) {
            $ho = (int)$_GET['ho_area_id'];
            $where[]  = '(a_sender.parent_ho_id = ? OR a_receiver.parent_ho_id = ?)';
            $params[] = $ho;
            $params[] = $ho;
        }

        if ($where) { $sql .= ' WHERE ' . implode(' AND ', $where); }
        $sql .= ' ORDER BY b.created_at DESC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        json_response($stmt->fetchAll());
        break;

    case 'POST':
        $barcode     = trim($data['barcode_id'] ?? '');
        $sender_id   = (int)($data['sender_user_id'] ?? 0);
        $rcv_area_id = (int)($data['receiver_area_id'] ?? 0);
        $jenis       = $data['jenis_berkas'] ?? '';
        $notes       = $data['notes'] ?? null;

        if (!$barcode || !$sender_id || !$rcv_area_id || !$jenis) {
            json_response(['status' => 'error', 'message' => 'Data tidak lengkap.'], 400);
        }

        $pdo->prepare(
            "INSERT INTO stl_berkas_satu_arah (barcode_id, sender_user_id, receiver_area_id, jenis_berkas, notes, status)
             VALUES (?, ?, ?, ?, ?, 'Dikirim')"
        )->execute([$barcode, $sender_id, $rcv_area_id, $jenis, $notes]);

        json_response(['status' => 'success', 'message' => 'Berkas berhasil dikirim.'], 201);
        break;

    case 'PUT':
        $barcode = trim($data['barcode_id'] ?? '');
        $user_id = (int)($data['user_id'] ?? 0);

        if (!$barcode || !$user_id) {
            json_response(['status' => 'error', 'message' => 'Barcode ID atau User ID tidak ada.'], 400);
        }

        // Cek status dan area tujuan
        $stmtChk = $pdo->prepare("SELECT status, receiver_area_id FROM stl_berkas_satu_arah WHERE barcode_id = ?");
        $stmtChk->execute([$barcode]);
        $doc = $stmtChk->fetch();

        if (!$doc) {
            json_response(['status' => 'error', 'message' => 'Dokumen tidak ditemukan.'], 404);
        }
        if ($doc['status'] === 'Diterima') {
            json_response(['status' => 'info', 'message' => 'Dokumen sudah divalidasi sebelumnya.'], 400);
        }

        // Cek area user
        $stmtUser = $pdo->prepare("SELECT area_id, role_id FROM stl_users WHERE user_id = ?");
        $stmtUser->execute([$user_id]);
        $user = $stmtUser->fetch();

        if (!$user) {
            json_response(['status' => 'error', 'message' => 'Pengguna tidak ditemukan.'], 404);
        }

        if ($user['role_id'] !== 'superadmin' && $user['area_id'] != $doc['receiver_area_id']) {
            json_response(['status' => 'error', 'message' => 'Validasi gagal. Anda tidak berada di area tujuan berkas ini.'], 403);
        }

        $pdo->prepare(
            "UPDATE stl_berkas_satu_arah SET status = 'Diterima', receiver_user_id = ?, received_at = NOW()
             WHERE barcode_id = ? AND status = 'Dikirim'"
        )->execute([$user_id, $barcode]);

        json_response(['status' => 'success', 'message' => 'Dokumen berhasil divalidasi.']);
        break;

    default:
        json_response(['message' => 'Metode tidak diizinkan.'], 405);
}

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST, GET, PUT, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    http_response_code(200);
    exit();
}
// ... (kode header tidak berubah) ...
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

$method = $_SERVER['REQUEST_METHOD'];
// ... (kode $data tidak berubah) ...
$data = json_decode(file_get_contents("php://input"));

switch ($method) {
    case 'GET':
        // Logika untuk mengambil data laporan berkas satu arah
// ... (kode $sql tidak berubah) ...
        $sql = "SELECT 
                    b.barcode_id, 
                    u_sender.full_name AS sender_name, 
                    a_sender.area_name AS sender_area_name,
                    b.jenis_berkas, 
                    b.created_at, 
                    b.notes, 
                    b.received_at, 
                    u_receiver.full_name AS receiver_name,
                    a_receiver.area_name AS receiver_area_name,
                    b.status,
                    a_sender.parent_ho_id AS sender_parent_ho_id,
                    a_receiver.parent_ho_id AS receiver_parent_ho_id
                FROM berkas_satu_arah b
                JOIN users u_sender ON b.sender_user_id = u_sender.user_id
                JOIN areas a_sender ON u_sender.area_id = a_sender.area_id
                LEFT JOIN users u_receiver ON b.receiver_user_id = u_receiver.user_id
                LEFT JOIN areas a_receiver ON b.receiver_area_id = a_receiver.area_id";

        $where = [];
// ... (kode $params, $types tidak berubah) ...
        $params = [];
        $types = '';
        
        // (PERBAIKAN) Ambil parameter ho_area_id
        $ho_area_id = isset($_GET['ho_area_id']) ? intval($_GET['ho_area_id']) : null;

        // Filter berdasarkan tanggal
// ... (kode filter tanggal tidak berubah) ...
        if (!empty($_GET['start_date'])) {
            $where[] = "DATE(b.created_at) >= ?";
            $params[] = $_GET['start_date'];
            $types .= 's';
        }
        if (!empty($_GET['end_date'])) {
            $where[] = "DATE(b.created_at) <= ?";
            $params[] = $_GET['end_date'];
            $types .= 's';
        }

        // Filter berdasarkan jenis berkas
// ... (kode filter $doc_type tidak berubah) ...
        if (!empty($_GET['doc_type'])) {
            $where[] = "b.jenis_berkas = ?";
            $params[] = $_GET['doc_type'];
            $types .= 's';
        }

        // Filter berdasarkan status
// ... (kode filter $status tidak berubah) ...
        if (!empty($_GET['status'])) {
            $where[] = "b.status = ?";
            $params[] = $_GET['status'];
            $types .= 's';
        }
        
        // Filter berdasarkan Area Pengirim
// ... (kode filter $area_id_sender tidak berubah) ...
        if (!empty($_GET['area_id_sender'])) {
            $where[] = "a_sender.area_id = ?";
            $params[] = $_GET['area_id_sender'];
            $types .= 'i';
        }

        // Filter berdasarkan Area Penerima
// ... (kode filter $area_id_receiver tidak berubah) ...
        if (!empty($_GET['area_id_receiver'])) {
            $where[] = "b.receiver_area_id = ?";
            $params[] = $_GET['area_id_receiver'];
            $types .= 'i';
        }
        
        // (DIUBAH) Filter berdasarkan Grup HO
        if ($ho_area_id) {
            // (PERBAIKAN) Tampilkan jika pengirim ATAU penerima ada di grup HO tersebut
            $where[] = "(a_sender.parent_ho_id = ? OR a_receiver.parent_ho_id = ?)";
            $params[] = $ho_area_id;
            $params[] = $ho_area_id;
            $types .= 'ii';
        }

        if (count($where) > 0) {
// ... (kode $sql WHERE tidak berubah) ...
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $sql .= " ORDER BY b.created_at DESC";

// ... (kode $stmt prepare, bind, execute tidak berubah) ...
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to prepare statement: ' . $conn->error]);
            exit();
        }

        if ($types) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $reports = [];
        while ($row = $result->fetch_assoc()) {
            $reports[] = $row;
        }

        echo json_encode($reports);
        $stmt->close();
        break;

    case 'POST':
// ... (kode POST tidak berubah) ...
        // Logika untuk mengirim berkas (membuat entri baru)
        if (empty($data->barcode_id) || empty($data->sender_user_id) || empty($data->receiver_area_id) || empty($data->jenis_berkas)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap.']);
            exit();
        }

        $notes = $data->notes ?? null;

        $stmt = $conn->prepare("INSERT INTO berkas_satu_arah (barcode_id, sender_user_id, receiver_area_id, jenis_berkas, notes, status) VALUES (?, ?, ?, ?, ?, 'Dikirim')");
        if ($stmt === false) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyiapkan statement: ' . $conn->error]);
            exit();
        }

        $stmt->bind_param("siiss", $data->barcode_id, $data->sender_user_id, $data->receiver_area_id, $data->jenis_berkas, $notes);

        if ($stmt->execute()) {
            http_response_code(201); // Created
            echo json_encode(['status' => 'success', 'message' => 'Berkas berhasil dikirim.']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data: ' . $stmt->error]);
        }
        $stmt->close();
        break;

    case 'PUT':
// ... (kode PUT tidak berubah) ...
        // Logika untuk memvalidasi penerimaan berkas
        if (empty($data->barcode_id) || empty($data->user_id)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Barcode ID atau User ID tidak ada.']);
            exit();
        }

        // 1. Cek status dokumen saat ini
        $stmt_check = $conn->prepare("SELECT status, receiver_area_id FROM berkas_satu_arah WHERE barcode_id = ?");
        if ($stmt_check === false) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyiapkan statement (check): ' . $conn->error]);
            exit();
        }
        $stmt_check->bind_param("s", $data->barcode_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows === 0) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Dokumen dengan barcode ' . $data->barcode_id . ' tidak ditemukan.']);
            $stmt_check->close();
            exit();
        }

        $doc = $result_check->fetch_assoc();
        $current_status = $doc['status'];
        $receiver_area_id = $doc['receiver_area_id'];
        $stmt_check->close();

        if ($current_status === 'Diterima') {
            http_response_code(400);
            echo json_encode(['status' => 'info', 'message' => 'Dokumen ' . $data->barcode_id . ' sudah divalidasi sebelumnya.']);
            exit();
        }

        // 2. Cek area dan peran penerima
        // (DIUBAH) Ambil area_id DAN role_id user
        $stmt_user = $conn->prepare("SELECT area_id, role_id FROM users WHERE user_id = ?");
        $stmt_user->bind_param("i", $data->user_id);
        $stmt_user->execute();
        $result_user = $stmt_user->get_result();
        
        if ($result_user->num_rows === 0) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Pengguna tidak ditemukan.']);
            $stmt_user->close();
            exit();
        }
        
        $user = $result_user->fetch_assoc();
        $user_area_id = $user['area_id'];
        $user_role_id = $user['role_id']; // (BARU) Ambil role_id
        $stmt_user->close();

        // (DIUBAH) Validasi: User harus berada di area tujuan ATAU dia adalah superadmin
        if ($user_role_id != 'superadmin' && $user_area_id != $receiver_area_id) {
             http_response_code(403); // Forbidden
             echo json_encode(["status" => "error", "message" => "Validasi gagal. Anda tidak berada di area tujuan berkas ini."]);
             $conn->close();
             exit();
        }


        // 3. Update status dokumen
        $stmt_update = $conn->prepare("UPDATE berkas_satu_arah SET status = 'Diterima', receiver_user_id = ?, received_at = CURRENT_TIMESTAMP WHERE barcode_id = ? AND status = 'Dikirim'");
        if ($stmt_update === false) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyiapkan statement (update): ' . $conn->error]);
            exit();
        }
        
        $stmt_update->bind_param("is", $data->user_id, $data->barcode_id);

        if ($stmt_update->execute()) {
            if ($stmt_update->affected_rows > 0) {
                http_response_code(200);
                echo json_encode(['status' => 'success', 'message' => 'Dokumen ' . $data->barcode_id . ' berhasil divalidasi.']);
            } else {
                // Ini bisa terjadi jika statusnya bukan 'Dikirim' (misalnya, race condition atau status sudah 'Diterima')
                http_response_code(409); // Conflict
                echo json_encode(['status' => 'info', 'message' => 'Tidak ada dokumen yang diupdate. Mungkin sudah divalidasi atau barcode salah.']);
            }
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Gagal mengupdate status: ' . $stmt_update->error]);
        }
        $stmt_update->close();
        break;

    default:
// ... (kode default tidak berubah) ...
        http_response_code(405); // Method Not Allowed
        echo json_encode(['status' => 'error', 'message' => 'Metode tidak diizinkan.']);
        break;
}

$conn->close();
?>