<?php
require_once __DIR__ . '/../db_connect.php';

// Cek session — semua method butuh login
$currentUser = require_auth();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $business_areas = $pdo->query("SELECT id, area_name, business_area_name FROM arsync_business_areas ORDER BY id")->fetchAll();
    $sales_offices  = $pdo->query("SELECT id, office_name, sales_office_name FROM arsync_sales_offices ORDER BY id")->fetchAll();
    json_response(['business_areas' => $business_areas, 'sales_offices' => $sales_offices]);

} elseif ($method === 'POST') {
    // Hanya admin yang boleh menambah atau mengubah (cek dari session, bukan dari client)
    if ($currentUser['role'] !== 'admin') {
        json_response(['success' => false, 'message' => 'Anda tidak memiliki izin untuk melakukan aksi ini.'], 403);
    }

    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        json_response(['success' => false, 'message' => 'Data tidak valid.'], 400);
    }

    $is_update = isset($data['action']) && $data['action'] === 'update';

    if ($is_update) {
        if (!isset($data['id'], $data['type'], $data['code'], $data['name'])) {
            json_response(['success' => false, 'message' => 'Data untuk update tidak lengkap.'], 400);
        }
        if ($data['type'] === 'business') {
            $stmt = $pdo->prepare("UPDATE arsync_business_areas SET area_name = :code, business_area_name = :name WHERE id = :id");
        } else {
            $stmt = $pdo->prepare("UPDATE arsync_sales_offices SET office_name = :code, sales_office_name = :name WHERE id = :id");
        }
        $stmt->execute([':code' => trim($data['code']), ':name' => trim($data['name']), ':id' => (int)$data['id']]);
        json_response(['success' => true]);
    } else {
        if (isset($data['area_code'], $data['area_name']) && trim($data['area_code']) !== '' && trim($data['area_name']) !== '') {
            $stmt = $pdo->prepare("INSERT INTO arsync_business_areas (area_name, business_area_name) VALUES (:code, :name)");
            $stmt->execute([':code' => trim($data['area_code']), ':name' => trim($data['area_name'])]);
            json_response(['success' => true]);
        } elseif (isset($data['office_code'], $data['office_name']) && trim($data['office_code']) !== '' && trim($data['office_name']) !== '') {
            $stmt = $pdo->prepare("INSERT INTO arsync_sales_offices (office_name, sales_office_name) VALUES (:code, :name)");
            $stmt->execute([':code' => trim($data['office_code']), ':name' => trim($data['office_name'])]);
            json_response(['success' => true]);
        } else {
            json_response(['success' => false, 'message' => 'Data tidak lengkap atau kosong.'], 400);
        }
    }

} elseif ($method === 'DELETE') {
    // Hanya admin yang boleh menghapus
    if ($currentUser['role'] !== 'admin') {
        json_response(['success' => false, 'message' => 'Anda tidak memiliki izin untuk melakukan aksi ini.'], 403);
    }

    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        json_response(['success' => false, 'message' => 'Data tidak valid.'], 400);
    }

    if (isset($data['area_id'])) {
        $stmt = $pdo->prepare("DELETE FROM arsync_business_areas WHERE id = :id");
        $stmt->execute([':id' => (int)$data['area_id']]);
        json_response(['success' => true]);
    } elseif (isset($data['office_id'])) {
        $stmt = $pdo->prepare("DELETE FROM arsync_sales_offices WHERE id = :id");
        $stmt->execute([':id' => (int)$data['office_id']]);
        json_response(['success' => true]);
    } else {
        json_response(['success' => false, 'message' => 'ID tidak ditemukan.'], 400);
    }

} else {
    json_response(['error' => 'Method not allowed'], 405);
}

} elseif ($method === 'POST') {
    // Menambahkan atau Memperbarui business area atau sales office
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Pengecekan keamanan: Hanya ITE yang boleh menambah atau mengubah
    if (!isset($data['currentUserJabatan']) || $data['currentUserJabatan'] !== 'ITE') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki izin untuk melakukan aksi ini.']);
        exit;
    }

    $is_update = isset($data['action']) && $data['action'] == 'update';
    $stmt = null;

    if ($is_update) {
        if (!isset($data['id'], $data['type'], $data['code'], $data['name'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Data untuk update tidak lengkap.']);
            exit;
        }
        if ($data['type'] == 'business') {
            $sql = "UPDATE business_areas SET area_name = ?, business_area_name = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt) $stmt->bind_param("ssi", $data['code'], $data['name'], $data['id']);
        } elseif ($data['type'] == 'sales') {
            $sql = "UPDATE sales_offices SET office_name = ?, sales_office_name = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt) $stmt->bind_param("ssi", $data['code'], $data['name'], $data['id']);
        }
    } else {
        if (isset($data['area_code'], $data['area_name'])) {
            $sql = "INSERT INTO business_areas (area_name, business_area_name) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            if ($stmt) $stmt->bind_param("ss", $data['area_code'], $data['area_name']);
        } elseif (isset($data['office_code'], $data['office_name'])) {
            $sql = "INSERT INTO sales_offices (office_name, sales_office_name) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            if ($stmt) $stmt->bind_param("ss", $data['office_code'], $data['office_name']);
        }
    }

    if ($stmt && $stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Eksekusi database gagal. Error: ' . ($stmt ? $stmt->error : $conn->error)]);
    }
    if ($stmt) $stmt->close();

} elseif ($method === 'DELETE') {
    // Menghapus business area atau sales office
    $data = json_decode(file_get_contents('php://input'), true);

    // Pengecekan keamanan: Hanya ITE yang boleh menghapus
    if (!isset($data['currentUserJabatan']) || $data['currentUserJabatan'] !== 'ITE') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki izin untuk melakukan aksi ini.']);
        exit;
    }

    if (isset($data['area_id'])) {
        $stmt = $conn->prepare("DELETE FROM business_areas WHERE id = ?");
        $stmt->bind_param("i", $data['area_id']);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus Business Area.']);
        }
        $stmt->close();
    } elseif (isset($data['office_id'])) {
        $stmt = $conn->prepare("DELETE FROM sales_offices WHERE id = ?");
        $stmt->bind_param("i", $data['office_id']);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus Sales Office.']);
        }
        $stmt->close();
    }
}

$conn->close();
?>

