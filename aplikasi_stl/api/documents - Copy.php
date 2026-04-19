<?php
include '../db_connect.php';

// Menangani preflight request (OPTIONS) untuk CORS
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$barcode_id = isset($_GET['barcode_id']) ? $conn->real_escape_string($_GET['barcode_id']) : null;

switch ($method) {
    case 'GET':
        // --- LOGIKA UNTUK MENGAMBIL SEMUA RIWAYAT DOKUMEN ---
        $sql = "SELECT 
                    d.barcode_id, 
                    d.doc_type, 
                    sender.full_name as sender_name, 
                    receiver_ho.full_name as receiver_name,
                    d.created_at, 
                    d.received_at_ho,
                    d.returned_at_ho,
                    d.received_at_dc,
                    d.status,
                    d.notes,
                    d.return_notes
                FROM documents d
                JOIN users sender ON d.sender_user_id = sender.user_id
                LEFT JOIN users receiver_ho ON d.receiver_ho_user_id = receiver_ho.user_id
                ORDER BY d.created_at DESC";
        
        $result = $conn->query($sql);
        $documents = [];
        while($row = $result->fetch_assoc()) {
            // Format tanggal agar lebih mudah dibaca di frontend
            $row['created_at'] = $row['created_at'] ? date("Y-m-d H:i:s", strtotime($row['created_at'])) : null;
            $row['received_at_ho'] = $row['received_at_ho'] ? date("Y-m-d H:i:s", strtotime($row['received_at_ho'])) : null;
            $row['returned_at_ho'] = $row['returned_at_ho'] ? date("Y-m-d H:i:s", strtotime($row['returned_at_ho'])) : null;
            $row['received_at_dc'] = $row['received_at_dc'] ? date("Y-m-d H:i:s", strtotime($row['received_at_dc'])) : null;
            $documents[] = $row;
        }
        echo json_encode($documents);
        break;

    case 'POST':
        // --- LOGIKA UNTUK MENAMBAH DOKUMEN BARU ---
        $data = json_decode(file_get_contents("php://input"));
        
        $barcode_id = $conn->real_escape_string($data->barcode_id);
        $sender_user_id = intval($data->sender_user_id);
        $doc_type = $conn->real_escape_string($data->doc_type);
        $start_period = $conn->real_escape_string($data->start_period);
        $end_period = $conn->real_escape_string($data->end_period);
        $notes = $conn->real_escape_string($data->notes);
        $status = 'Dikirim ke HO'; // Status awal

        $sql = "INSERT INTO documents (barcode_id, sender_user_id, doc_type, start_period, end_period, notes, status, created_at) 
                VALUES ('$barcode_id', '$sender_user_id', '$doc_type', '$start_period', '$end_period', '$notes', '$status', NOW())";

        if ($conn->query($sql) === TRUE) {
            http_response_code(201); // Created
            echo json_encode(["status" => "success", "message" => "Dokumen berhasil disimpan."]);
        } else {
            http_response_code(500); // Internal Server Error
            echo json_encode(["status" => "error", "message" => "Error: " . $conn->error]);
        }
        break;

    case 'PUT':
        // --- LOGIKA UNTUK UPDATE STATUS DOKUMEN DENGAN VALIDASI ---
        $data = json_decode(file_get_contents("php://input"));
        $new_status = $conn->real_escape_string($data->status);
        $user_id = intval($data->user_id);
        
        if (!$barcode_id || !$new_status || !$user_id) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Data tidak lengkap."]);
            exit();
        }

        // 1. Cek status dokumen saat ini
        $check_sql = "SELECT status FROM documents WHERE barcode_id = '$barcode_id'";
        $result = $conn->query($check_sql);

        if ($result->num_rows > 0) {
            $current_doc = $result->fetch_assoc();
            $current_status = $current_doc['status'];
            
            // 2. Tentukan alur yang valid
            $valid_transition = false;
            $timestamp_field = '';

            if ($new_status == 'Diterima HO' && $current_status == 'Dikirim ke HO') {
                $valid_transition = true;
                $timestamp_field = "received_at_ho=NOW(), receiver_ho_user_id=$user_id";
            } elseif ($new_status == 'Dikembalikan ke DC' && $current_status == 'Diterima HO') {
                $valid_transition = true;
                $return_notes = isset($data->notes) ? $conn->real_escape_string($data->notes) : '';
                $timestamp_field = "returned_at_ho=NOW(), return_notes='$return_notes'";
            } elseif ($new_status == 'Selesai' && $current_status == 'Dikembalikan ke DC') {
                $valid_transition = true;
                $timestamp_field = "received_at_dc=NOW(), receiver_dc_user_id=$user_id";
            }

            if ($valid_transition) {
                // 3. Jika transisi valid, update status
                $update_sql = "UPDATE documents SET status='$new_status', $timestamp_field WHERE barcode_id='$barcode_id'";
                if ($conn->query($update_sql) === TRUE) {
                    echo json_encode(["status" => "success", "message" => "Status dokumen berhasil diperbarui."]);
                } else {
                    echo json_encode(["status" => "error", "message" => "Error: " . $conn->error]);
                }
            } else {
                // 4. Jika transisi tidak valid (misal: sudah di-scan), kirim error
                http_response_code(409); // Conflict
                echo json_encode(["status" => "error", "message" => "Dokumen sudah diproses atau status tidak sesuai. Status saat ini: " . $current_status]);
            }
        } else {
            http_response_code(404); // Not Found
            echo json_encode(["status" => "error", "message" => "Barcode tidak ditemukan."]);
        }
        break;
    
    default:
        http_response_code(405);
        echo json_encode(["message" => "Metode tidak diizinkan."]);
        break;
}

$conn->close();
?>
