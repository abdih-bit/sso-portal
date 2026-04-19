<?php
require_once __DIR__ . '/../db_connect.php';
$currentUser = require_auth();

$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

switch ($method) {
    case 'GET':
        $stmt = $pdo->query(
            "SELECT s.*, a.area_name AS ho_area_name
             FROM stl_sales_offices s
             LEFT JOIN stl_areas a ON s.ho_area_id = a.area_id
             ORDER BY s.so_name ASC"
        );
        json_response($stmt->fetchAll());
        break;

    case 'POST':
        if ($currentUser['role_id'] !== 'superadmin') {
            json_response(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }
        $data       = json_decode(file_get_contents('php://input'), true) ?? [];
        $so_name    = trim($data['so_name'] ?? '');
        $ho_area_id = !empty($data['ho_area_id']) ? (int)$data['ho_area_id'] : null;

        if (!$so_name) {
            json_response(['status' => 'error', 'message' => 'Nama Sales Office tidak boleh kosong.'], 400);
        }
        if (!$ho_area_id) {
            json_response(['status' => 'error', 'message' => 'Induk HO wajib dipilih.'], 400);
        }

        $pdo->prepare("INSERT INTO stl_sales_offices (so_name, ho_area_id) VALUES (?, ?)")->execute([$so_name, $ho_area_id]);
        json_response(['status' => 'success', 'message' => 'Sales Office berhasil ditambahkan.']);
        break;

    case 'PUT':
        if ($currentUser['role_id'] !== 'superadmin') {
            json_response(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }
        $data       = json_decode(file_get_contents('php://input'), true) ?? [];
        $so_name    = trim($data['so_name'] ?? '');
        $ho_area_id = !empty($data['ho_area_id']) ? (int)$data['ho_area_id'] : null;

        if (!$id || !$so_name || !$ho_area_id) {
            json_response(['status' => 'error', 'message' => 'Data tidak lengkap.'], 400);
        }

        $pdo->prepare("UPDATE stl_sales_offices SET so_name = ?, ho_area_id = ? WHERE so_id = ?")->execute([$so_name, $ho_area_id, $id]);
        json_response(['status' => 'success', 'message' => 'Sales Office berhasil diperbarui.']);
        break;

    case 'DELETE':
        if ($currentUser['role_id'] !== 'superadmin') {
            json_response(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }
        if (!$id) {
            json_response(['status' => 'error', 'message' => 'ID wajib ada.'], 400);
        }
        $pdo->prepare("DELETE FROM stl_sales_offices WHERE so_id = ?")->execute([$id]);
        json_response(['status' => 'success', 'message' => 'Sales Office berhasil dihapus.']);
        break;

    default:
        json_response(['message' => 'Metode tidak diizinkan.'], 405);
}


switch ($method) {
    case 'GET':
        // (BARU) Mengambil data sales office beserta nama Induk HO-nya
        $sql = "SELECT s.*, a.area_name as ho_area_name
                FROM sales_offices s
                LEFT JOIN areas a ON s.ho_area_id = a.area_id
                ORDER BY s.so_name ASC";
        $result = $conn->query($sql);
        $offices = [];
        while($row = $result->fetch_assoc()) {
            $offices[] = $row;
        }
        echo json_encode($offices);
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"));
        $so_name = $conn->real_escape_string($data->so_name);
        // (BARU) Ambil ho_area_id
        $ho_area_id = isset($data->ho_area_id) && !empty($data->ho_area_id) ? intval($data->ho_area_id) : "NULL";


        if (empty($so_name)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Nama Sales Office tidak boleh kosong."]);
            exit();
        }
         if ($ho_area_id == "NULL") {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Induk HO wajib dipilih."]);
            exit();
        }

        $sql = "INSERT INTO sales_offices (so_name, ho_area_id) VALUES ('$so_name', $ho_area_id)";
        
        if ($conn->query($sql) === TRUE) {
            echo json_encode(["status" => "success", "message" => "Sales Office berhasil ditambahkan."]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Error: " . $conn->error]);
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents("php://input"));
        $so_name = $conn->real_escape_string($data->so_name);
        // (BARU) Ambil ho_area_id
        $ho_area_id = isset($data->ho_area_id) && !empty($data->ho_area_id) ? intval($data->ho_area_id) : "NULL";
        
        if ($id && !empty($so_name) && $ho_area_id != "NULL") {
            $sql = "UPDATE sales_offices SET so_name='$so_name', ho_area_id=$ho_area_id WHERE so_id=$id";
            if ($conn->query($sql) === TRUE) {
                echo json_encode(["status" => "success", "message" => "Sales Office berhasil diperbarui."]);
            } else {
                echo json_encode(["status" => "error", "message" => "Error: " . $conn->error]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Data tidak lengkap. Nama dan Induk HO wajib diisi."]);
        }
        break;

    case 'DELETE':
        if ($id) {
            // (BARU) Cek keterkaitan di tabel documents
            $check_sql = "SELECT barcode_id FROM documents WHERE so_id = $id";
            $check_result = $conn->query($check_sql);
            if ($check_result->num_rows > 0) {
                http_response_code(409); // Conflict
                echo json_encode(["status" => "error", "message" => "Sales Office tidak dapat dihapus karena masih digunakan di dokumen (2 Arah)."]);
                exit();
            }
            
            $sql = "DELETE FROM sales_offices WHERE so_id=$id";
            if ($conn->query($sql) === TRUE) {
                echo json_encode(["status" => "success", "message" => "Sales Office berhasil dihapus."]);
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