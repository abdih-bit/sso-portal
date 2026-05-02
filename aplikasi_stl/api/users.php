<?php
require_once __DIR__ . '/../db_connect.php';
$currentUser = require_auth();

$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

switch ($method) {
    case 'GET':
        $sql    = "SELECT u.user_id, u.full_name, u.username, u.status,
                          a.id AS area_id, a.name AS area_name, r.role_id, r.role_name
                   FROM stl_users u
                   JOIN areas a ON u.area_id = a.id
                   JOIN stl_roles r ON u.role_id = r.role_id";
        $params = [];
        if ($id) {
            $sql   .= ' WHERE u.user_id = ?';
            $params = [$id];
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        json_response($stmt->fetchAll());
        break;

    case 'POST':
        if ($currentUser['role_id'] !== 'superadmin') {
            json_response(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }
        $data    = json_decode(file_get_contents('php://input'), true) ?? [];
        $name    = trim($data['name'] ?? '');
        $username = trim($data['username'] ?? '');
        $area_id = (int)($data['area_id'] ?? 0);
        $role_id = trim($data['role_id'] ?? '');
        $status  = $data['status'] ?? 'Aktif';

        if (!$name || !$username || !$area_id || !$role_id) {
            json_response(['status' => 'error', 'message' => 'Data tidak lengkap.'], 400);
        }

        $pdo->prepare(
            "INSERT INTO stl_users (full_name, username, role_id, area_id, status)
             VALUES (?, ?, ?, ?, ?)"
        )->execute([$name, $username, $role_id, $area_id, $status]);

        json_response(['status' => 'success', 'message' => 'Pengguna berhasil ditambahkan.']);
        break;

    case 'PUT':
        if ($currentUser['role_id'] !== 'superadmin') {
            json_response(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }
        if (!$id) {
            json_response(['status' => 'error', 'message' => 'ID user wajib ada.'], 400);
        }

        $data    = json_decode(file_get_contents('php://input'), true) ?? [];
        $name    = trim($data['name'] ?? '');
        $username = trim($data['username'] ?? '');
        $area_id = (int)($data['area_id'] ?? 0);
        $role_id = trim($data['role_id'] ?? '');
        $status  = $data['status'] ?? 'Aktif';

        $pdo->prepare(
            "UPDATE stl_users SET full_name = ?, username = ?, area_id = ?, role_id = ?, status = ?
             WHERE user_id = ?"
        )->execute([$name, $username, $area_id, $role_id, $status, $id]);

        json_response(['status' => 'success', 'message' => 'Pengguna berhasil diperbarui.']);
        break;

    case 'DELETE':
        if ($currentUser['role_id'] !== 'superadmin') {
            json_response(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }
        if (!$id) {
            json_response(['status' => 'error', 'message' => 'ID user wajib ada.'], 400);
        }

        // Jangan hapus user yang sedang login
        if ($id === (int)$currentUser['user_id']) {
            json_response(['status' => 'error', 'message' => 'Tidak bisa menghapus akun sendiri.'], 403);
        }

        $pdo->prepare("DELETE FROM stl_users WHERE user_id = ?")->execute([$id]);
        json_response(['status' => 'success', 'message' => 'Pengguna berhasil dihapus.']);
        break;

    default:
        json_response(['message' => 'Metode tidak diizinkan.'], 405);
}

// Pastikan user superadmin default ada di database
$superadmin_username = 'Superadmin';
$check_superadmin_sql = "SELECT user_id FROM users WHERE username = '$superadmin_username'";
$result = $conn->query($check_superadmin_sql);

if ($result->num_rows == 0) {
    // Superadmin tidak ada, maka buat baru
    $superadmin_password = 'strongadmin'; // (PERUBAHAN: Menyimpan password sebagai plain text)
    $superadmin_fullname = 'Super Administrator';
    $superadmin_role_id = 'superadmin';
    $superadmin_area_id = 1; // Asumsi ID 1 adalah untuk area default/HO
    $superadmin_status = 'Aktif';

    $insert_sql = "INSERT INTO users (username, password, full_name, role_id, area_id, status) 
                   VALUES ('$superadmin_username', '$superadmin_password', '$superadmin_fullname', '$superadmin_role_id', $superadmin_area_id, '$superadmin_status')";
    
    // Jalankan query tanpa output, karena ini adalah setup background
    $conn->query($insert_sql);
}
// --- End Superadmin User Setup ---

// Menangani preflight request (OPTIONS) untuk CORS
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

$id = isset($_GET['id']) ? intval($_GET['id']) : null;

switch ($method) {
    case 'GET':
        $sql = "SELECT u.user_id, u.full_name, u.username, u.status, a.area_id, a.area_name, r.role_id, r.role_name 
                FROM users u 
                JOIN areas a ON u.area_id = a.area_id 
                JOIN roles r ON u.role_id = r.role_id";
        if ($id) {
            $sql .= " WHERE u.user_id = $id";
        }
        $result = $conn->query($sql);
        $users = [];
        while($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        echo json_encode($users);
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"));
        
        $name = $conn->real_escape_string($data->name);
        $username = $conn->real_escape_string($data->username);
        $password = $conn->real_escape_string($data->password); // (PERUBAHAN: Menghapus password_hash)
        $area_id = intval($data->area_id);
        $role_id = $conn->real_escape_string($data->role_id);
        $status = $conn->real_escape_string($data->status);

        $sql = "INSERT INTO users (full_name, username, password, area_id, role_id, status) VALUES ('$name', '$username', '$password', $area_id, '$role_id', '$status')";
        
        if ($conn->query($sql) === TRUE) {
            echo json_encode(["status" => "success", "message" => "Pengguna berhasil ditambahkan."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Error: " . $conn->error]);
        }
        break;
        
    case 'PUT':
        if ($id) {
            // Cek apakah user yang diedit adalah superadmin
            $check_sql = "SELECT username FROM users WHERE user_id=$id";
            $check_result = $conn->query($check_sql);
            if ($check_result && $check_result->num_rows > 0) {
                $user_to_edit = $check_result->fetch_assoc();
                if ($user_to_edit['username'] === 'Superadmin') {
                    http_response_code(403); // Forbidden
                    echo json_encode(["status" => "error", "message" => "Superadmin tidak dapat diubah."]);
                    exit();
                }
            }

            $data = json_decode(file_get_contents("php://input"));
            
            $name = $conn->real_escape_string($data->name);
            $username = $conn->real_escape_string($data->username);
            $area_id = intval($data->area_id);
            $role_id = $conn->real_escape_string($data->role_id);
            $status = $conn->real_escape_string($data->status);

            $sql = "UPDATE users SET full_name='$name', username='$username', area_id=$area_id, role_id='$role_id', status='$status'";
            
            if (!empty($data->password)) {
                $password = $conn->real_escape_string($data->password); // (PERUBAHAN: Menghapus password_hash)
                $sql .= ", password='$password'";
            }
            
            $sql .= " WHERE user_id=$id";

            if ($conn->query($sql) === TRUE) {
                echo json_encode(["status" => "success", "message" => "Pengguna berhasil diupdate."]);
            } else {
                echo json_encode(["status" => "error", "message" => "Error: " . $conn->error]);
            }
        }
        break;
        
    case 'DELETE':
        if ($id) {
            // Cek apakah user yang akan dihapus adalah superadmin
            $check_sql = "SELECT username FROM users WHERE user_id=$id";
            $check_result = $conn->query($check_sql);
            if ($check_result && $check_result->num_rows > 0) {
                $user_to_delete = $check_result->fetch_assoc();
                if ($user_to_delete['username'] === 'Superadmin') {
                    http_response_code(403); // Forbidden
                    echo json_encode(["status" => "error", "message" => "Superadmin tidak dapat dihapus."]);
                    exit();
                }
            }
            $sql = "DELETE FROM users WHERE user_id=$id";
            if ($conn->query($sql) === TRUE) {
                echo json_encode(["status" => "success", "message" => "Pengguna berhasil dihapus."]);
            } else {
                echo json_encode(["status" => "error", "message" => "Error: " . $conn->error]);
            }
        }
        break;
        
    default:
        http_response_code(405); // Method Not Allowed
        echo json_encode(["message" => "Metode tidak diizinkan."]);
        break;
}

$conn->close();
?>