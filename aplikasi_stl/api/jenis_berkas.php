<?php
require_once __DIR__ . '/../db_connect.php';
$currentUser = require_auth();

$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$protected_berkas = ['BPK dan EBP', 'Laporan', 'STNK'];

switch ($method) {
    case 'GET':
        $stmt = $pdo->query("SELECT * FROM stl_jenis_berkas ORDER BY nama_berkas ASC");
        json_response($stmt->fetchAll());
        break;

    case 'POST':
        if ($currentUser['role_id'] !== 'superadmin') {
            json_response(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }
        $data       = json_decode(file_get_contents('php://input'), true) ?? [];
        $nama_berkas = trim($data['nama_berkas'] ?? '');

        if (!$nama_berkas) {
            json_response(['status' => 'error', 'message' => 'Nama berkas tidak boleh kosong.'], 400);
        }

        $pdo->prepare("INSERT INTO stl_jenis_berkas (nama_berkas) VALUES (?)")->execute([$nama_berkas]);
        json_response(['status' => 'success', 'message' => 'Jenis berkas berhasil ditambahkan.']);
        break;

    case 'PUT':
        if ($currentUser['role_id'] !== 'superadmin') {
            json_response(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }
        if (!$id) {
            json_response(['status' => 'error', 'message' => 'ID wajib ada.'], 400);
        }

        $chk = $pdo->prepare("SELECT nama_berkas FROM stl_jenis_berkas WHERE id = ?");
        $chk->execute([$id]);
        $row = $chk->fetch();
        if ($row && in_array($row['nama_berkas'], $protected_berkas, true)) {
            json_response(['status' => 'error', 'message' => 'Jenis berkas default tidak dapat diubah.'], 403);
        }

        $data       = json_decode(file_get_contents('php://input'), true) ?? [];
        $nama_berkas = trim($data['nama_berkas'] ?? '');

        $pdo->prepare("UPDATE stl_jenis_berkas SET nama_berkas = ? WHERE id = ?")->execute([$nama_berkas, $id]);
        json_response(['status' => 'success', 'message' => 'Jenis berkas berhasil diperbarui.']);
        break;

    case 'DELETE':
        if ($currentUser['role_id'] !== 'superadmin') {
            json_response(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }
        if (!$id) {
            json_response(['status' => 'error', 'message' => 'ID wajib ada.'], 400);
        }

        $chk = $pdo->prepare("SELECT nama_berkas FROM stl_jenis_berkas WHERE id = ?");
        $chk->execute([$id]);
        $row = $chk->fetch();
        if ($row && in_array($row['nama_berkas'], $protected_berkas, true)) {
            json_response(['status' => 'error', 'message' => 'Jenis berkas default tidak dapat dihapus.'], 403);
        }

        $pdo->prepare("DELETE FROM stl_jenis_berkas WHERE id = ?")->execute([$id]);
        json_response(['status' => 'success', 'message' => 'Jenis berkas berhasil dihapus.']);
        break;

    default:
        json_response(['message' => 'Metode tidak diizinkan.'], 405);
}


switch ($method) {
    case 'GET':
        // Mengambil semua jenis berkas, diurutkan berdasarkan nama
        $sql = "SELECT * FROM jenis_berkas ORDER BY nama_berkas ASC";
        $result = $conn->query($sql);
        $berkas = [];
        while($row = $result->fetch_assoc()) {
            $berkas[] = $row;
        }
        echo json_encode($berkas);
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"));
        $nama_berkas = $conn->real_escape_string($data->nama_berkas);

        if (empty($nama_berkas)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Nama berkas tidak boleh kosong."]);
            exit();
        }

        $sql = "INSERT INTO jenis_berkas (nama_berkas) VALUES (?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $nama_berkas);
        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Jenis berkas berhasil ditambahkan."]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Error: " . $stmt->error]);
        }
        $stmt->close();
        break;

    case 'PUT':
        if ($id > 0) {
            // Cek apakah berkas yang akan diubah dilindungi
            $check_sql = "SELECT nama_berkas FROM jenis_berkas WHERE id = ?";
            $stmt = $conn->prepare($check_sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $berkas = $result->fetch_assoc();
            $stmt->close();

            if ($berkas && in_array($berkas['nama_berkas'], $protected_berkas)) {
                http_response_code(403); // Forbidden
                echo json_encode(["status" => "error", "message" => "Jenis berkas default tidak dapat diubah."]);
                exit();
            }

            $data = json_decode(file_get_contents("php://input"));
            $nama_berkas = $conn->real_escape_string($data->nama_berkas);
            $sql = "UPDATE jenis_berkas SET nama_berkas=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $nama_berkas, $id);
            if ($stmt->execute()) {
                echo json_encode(["status" => "success", "message" => "Jenis berkas berhasil diupdate."]);
            } else {
                http_response_code(500);
                echo json_encode(["status" => "error", "message" => "Error: " . $stmt->error]);
            }
            $stmt->close();
        }
        break;

    case 'DELETE':
        if ($id > 0) {
            // Cek apakah berkas yang akan dihapus dilindungi
            $check_sql = "SELECT nama_berkas FROM jenis_berkas WHERE id = ?";
            $stmt = $conn->prepare($check_sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $berkas = $result->fetch_assoc();
            $stmt->close();

            if ($berkas && in_array($berkas['nama_berkas'], $protected_berkas)) {
                http_response_code(403); // Forbidden
                echo json_encode(["status" => "error", "message" => "Jenis berkas default tidak dapat dihapus."]);
                exit();
            }

            $sql = "DELETE FROM jenis_berkas WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                echo json_encode(["status" => "success", "message" => "Jenis berkas berhasil dihapus."]);
            } else {
                http_response_code(500);
                echo json_encode(["status" => "error", "message" => "Error: " . $stmt->error]);
            }
            $stmt->close();
        }
        break;
}

$conn->close();
?>