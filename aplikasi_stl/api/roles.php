<?php
require_once __DIR__ . '/../db_connect.php';
$currentUser = require_auth();

$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? $_GET['id'] : null;

switch ($method) {
    case 'GET':
        $stmt = $pdo->query("SELECT * FROM stl_roles ORDER BY role_name ASC");
        json_response($stmt->fetchAll());
        break;

    case 'POST':
        if ($currentUser['role_id'] !== 'superadmin') {
            json_response(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }
        $data      = json_decode(file_get_contents('php://input'), true) ?? [];
        $role_id   = trim($data['role_id'] ?? '');
        $role_name = trim($data['role_name'] ?? '');

        if (!$role_id || !$role_name) {
            json_response(['status' => 'error', 'message' => 'ID Peran dan Nama Peran tidak boleh kosong.'], 400);
        }

        $chk = $pdo->prepare("SELECT role_id FROM stl_roles WHERE role_id = ?");
        $chk->execute([$role_id]);
        if ($chk->fetch()) {
            json_response(['status' => 'error', 'message' => 'ID Peran sudah ada.'], 409);
        }

        $pdo->prepare("INSERT INTO stl_roles (role_id, role_name) VALUES (?, ?)")->execute([$role_id, $role_name]);
        json_response(['status' => 'success', 'message' => 'Peran berhasil ditambahkan.']);
        break;

    case 'PUT':
        if ($currentUser['role_id'] !== 'superadmin') {
            json_response(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }
        $data      = json_decode(file_get_contents('php://input'), true) ?? [];
        $role_name = trim($data['role_name'] ?? '');

        if (!$id || !$role_name) {
            json_response(['status' => 'error', 'message' => 'Data tidak lengkap.'], 400);
        }

        $pdo->prepare("UPDATE stl_roles SET role_name = ? WHERE role_id = ?")->execute([$role_name, $id]);
        json_response(['status' => 'success', 'message' => 'Peran berhasil diperbarui.']);
        break;

    case 'DELETE':
        if ($currentUser['role_id'] !== 'superadmin') {
            json_response(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }
        if (!$id) {
            json_response(['status' => 'error', 'message' => 'ID role wajib ada.'], 400);
        }
        $pdo->prepare("DELETE FROM stl_roles WHERE role_id = ?")->execute([$id]);
        json_response(['status' => 'success', 'message' => 'Peran berhasil dihapus.']);
        break;

    default:
        json_response(['message' => 'Metode tidak diizinkan.'], 405);
}


// Menangani preflight request (OPTIONS) untuk CORS
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    http_response_code(200);
    exit();
}

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? $conn->real_escape_string($_GET['id']) : null;

switch ($method) {
    case 'GET':
        $sql = "SELECT * FROM roles ORDER BY role_name ASC";
        $result = $conn->query($sql);
        $roles = [];
        while($row = $result->fetch_assoc()) {
            $roles[] = $row;
        }
        echo json_encode($roles);
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"));
        
        $role_id = $conn->real_escape_string($data->role_id);
        $role_name = $conn->real_escape_string($data->role_name);

        if (empty($role_id) || empty($role_name)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "ID Peran dan Nama Peran tidak boleh kosong."]);
            exit();
        }

        $check_sql = "SELECT role_id FROM roles WHERE role_id = '$role_id'";
        $check_result = $conn->query($check_sql);
        if ($check_result->num_rows > 0) {
            http_response_code(409); // Conflict
            echo json_encode(["status" => "error", "message" => "ID Peran sudah ada."]);
            exit();
        }

        $sql = "INSERT INTO roles (role_id, role_name) VALUES ('$role_id', '$role_name')";
        
        if ($conn->query($sql) === TRUE) {
            echo json_encode(["status" => "success", "message" => "Peran berhasil ditambahkan."]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Error: " . $conn->error]);
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents("php://input"));
        $role_name = $conn->real_escape_string($data->role_name);
        
        if ($id && !empty($role_name)) {
            $sql = "UPDATE roles SET role_name='$role_name' WHERE role_id='$id'";
            if ($conn->query($sql) === TRUE) {
                echo json_encode(["status" => "success", "message" => "Peran berhasil diperbarui."]);
            } else {
                echo json_encode(["status" => "error", "message" => "Error: " . $conn->error]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Data tidak lengkap."]);
        }
        break;

    case 'DELETE':
        if ($id) {
            $sql = "DELETE FROM roles WHERE role_id='$id'";
            if ($conn->query($sql) === TRUE) {
                echo json_encode(["status" => "success", "message" => "Peran berhasil dihapus."]);
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
