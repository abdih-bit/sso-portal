<?php
require_once __DIR__ . '/../db_connect.php';
$currentUser = require_auth();

$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$protected_areas = ['Head Office MDR 1', 'Head Office MDR 2', 'Head Office MDR 3', 'Head Office MDR 4', 'All Access'];

switch ($method) {
    case 'GET':
        $type         = $_GET['type']         ?? 'all';
        $parent_ho_id = isset($_GET['parent_ho_id']) ? (int)$_GET['parent_ho_id'] : null;

        // Query langsung dari tabel areas (SSO Portal master data)
        $sql    = "SELECT a.id AS area_id, a.name AS area_name, a.is_ho, a.pt
                   FROM areas a";
        $params = [];
        $where  = [];

        if ($type === 'ho') {
            $where[] = 'a.is_ho = TRUE';
        } elseif ($type === 'dc') {
            $where[] = 'a.is_ho = FALSE';
        }

        if ($parent_ho_id) {
            // Filter DC yang satu PT dengan HO parent
            $where[]  = 'a.pt = (SELECT pt FROM areas WHERE id = ?)';
            $params[] = $parent_ho_id;
        }

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= " ORDER BY
                    CASE WHEN a.is_ho = TRUE THEN 1 ELSE 2 END,
                    a.pt ASC, a.name ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        json_response($stmt->fetchAll());
        break;

    default:
        json_response(['message' => 'Metode tidak diizinkan.'], 405);
}

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