<?php
require_once __DIR__ . '/../db_connect.php';
$currentUser = require_auth();

$method     = $_SERVER['REQUEST_METHOD'];
$barcode_id = isset($_GET['barcode_id']) ? trim($_GET['barcode_id']) : null;

switch ($method) {
    case 'GET':
        $params = [];
        $where  = [];

        $sql = "SELECT d.*,
                       sender.full_name AS sender_name,
                       sender_area.name AS sender_area_name,
                       receiver_ho.full_name AS receiver_name,
                       receiver_area.name AS receiver_area_name,
                       receiver_dc.full_name AS receiver_dc_name
                FROM stl_documents d
                LEFT JOIN stl_users sender      ON d.sender_user_id = sender.user_id
                LEFT JOIN areas sender_area ON sender.area_id = sender_area.id
                LEFT JOIN stl_users receiver_ho ON d.receiver_ho_user_id = receiver_ho.user_id
                LEFT JOIN areas receiver_area ON d.receiver_ho_area_id = receiver_area.id
                LEFT JOIN stl_users receiver_dc ON d.receiver_dc_user_id = receiver_dc.user_id";

        if ($barcode_id)                   { $where[] = 'd.barcode_id = ?';        $params[] = $barcode_id; }
        if (!empty($_GET['user_id']))       { $where[] = 'd.sender_user_id = ?';    $params[] = (int)$_GET['user_id']; }
        if (!empty($_GET['start_date']))    { $where[] = 'DATE(d.created_at) >= ?'; $params[] = $_GET['start_date']; }
        if (!empty($_GET['end_date']))      { $where[] = 'DATE(d.created_at) <= ?'; $params[] = $_GET['end_date']; }
        if (!empty($_GET['doc_type']))      { $where[] = 'd.doc_type = ?';          $params[] = $_GET['doc_type']; }
        if (!empty($_GET['status']))        { $where[] = 'd.status = ?';            $params[] = $_GET['status']; }

        if (!empty($_GET['area_id'])) {
            $where[]  = 'sender.area_id = ?';
            $params[] = (int)$_GET['area_id'];
        } elseif (!empty($_GET['ho_area_id'])) {
            $where[]  = 'sender_area.pt = (SELECT pt FROM areas WHERE id = ?)';
            $params[] = (int)$_GET['ho_area_id'];
        }

        if ($where) { $sql .= ' WHERE ' . implode(' AND ', $where); }
        $sql .= ' ORDER BY d.created_at DESC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        json_response($stmt->fetchAll());
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $barcode       = trim($data['barcode_id'] ?? '');
        $doc_type      = $data['doc_type'] ?? null;
        $sender_id     = (int)($data['sender_user_id'] ?? 0);
        $rcv_ho_area   = !empty($data['receiver_ho_area_id']) ? (int)$data['receiver_ho_area_id'] : null;
        $start_period  = !empty($data['start_period']) ? $data['start_period'] : null;
        $end_period    = !empty($data['end_period']) ? $data['end_period'] : null;
        $notes         = $data['notes'] ?? null;
        $so_id         = !empty($data['so_id']) ? (int)$data['so_id'] : null;
        $doc_type_so   = $data['doc_type_so'] ?? null;
        $start_so      = !empty($data['start_period_so']) ? $data['start_period_so'] : null;
        $end_so        = !empty($data['end_period_so']) ? $data['end_period_so'] : null;

        if (!$barcode || !$sender_id) {
            json_response(['status' => 'error', 'message' => 'Barcode dan pengirim wajib diisi.'], 400);
        }

        $pdo->prepare(
            "INSERT INTO stl_documents
             (barcode_id, doc_type, sender_user_id, receiver_ho_area_id, start_period, end_period,
              notes, so_id, doc_type_so, start_period_so, end_period_so, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Dikirim ke HO', NOW())"
        )->execute([$barcode, $doc_type, $sender_id, $rcv_ho_area, $start_period, $end_period,
                    $notes, $so_id, $doc_type_so, $start_so, $end_so]);

        json_response(['status' => 'success', 'message' => 'Dokumen berhasil dibuat.']);
        break;

    case 'PUT':
        $data   = json_decode(file_get_contents('php://input'), true) ?? [];
        $action = $data['action'] ?? null;
        $bcode  = trim($data['barcode_id'] ?? '');

        if (!$bcode) {
            json_response(['status' => 'error', 'message' => 'Barcode wajib ada.'], 400);
        }

        switch ($action) {
            case 'terima_ho':
                $rcv_user = (int)($data['receiver_ho_user_id'] ?? 0);

                // Validasi: dokumen harus berstatus 'Dikirim ke HO'
                $stmtDoc = $pdo->prepare("SELECT status, receiver_ho_area_id FROM stl_documents WHERE barcode_id = ?");
                $stmtDoc->execute([$bcode]);
                $docRow = $stmtDoc->fetch();
                if (!$docRow) {
                    json_response(['status' => 'error', 'message' => 'Dokumen tidak ditemukan.'], 404);
                }
                if ($docRow['status'] !== 'Dikirim ke HO') {
                    json_response(['status' => 'error', 'message' => 'Dokumen sudah diproses sebelumnya (status: ' . $docRow['status'] . ').'], 400);
                }

                // Validasi: user harus di HO area yang sesuai (kecuali superadmin)
                if ($currentUser['role_id'] !== 'superadmin' && $rcv_user && !empty($docRow['receiver_ho_area_id'])) {
                    $stmtUsr = $pdo->prepare("SELECT area_id FROM stl_users WHERE user_id = ?");
                    $stmtUsr->execute([$rcv_user]);
                    $usrRow = $stmtUsr->fetch();
                    if ($usrRow && (int)$usrRow['area_id'] !== (int)$docRow['receiver_ho_area_id']) {
                        json_response(['status' => 'error', 'message' => 'Anda tidak dapat menerima berkas ini. Bukan area HO Anda.'], 403);
                    }
                }

                $pdo->prepare(
                    "UPDATE stl_documents SET status = 'Diterima di HO', receiver_ho_user_id = ?, received_at_ho = NOW()
                     WHERE barcode_id = ? AND status = 'Dikirim ke HO'"
                )->execute([$rcv_user ?: null, $bcode]);
                break;

            case 'cek_dokumen':
                $check_notes = $data['check_notes'] ?? null;

                // Validasi: dokumen harus berstatus 'Diterima di HO'
                $stmtDoc = $pdo->prepare("SELECT status FROM stl_documents WHERE barcode_id = ?");
                $stmtDoc->execute([$bcode]);
                $docRow = $stmtDoc->fetch();
                if (!$docRow) {
                    json_response(['status' => 'error', 'message' => 'Dokumen tidak ditemukan.'], 404);
                }
                if ($docRow['status'] !== 'Diterima di HO') {
                    json_response(['status' => 'error', 'message' => 'Dokumen harus berstatus "Diterima di HO" untuk dapat dicek.'], 400);
                }

                $pdo->prepare(
                    "UPDATE stl_documents SET status = 'Sedang Dicek', check_notes = ?, checked_at = NOW()
                     WHERE barcode_id = ?"
                )->execute([$check_notes, $bcode]);
                break;

            case 'kembalikan_dc':
                $return_notes = $data['return_notes'] ?? null;

                // Validasi: dokumen harus berstatus 'Sedang Dicek'
                $stmtDoc = $pdo->prepare("SELECT status FROM stl_documents WHERE barcode_id = ?");
                $stmtDoc->execute([$bcode]);
                $docRow = $stmtDoc->fetch();
                if (!$docRow) {
                    json_response(['status' => 'error', 'message' => 'Dokumen tidak ditemukan.'], 404);
                }
                if ($docRow['status'] !== 'Sedang Dicek') {
                    json_response(['status' => 'error', 'message' => 'Dokumen harus berstatus "Sedang Dicek" untuk dikembalikan ke DC.'], 400);
                }

                $pdo->prepare(
                    "UPDATE stl_documents SET status = 'Dikembalikan ke DC', return_notes = ?, returned_at_ho = NOW()
                     WHERE barcode_id = ?"
                )->execute([$return_notes, $bcode]);
                break;

            case 'terima_dc':
                $rcv_dc = (int)($data['receiver_dc_user_id'] ?? 0);

                // Validasi: dokumen harus berstatus 'Dikembalikan ke DC'
                $stmtDoc = $pdo->prepare("SELECT status, sender_user_id FROM stl_documents WHERE barcode_id = ?");
                $stmtDoc->execute([$bcode]);
                $docRow = $stmtDoc->fetch();
                if (!$docRow) {
                    json_response(['status' => 'error', 'message' => 'Dokumen tidak ditemukan.'], 404);
                }
                if ($docRow['status'] !== 'Dikembalikan ke DC') {
                    json_response(['status' => 'error', 'message' => 'Dokumen belum dikembalikan dari HO (status: ' . $docRow['status'] . ').'], 400);
                }

                // Validasi: penerima DC harus dari area yang sama dengan pengirim (kecuali superadmin)
                if ($currentUser['role_id'] !== 'superadmin' && $rcv_dc && !empty($docRow['sender_user_id'])) {
                    $stmtSender = $pdo->prepare("SELECT area_id FROM stl_users WHERE user_id = ?");
                    $stmtSender->execute([$docRow['sender_user_id']]);
                    $senderRow = $stmtSender->fetch();
                    $stmtRcv = $pdo->prepare("SELECT area_id FROM stl_users WHERE user_id = ?");
                    $stmtRcv->execute([$rcv_dc]);
                    $rcvRow = $stmtRcv->fetch();
                    if ($senderRow && $rcvRow && (int)$senderRow['area_id'] !== (int)$rcvRow['area_id']) {
                        json_response(['status' => 'error', 'message' => 'Anda tidak dapat menerima berkas ini. Bukan DC pengirim asal.'], 403);
                    }
                }

                $pdo->prepare(
                    "UPDATE stl_documents SET status = 'Diterima di DC', receiver_dc_user_id = ?, received_at_dc = NOW()
                     WHERE barcode_id = ? AND status = 'Dikembalikan ke DC'"
                )->execute([$rcv_dc ?: null, $bcode]);
                break;

            default:
                // Generic update (tidak disarankan untuk alur two-way)
                $new_status = $data['status'] ?? null;
                if ($new_status) {
                    $pdo->prepare("UPDATE stl_documents SET status = ? WHERE barcode_id = ?")->execute([$new_status, $bcode]);
                }
        }
        json_response(['status' => 'success', 'message' => 'Dokumen berhasil diperbarui.']);
        break;

    default:
        json_response(['message' => 'Metode tidak diizinkan.'], 405);
}


switch ($method) {
    case 'GET':
        // Ambil parameter filter dari query string
        $start_date = isset($_GET['start_date']) && !empty($_GET['start_date']) ? $conn->real_escape_string($_GET['start_date']) : null;
        $end_date = isset($_GET['end_date']) && !empty($_GET['end_date']) ? $conn->real_escape_string($_GET['end_date']) : null;
        $doc_type = isset($_GET['doc_type']) && !empty($_GET['doc_type']) ? $conn->real_escape_string($_GET['doc_type']) : null;
        $status = isset($_GET['status']) && !empty($_GET['status']) ? $conn->real_escape_string($_GET['status']) : null;
        $area_id = isset($_GET['area_id']) && !empty($_GET['area_id']) ? intval($_GET['area_id']) : null;
        $user_id_filter = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
        $ho_area_id = isset($_GET['ho_area_id']) ? intval($_GET['ho_area_id']) : null; // (BARU) Ambil parameter HO


        // (DIUBAH) Logika join untuk 'receiver_area_name' agar lebih akurat
        $sql = "SELECT d.*, 
                       sender.full_name as sender_name, 
                       sender_area.area_name as sender_area_name,
                       receiver_ho.full_name as receiver_name,
                       receiver_area.area_name as receiver_area_name, -- (DIUBAH) Mengambil nama area dari ID yang tersimpan
                       receiver_dc.full_name as receiver_dc_name
                FROM documents d
                LEFT JOIN users sender ON d.sender_user_id = sender.user_id
                LEFT JOIN areas sender_area ON sender.area_id = sender_area.area_id
                LEFT JOIN users receiver_ho ON d.receiver_ho_user_id = receiver_ho.user_id
                LEFT JOIN areas receiver_area ON d.receiver_ho_area_id = receiver_area.area_id -- (DIUBAH) Join ke kolom area tujuan
                LEFT JOIN users receiver_dc ON d.receiver_dc_user_id = receiver_dc.user_id";

        $where_clauses = [];

        if ($barcode_id) {
            $where_clauses[] = "d.barcode_id = '$barcode_id'";
        }
        if ($user_id_filter) {
            $where_clauses[] = "d.sender_user_id = $user_id_filter";
        }

        // Filter berdasarkan tanggal kirim DC (created_at)
        if ($start_date) {
            $where_clauses[] = "DATE(d.created_at) >= '$start_date'";
        }
        if ($end_date) {
            $where_clauses[] = "DATE(d.created_at) <= '$end_date'";
        }
        if ($doc_type) {
            $where_clauses[] = "d.doc_type = '$doc_type'";
        }
        if ($status) {
            $where_clauses[] = "d.status = '$status'";
        }
        
        // (DIUBAH) Logika filter area
        if ($area_id) {
            // Ini untuk filter admin DC
            $where_clauses[] = "sender.area_id = $area_id";
        } elseif ($ho_area_id) {
            // (BARU) Ini untuk filter admin HO
            $where_clauses[] = "sender_area.parent_ho_id = $ho_area_id";
        }


        if (!empty($where_clauses)) {
            $sql .= " WHERE " . implode(' AND ', $where_clauses);
        }
        
        $sql .= " ORDER BY d.created_at DESC";
        
        $result = $conn->query($sql);
        $documents = [];
        while($row = $result->fetch_assoc()) {
            $documents[] = $row;
        }
        echo json_encode($documents);
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"));

        $barcode_id = $conn->real_escape_string($data->barcode_id);
        $doc_type = $conn->real_escape_string($data->doc_type);
        $sender_user_id = intval($data->sender_user_id);
        $receiver_ho_area_id = isset($data->receiver_ho_area_id) && !empty($data->receiver_ho_area_id) ? intval($data->receiver_ho_area_id) : "NULL"; // (BARU)
        $start_period = !empty($data->start_period) ? "'" . $conn->real_escape_string($data->start_period) . "'" : "NULL";
        $end_period = !empty($data->end_period) ? "'" . $conn->real_escape_string($data->end_period) . "'" : "NULL";
        $notes = $conn->real_escape_string($data->notes);

        $so_id = !empty($data->so_id) ? intval($data->so_id) : "NULL";
        $doc_type_so = !empty($data->doc_type_so) ? "'" . $conn->real_escape_string($data->doc_type_so) . "'" : "NULL";
        $start_period_so = !empty($data->start_period_so) ? "'" . $conn->real_escape_string($data->start_period_so) . "'" : "NULL";
        $end_period_so = !empty($data->end_period_so) ? "'" . $conn->real_escape_string($data->end_period_so) . "'" : "NULL";

        // (DIUBAH) Menambahkan 'receiver_ho_area_id' ke query INSERT
        $sql = "INSERT INTO documents (barcode_id, doc_type, sender_user_id, receiver_ho_area_id, start_period, end_period, notes, so_id, doc_type_so, start_period_so, end_period_so, status, created_at) 
                VALUES ('$barcode_id', '$doc_type', $sender_user_id, $receiver_ho_area_id, $start_period, $end_period, '$notes', $so_id, $doc_type_so, $start_period_so, $end_period_so, 'Dikirim ke HO', NOW())";
        
        if ($conn->query($sql) === TRUE) {
            echo json_encode(["status" => "success", "message" => "Dokumen berhasil dibuat."]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Error: " . $conn->error]);
        }
        break;

    case 'PUT':
        // --- LOGIKA UNTUK UPDATE STATUS DOKUMEN DENGAN VALIDASI ---
        $data = json_decode(file_get_contents("php://input"));
        
        if (!$barcode_id || empty($data->user_id)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Data tidak lengkap."]);
            exit();
        }
        
        $user_id = intval($data->user_id);

        // Cek status dokumen saat ini
        $check_sql = "SELECT status, sender_user_id, receiver_ho_area_id FROM documents WHERE barcode_id = '$barcode_id'";
        $result = $conn->query($check_sql);

        if ($result->num_rows > 0) {
            $current_doc = $result->fetch_assoc();
            $current_status = $current_doc['status'];
            
            // (BARU) Dapatkan area pengguna yang melakukan validasi
            $user_area_sql = "SELECT area_id, role_id FROM users WHERE user_id = $user_id";
            $user_result = $conn->query($user_area_sql);
            $user_data = $user_result->fetch_assoc();
            $user_area_id = $user_data['area_id'];
            $user_role_id = $user_data['role_id'];
            
            $update_fields = [];
            $valid_action = false;

            // Logika untuk update status
            if (isset($data->status)) {
                $new_status = $conn->real_escape_string($data->status);
                $valid_transition = false;
                
                // (BARU) Cek apakah user superadmin
                $is_superadmin = ($user_role_id == 'superadmin');

                if ($new_status == 'Diterima HO' && $current_status == 'Dikirim ke HO') {
                    // (BARU) Validasi: Hanya user di area HO tujuan ATAU superadmin yang bisa menerima
                    if ($is_superadmin || $user_area_id == $current_doc['receiver_ho_area_id']) {
                        $valid_transition = true;
                        $update_fields[] = "received_at_ho=NOW()";
                        $update_fields[] = "receiver_ho_user_id=$user_id";
                    } else {
                         http_response_code(403); // Forbidden
                         echo json_encode(["status" => "error", "message" => "Validasi gagal. Anda tidak berada di area HO tujuan untuk dokumen ini."]);
                         $conn->close();
                         exit();
                    }
                } elseif ($new_status == 'Dokumen Cek' && $current_status == 'Diterima HO') {
                    // (BARU) Validasi: Hanya user di area HO tujuan ATAU superadmin yang bisa cek
                    if ($is_superadmin || $user_area_id == $current_doc['receiver_ho_area_id']) {
                        $valid_transition = true;
                        $check_notes = isset($data->notes) ? "'" . $conn->real_escape_string($data->notes) . "'" : "''";
                        $update_fields[] = "check_notes=$check_notes";
                    } else {
                         http_response_code(403); // Forbidden
                         echo json_encode(["status" => "error", "message" => "Validasi gagal. Anda tidak berada di area HO tujuan untuk dokumen ini."]);
                         $conn->close();
                         exit();
                    }
                } elseif ($new_status == 'Dikembalikan ke DC' && $current_status == 'Dokumen Cek') {
                     // (BARU) Validasi: Hanya user di area HO tujuan ATAU superadmin yang bisa kembalikan
                    if ($is_superadmin || $user_area_id == $current_doc['receiver_ho_area_id']) {
                        $valid_transition = true;
                        $return_notes = isset($data->notes) ? "'" . $conn->real_escape_string($data->notes) . "'" : "''";
                        $update_fields[] = "returned_at_ho=NOW()";
                        $update_fields[] = "return_notes=$return_notes";
                    } else {
                         http_response_code(403); // Forbidden
                         echo json_encode(["status" => "error", "message" => "Validasi gagal. Anda tidak berada di area HO tujuan untuk dokumen ini."]);
                         $conn->close();
                         exit();
                    }
                } elseif ($new_status == 'Selesai' && $current_status == 'Dikembalikan ke DC') {
                    // Validasi: Hanya pengguna pengirim asli ATAU superadmin yang dapat menyelesaikan
                    if ($is_superadmin || $current_doc['sender_user_id'] == $user_id) {
                        $valid_transition = true;
                        $update_fields[] = "received_at_dc=NOW()";
                        $update_fields[] = "receiver_dc_user_id=$user_id";
                    } else {
                        http_response_code(403);
                        echo json_encode(["status" => "error", "message" => "Validasi gagal. Hanya pengguna yang membuat dokumen yang dapat menyelesaikan proses ini."]);
                        $conn->close();
                        exit();
                    }
                }

                if ($valid_transition) {
                    $update_fields[] = "status='$new_status'";
                    $valid_action = true;
                } else {
                     http_response_code(409); // Conflict
                     echo json_encode(["status" => "error", "message" => "Dokumen sudah diproses atau status tidak sesuai. Status saat ini: " . $current_status]);
                     $conn->close();
                     exit();
                }
            }

            if ($valid_action && !empty($update_fields)) {
                $update_sql = "UPDATE documents SET " . implode(', ', $update_fields) . " WHERE barcode_id='$barcode_id'";
                if ($conn->query($update_sql) === TRUE) {
                    echo json_encode(["status" => "success", "message" => "Dokumen berhasil diperbarui."]);
                } else {
                    http_response_code(500);
                    echo json_encode(["status" => "error", "message" => "Gagal memperbarui database: " . $conn->error]);
                }
            } elseif (!$valid_action) {
                 http_response_code(400); // Bad Request
                 echo json_encode(["status" => "error", "message" => "Tidak ada aksi valid yang dilakukan."]);
            }

        } else {
            http_response_code(404); // Not Found
            echo json_encode(["status" => "error", "message" => "Barcode tidak ditemukan."]);
        }
        break;


    default:
        http_response_code(405); // Method Not Allowed
        echo json_encode(["message" => "Metode tidak diizinkan."]);
        break;
}

$conn->close();
?>