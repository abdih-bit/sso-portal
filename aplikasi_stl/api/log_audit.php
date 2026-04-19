<?php
require_once __DIR__ . '/../db_connect.php';
$currentUser = require_auth();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $params = [];
        $where  = [];

        $sql = "SELECT l.timestamp, u.full_name AS user, l.action, l.details
                FROM stl_audit_log l
                JOIN stl_users u ON l.user_id = u.user_id";

        if (!empty($_GET['start_date'])) { $where[] = 'DATE(l.timestamp) >= ?'; $params[] = $_GET['start_date']; }
        if (!empty($_GET['end_date']))   { $where[] = 'DATE(l.timestamp) <= ?'; $params[] = $_GET['end_date']; }

        if ($where) { $sql .= ' WHERE ' . implode(' AND ', $where); }
        $sql .= ' ORDER BY l.timestamp DESC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $logs = [];
        while ($row = $stmt->fetch()) {
            $row['timestamp'] = date('d/m/Y, H:i:s', strtotime($row['timestamp']));
            $logs[] = $row;
        }
        json_response($logs);
        break;

    case 'POST':
        $data    = json_decode(file_get_contents('php://input'), true) ?? [];
        $user_id = (int)($data['user_id'] ?? 0);
        $action  = trim($data['action'] ?? '');
        $details = $data['details'] ?? '';

        if (!empty($data['status'])) {
            $details .= ' | Status: ' . $data['status'];
        }

        if (!$user_id || !$action) {
            json_response(['status' => 'error', 'message' => 'Data tidak lengkap.'], 400);
        }

        $pdo->prepare(
            "INSERT INTO stl_audit_log (user_id, action, details) VALUES (?, ?, ?)"
        )->execute([$user_id, $action, $details]);

        json_response(['status' => 'success', 'message' => 'Log berhasil disimpan.']);
        break;

    default:
        json_response(['message' => 'Metode tidak diizinkan.'], 405);
}


switch ($method) {
    case 'GET':
        // --- LOGIKA UNTUK MENGAMBIL SEMUA LOG AUDIT ---
        $start_date = isset($_GET['start_date']) ? $conn->real_escape_string($_GET['start_date']) : null;
        $end_date = isset($_GET['end_date']) ? $conn->real_escape_string($_GET['end_date']) : null;

        $sql = "SELECT l.timestamp, u.full_name as user, l.action, l.details
                FROM audit_log l
                JOIN users u ON l.user_id = u.user_id";

        if ($start_date && $end_date) {
            $sql .= " WHERE DATE(l.timestamp) BETWEEN '$start_date' AND '$end_date'";
        } elseif ($start_date) {
            $sql .= " WHERE DATE(l.timestamp) >= '$start_date'";
        } elseif ($end_date) {
            $sql .= " WHERE DATE(l.timestamp) <= '$end_date'";
        }

        $sql .= " ORDER BY l.timestamp DESC";
        
        $result = $conn->query($sql);
        $logs = [];
        while($row = $result->fetch_assoc()) {
            // Format timestamp agar sesuai dengan tampilan frontend
            $row['timestamp'] = date("d/m/Y, H:i:s", strtotime($row['timestamp']));
            $logs[] = $row;
        }
        echo json_encode($logs);
        break;

    case 'POST':
        // --- LOGIKA UNTUK MENYIMPAN LOG BARU ---
        $data = json_decode(file_get_contents("php://input"));
        
        $user_id = intval($data->user_id);
        $action = $conn->real_escape_string($data->action);
        $details = $conn->real_escape_string($data->details);
        
        // Gabungkan status ke dalam detail jika ada
        if (!empty($data->status)) {
            $details .= " | Status: " . $conn->real_escape_string($data->status);
        }

        $sql = "INSERT INTO audit_log (user_id, action, details, timestamp) VALUES ('$user_id', '$action', '$details', NOW())";

        if ($conn->query($sql) === TRUE) {
            echo json_encode(["status" => "success", "message" => "Log berhasil disimpan."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Error: " . $conn->error]);
        }
        break;
    
    default:
        http_response_code(405); // Method Not Allowed
        echo json_encode(["message" => "Metode tidak diizinkan."]);
        break;
}

$conn->close();
?>
