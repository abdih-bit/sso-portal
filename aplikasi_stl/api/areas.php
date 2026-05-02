<?php
require_once __DIR__ . '/../db_connect.php';
$currentUser = require_auth();

$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$protected_areas = ['Head Office MDR 1', 'Head Office MDR 2', 'Head Office MDR 3', 'Head Office MDR 4', 'All Access'];

switch ($method) {
    case 'GET':
        $type        = $_GET['type']        ?? 'all';
        $parent_ho_id = isset($_GET['parent_ho_id']) ? (int)$_GET['parent_ho_id'] : null;

        // Auto-sync: tambahkan area dari tabel users SSO yang belum ada di stl_areas
        $pdo->exec("
            INSERT INTO stl_areas (area_name, is_ho)
            SELECT DISTINCT u.area, FALSE
            FROM users u
            WHERE u.area IS NOT NULL
              AND u.area <> ''
              AND NOT EXISTS (
                  SELECT 1 FROM stl_areas a WHERE a.area_name = u.area
              )
        ");

        $sql    = "SELECT a.*, ho.area_name AS parent_ho_name
                   FROM stl_areas a
                   LEFT JOIN stl_areas ho ON a.parent_ho_id = ho.area_id";
        $params = [];
        $where  = [];

        if ($type === 'ho') {
            $where[] = 'a.is_ho = TRUE';
        } elseif ($type === 'dc') {
            $where[] = 'a.is_ho = FALSE';
        }

        if ($parent_ho_id) {
            $where[]  = 'a.parent_ho_id = ?';
            $params[] = $parent_ho_id;
        }

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= " ORDER BY
                    CASE
                        WHEN a.area_name = 'Head Office MDR 1' THEN 1
                        WHEN a.area_name = 'Head Office MDR 2' THEN 2
                        WHEN a.area_name = 'Head Office MDR 3' THEN 3
                        WHEN a.area_name = 'Head Office MDR 4' THEN 4
                        WHEN a.area_name LIKE 'Head Office%'   THEN 5
                        WHEN a.area_name = 'All Access'        THEN 6
                        ELSE 7
                    END, a.area_name ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        json_response($stmt->fetchAll());
        break;

    case 'POST':
        if ($currentUser['role_id'] !== 'superadmin') {
            json_response(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }
        $data       = json_decode(file_get_contents('php://input'), true) ?? [];
        $area_name  = trim($data['area_name'] ?? '');
        $is_ho      = !empty($data['is_ho']) ? 'TRUE' : 'FALSE';
        $parent_id  = !empty($data['parent_ho_id']) ? (int)$data['parent_ho_id'] : null;

        if (!$area_name) {
            json_response(['status' => 'error', 'message' => 'Nama area tidak boleh kosong.'], 400);
        }

        $stmt = $pdo->prepare(
            "INSERT INTO stl_areas (area_name, is_ho, parent_ho_id) VALUES (?, ?, ?) RETURNING area_id"
        );
        $stmt->execute([$area_name, $is_ho === 'TRUE', $parent_id]);
        json_response(['status' => 'success', 'message' => 'Area berhasil ditambahkan.']);
        break;

    case 'PUT':
        if ($currentUser['role_id'] !== 'superadmin') {
            json_response(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }
        if (!$id) {
            json_response(['status' => 'error', 'message' => 'ID area wajib ada.'], 400);
        }

        // Cek apakah area dilindungi
        $chk = $pdo->prepare("SELECT area_name FROM stl_areas WHERE area_id = ?");
        $chk->execute([$id]);
        $row = $chk->fetch();
        if ($row && in_array($row['area_name'], $protected_areas, true)) {
            json_response(['status' => 'error', 'message' => 'Area default tidak dapat diubah.'], 403);
        }

        $data      = json_decode(file_get_contents('php://input'), true) ?? [];
        $area_name = trim($data['area_name'] ?? '');
        $is_ho     = !empty($data['is_ho']);
        $parent_id = !empty($data['parent_ho_id']) ? (int)$data['parent_ho_id'] : null;

        if (!$area_name) {
            json_response(['status' => 'error', 'message' => 'Nama area tidak boleh kosong.'], 400);
        }

        $stmt = $pdo->prepare(
            "UPDATE stl_areas SET area_name = ?, is_ho = ?, parent_ho_id = ? WHERE area_id = ?"
        );
        $stmt->execute([$area_name, $is_ho, $parent_id, $id]);
        json_response(['status' => 'success', 'message' => 'Area berhasil diperbarui.']);
        break;

    case 'DELETE':
        if ($currentUser['role_id'] !== 'superadmin') {
            json_response(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }
        if (!$id) {
            json_response(['status' => 'error', 'message' => 'ID area wajib ada.'], 400);
        }

        $chk = $pdo->prepare("SELECT area_name FROM stl_areas WHERE area_id = ?");
        $chk->execute([$id]);
        $row = $chk->fetch();
        if ($row && in_array($row['area_name'], $protected_areas, true)) {
            json_response(['status' => 'error', 'message' => 'Area default tidak dapat dihapus.'], 403);
        }

        $pdo->prepare("DELETE FROM stl_areas WHERE area_id = ?")->execute([$id]);
        json_response(['status' => 'success', 'message' => 'Area berhasil dihapus.']);
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
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");


// --- (BARU) One-time rename untuk "Head Office (HO)" menjadi "Head Office MDR 3" ---
$conn->query("UPDATE areas SET area_name = 'Head Office MDR 3' WHERE area_name = 'Head Office (HO)'");
// --- End One-time rename ---

// Fungsi untuk memastikan area default ada di database
function ensure_default_areas($conn) {
    // (BARU) Daftar area default yang diperbarui
    $default_areas = ['Head Office MDR 1', 'Head Office MDR 2', 'Head Office MDR 3', 'Head Office MDR 4', 'All Access'];
    
    foreach ($default_areas as $area_name) {
        // Cek apakah area sudah ada
        $check_sql = "SELECT area_id FROM areas WHERE area_name = ?";
        $stmt_check = $conn->prepare($check_sql);
        $stmt_check->bind_param("s", $area_name);
        $stmt_check->execute();
        $result = $stmt_check->get_result();
        
        // Jika tidak ada, baru tambahkan
        if ($result->num_rows == 0) {
            // (BARU) Tentukan apakah ini area HO atau bukan
            $is_ho = (strpos($area_name, 'Head Office') !== false || $area_name === 'All Access') ? 1 : 0;
            
            $insert_sql = "INSERT INTO areas (area_name, is_ho) VALUES (?, ?)";
            $stmt_insert = $conn->prepare($insert_sql);
            $stmt_insert->bind_param("si", $area_name, $is_ho);
            $stmt_insert->execute();
            $stmt_insert->close();
        }
        $stmt_check->close();
    }
}


ensure_default_areas($conn);

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// (BARU) Daftar area yang dilindungi
$protected_areas = ['Head Office MDR 1', 'Head Office MDR 2', 'Head Office MDR 3', 'Head Office MDR 4', 'All Access'];

switch ($method) {
    case 'GET':
        $type = isset($_GET['type']) ? $_GET['type'] : 'all'; // Tipe baru: 'all', 'ho', 'dc'
        $parent_ho_id = isset($_GET['parent_ho_id']) ? intval($_GET['parent_ho_id']) : null; // (BARU) Filter berdasarkan parent HO

        // (BARU) Logika pengambilan data yang diperbarui dengan pengurutan khusus
        $sql = "SELECT a.*, ho.area_name as parent_ho_name 
                FROM areas a
                LEFT JOIN areas ho ON a.parent_ho_id = ho.area_id";
        
        $where_clauses = [];
        if ($type == 'ho') {
            $where_clauses[] = "a.is_ho = 1";
        } elseif ($type == 'dc') {
            $where_clauses[] = "a.is_ho = 0";
        }
        
        // (BARU) Tambahkan filter parent_ho_id jika ada
        if ($parent_ho_id) {
            $where_clauses[] = "a.parent_ho_id = $parent_ho_id";
        }

        if (!empty($where_clauses)) {
            $sql .= " WHERE " . implode(' AND ', $where_clauses);
        }

        // (BARU) Logika pengurutan khusus
        $sql .= " ORDER BY 
                    CASE 
                        WHEN a.area_name = 'Head Office MDR 1' THEN 1
                        WHEN a.area_name = 'Head Office MDR 2' THEN 2
                        WHEN a.area_name = 'Head Office MDR 3' THEN 3
                        WHEN a.area_name = 'Head Office MDR 4' THEN 4
                        WHEN a.area_name LIKE 'Head Office%' THEN 5
                        WHEN a.area_name = 'All Access' THEN 6
                        ELSE 7
                    END,
                    a.area_name ASC";
                    
        $result = $conn->query($sql);
        $areas = [];
        while($row = $result->fetch_assoc()) {
            $areas[] = $row;
        }
        echo json_encode($areas);
        break;


    case 'POST':
        $data = json_decode(file_get_contents("php://input"));
        $area_name = $conn->real_escape_string($data->area_name);
        // (BARU) Ambil parent_ho_id dan is_ho
        $parent_ho_id = isset($data->parent_ho_id) && !empty($data->parent_ho_id) ? intval($data->parent_ho_id) : "NULL";
        $is_ho = isset($data->is_ho) ? intval($data->is_ho) : 0;

        $sql = "INSERT INTO areas (area_name, is_ho, parent_ho_id) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        // Perlu penyesuaian bind_param jika $parent_ho_id adalah NULL
        if ($parent_ho_id == "NULL") {
             $parent_ho_id_val = null;
             $stmt->bind_param("sis", $area_name, $is_ho, $parent_ho_id_val);
        } else {
             $stmt->bind_param("sii", $area_name, $is_ho, $parent_ho_id);
        }

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Area berhasil ditambahkan."]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Error: " . $stmt->error]);
        }
        $stmt->close();
        break;


    case 'PUT':
        if ($id > 0) {
            // Cek apakah area yang akan diubah adalah area yang dilindungi
            $check_sql = "SELECT area_name FROM areas WHERE area_id = ?";
            $stmt = $conn->prepare($check_sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $area = $result->fetch_assoc();
            $stmt->close();

            if ($area && in_array($area['area_name'], $protected_areas)) {
                http_response_code(403); // Forbidden
                echo json_encode(["status" => "error", "message" => "Area default tidak dapat diubah."]);
                exit();
            }

            $data = json_decode(file_get_contents("php://input"));
            $area_name = $conn->real_escape_string($data->area_name);
            // (BARU) Ambil parent_ho_id
            $parent_ho_id = isset($data->parent_ho_id) && !empty($data->parent_ho_id) ? intval($data->parent_ho_id) : null;

            $sql = "UPDATE areas SET area_name=?, parent_ho_id=? WHERE area_id=?";
            $stmt = $conn->prepare($sql);
            
            if ($parent_ho_id === null) {
                $stmt->bind_param("ssi", $area_name, $parent_ho_id, $id); // Bind as string if null
            } else {
                $stmt->bind_param("sii", $area_name, $parent_ho_id, $id); // Bind as integer if not null
            }
            
            if ($stmt->execute()) {
                echo json_encode(["status" => "success", "message" => "Area berhasil diupdate."]);
            } else {
                http_response_code(500);
                echo json_encode(["status" => "error", "message" => "Error updating record: " . $stmt->error]);
            }
            $stmt->close();
        }
        break;


    case 'DELETE':
        if ($id > 0) {
            // Cek apakah area yang akan dihapus adalah area yang dilindungi
            $check_sql = "SELECT area_name FROM areas WHERE area_id = ?";
            $stmt = $conn->prepare($check_sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $area = $result->fetch_assoc();
            $stmt->close();

            if ($area && in_array($area['area_name'], $protected_areas)) {
                http_response_code(403); // Forbidden
                echo json_encode(["status" => "error", "message" => "Area default tidak dapat dihapus."]);
                exit();
            }

            // (BARU) Cek keterkaitan sebelum menghapus
            // 1. Cek di users
            $check_users = $conn->query("SELECT user_id FROM users WHERE area_id = $id");
            if ($check_users->num_rows > 0) {
                 http_response_code(409); // Conflict
                 echo json_encode(["status" => "error", "message" => "Area tidak dapat dihapus karena masih digunakan oleh pengguna."]);
                 exit();
            }
            // 2. Cek di areas (sebagai parent_ho_id)
            $check_parent = $conn->query("SELECT area_id FROM areas WHERE parent_ho_id = $id");
            if ($check_parent->num_rows > 0) {
                 http_response_code(409); // Conflict
                 echo json_encode(["status" => "error", "message" => "Area tidak dapat dihapus karena masih menjadi Induk HO untuk area lain."]);
                 exit();
            }
             // 3. Cek di sales_offices (sebagai ho_area_id)
            $check_so = $conn->query("SELECT so_id FROM sales_offices WHERE ho_area_id = $id");
            if ($check_so->num_rows > 0) {
                 http_response_code(409); // Conflict
                 echo json_encode(["status" => "error", "message" => "Area tidak dapat dihapus karena masih menjadi Induk HO untuk Sales Office."]);
                 exit();
            }


            $sql = "DELETE FROM areas WHERE area_id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                echo json_encode(["status" => "success", "message" => "Area berhasil dihapus."]);
            } else {
                http_response_code(500);
                echo json_encode(["status" => "error", "message" => "Error deleting record: " . $stmt->error]);
            }
            $stmt->close();
        }
        break;
        
    default:
        http_response_code(405); // Method Not Allowed
        echo json_encode(["message" => "Metode tidak diizinkan."]);
        break;
}

$conn->close();
?>