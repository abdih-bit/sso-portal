<?php
require_once __DIR__ . '/../db_connect.php';
$currentUser = require_auth();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $stmt = $pdo->query(
            "SELECT e.barcode_id,
                    d.sender_user_id,
                    u_sender.full_name AS sender_name,
                    e.jenis_pengiriman,
                    e.nomor_resi,
                    j.nama_jasa AS nama_ekspedisi
             FROM stl_ekspedisi e
             JOIN stl_documents d ON e.barcode_id = d.barcode_id
             JOIN stl_users u_sender ON d.sender_user_id = u_sender.user_id
             JOIN stl_jasa_ekspedisi j ON e.jasa_ekspedisi_id = j.id
             ORDER BY e.created_at DESC"
        );
        json_response($stmt->fetchAll());
        break;

    case 'POST':
        $data             = json_decode(file_get_contents('php://input'), true) ?? [];
        $barcode_id       = trim($data['barcode_id'] ?? '');
        $jenis            = $data['jenis_pengiriman'] ?? '';
        $nomor_resi       = trim($data['nomor_resi'] ?? '');
        $jasa_id          = (int)($data['jasa_ekspedisi_id'] ?? 0);
        $user_id          = (int)($data['user_id'] ?? 0);

        if (!$barcode_id || !$jenis || !$nomor_resi || !$jasa_id || !$user_id) {
            json_response(['status' => 'error', 'message' => 'Semua field wajib diisi.'], 400);
        }

        // Cek dokumen ada
        $chk = $pdo->prepare("SELECT barcode_id FROM stl_documents WHERE barcode_id = ?");
        $chk->execute([$barcode_id]);
        if (!$chk->fetch()) {
            json_response(['status' => 'error', 'message' => 'ID Dokumen tidak ditemukan.'], 404);
        }

        $pdo->prepare(
            "INSERT INTO stl_ekspedisi (barcode_id, jenis_pengiriman, nomor_resi, jasa_ekspedisi_id, user_id)
             VALUES (?, ?, ?, ?, ?)
             ON CONFLICT (barcode_id, jenis_pengiriman) DO UPDATE
               SET nomor_resi = EXCLUDED.nomor_resi,
                   jasa_ekspedisi_id = EXCLUDED.jasa_ekspedisi_id,
                   user_id = EXCLUDED.user_id"
        )->execute([$barcode_id, $jenis, $nomor_resi, $jasa_id, $user_id]);

        json_response(['status' => 'success', 'message' => 'Data resi berhasil disimpan.']);
        break;

    default:
        json_response(['message' => 'Metode tidak diizinkan.'], 405);
}


switch ($method) {
    case 'GET':
        // Mengambil semua data resi yang sudah tersimpan
        $sql = "SELECT 
                    e.barcode_id,
                    d.sender_user_id,
                    u_sender.full_name as sender_name,
                    e.jenis_pengiriman,
                    e.nomor_resi,
                    j.nama_jasa as nama_ekspedisi
                FROM ekspedisi e
                JOIN documents d ON e.barcode_id = d.barcode_id
                JOIN users u_sender ON d.sender_user_id = u_sender.user_id
                JOIN jasa_ekspedisi j ON e.jasa_ekspedisi_id = j.id
                ORDER BY e.created_at DESC";
        $result = $conn->query($sql);
        $ekspedisi_data = [];
        while($row = $result->fetch_assoc()) {
            $ekspedisi_data[] = $row;
        }
        echo json_encode($ekspedisi_data);
        break;

    case 'POST':
        // Menyimpan data resi baru
        $data = json_decode(file_get_contents("php://input"));
        
        $barcode_id = $conn->real_escape_string($data->barcode_id);
        $jenis_pengiriman = $conn->real_escape_string($data->jenis_pengiriman);
        $nomor_resi = $conn->real_escape_string($data->nomor_resi);
        $jasa_ekspedisi_id = intval($data->jasa_ekspedisi_id);
        $user_id = intval($data->user_id);

        // Validasi input
        if (empty($barcode_id) || empty($jenis_pengiriman) || empty($nomor_resi) || empty($jasa_ekspedisi_id) || empty($user_id)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Semua field wajib diisi."]);
            exit();
        }

        // Cek apakah dokumen ada
        $check_doc_sql = "SELECT barcode_id FROM documents WHERE barcode_id = '$barcode_id'";
        $doc_result = $conn->query($check_doc_sql);
        if ($doc_result->num_rows == 0) {
            http_response_code(404);
            echo json_encode(["status" => "error", "message" => "ID Dokumen tidak ditemukan."]);
            exit();
        }

        // Gunakan INSERT ... ON DUPLICATE KEY UPDATE untuk handle data yang sudah ada (update jika sudah ada)
        $sql = "INSERT INTO ekspedisi (barcode_id, jenis_pengiriman, nomor_resi, jasa_ekspedisi_id, user_id)
                VALUES ('$barcode_id', '$jenis_pengiriman', '$nomor_resi', $jasa_ekspedisi_id, $user_id)
                ON DUPLICATE KEY UPDATE
                nomor_resi = VALUES(nomor_resi),
                jasa_ekspedisi_id = VALUES(jasa_ekspedisi_id),
                user_id = VALUES(user_id)";

        if ($conn->query($sql) === TRUE) {
            echo json_encode(["status" => "success", "message" => "Data resi berhasil disimpan."]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Gagal menyimpan data resi: " . $conn->error]);
        }
        break;

    default:
        http_response_code(405); // Method Not Allowed
        echo json_encode(["message" => "Metode tidak diizinkan."]);
        break;
}

$conn->close();
?>
