<?php
require_once __DIR__ . '/../db_connect.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Mengambil semua data pengguna (password tidak diambil)
    $stmt = $pdo->query("SELECT id, fullname, username, role, jabatan FROM arsync_users ORDER BY id");
    echo json_encode($stmt->fetchAll());

} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? 'create';

    if ($action === 'create') {
        // Hanya ITE atau Head AR yang boleh membuat pengguna
        if (!isset($data['currentUserJabatan']) || !in_array($data['currentUserJabatan'], ['ITE', 'Head AR'], true)) {
            json_response(['success' => false, 'message' => 'Anda tidak memiliki izin untuk menambah pengguna.'], 403);
        }
        $stmt = $pdo->prepare("INSERT INTO arsync_users (fullname, username, role, jabatan) VALUES (:fullname, :username, :role, :jabatan)");
        $stmt->execute([
            ':fullname' => $data['fullname'],
            ':username' => $data['username'],
            ':role'     => $data['role'],
            ':jabatan'  => $data['jabatan'] ?? '',
        ]);
        echo json_encode(['success' => true]);

    } elseif ($action === 'update') {
        // Hanya ITE yang boleh mengubah pengguna
        if (!isset($data['currentUserJabatan']) || $data['currentUserJabatan'] !== 'ITE') {
            json_response(['success' => false, 'message' => 'Anda tidak memiliki izin untuk mengubah data pengguna.'], 403);
        }
        $stmt = $pdo->prepare("UPDATE arsync_users SET fullname = :fullname, username = :username, role = :role, jabatan = :jabatan WHERE id = :id");
        $stmt->execute([
            ':fullname' => $data['fullname'],
            ':username' => $data['username'],
            ':role'     => $data['role'],
            ':jabatan'  => $data['jabatan'] ?? '',
            ':id'       => (int)$data['id'],
        ]);
        echo json_encode(['success' => true]);
    }

} elseif ($method === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);

    // Hanya ITE yang boleh menghapus
    if (!isset($data['currentUserJabatan']) || $data['currentUserJabatan'] !== 'ITE') {
        json_response(['success' => false, 'message' => 'Anda tidak memiliki izin untuk melakukan aksi ini.'], 403);
    }

    if (isset($data['id'])) {
        if ((int)$data['id'] === 1) {
            echo json_encode(['success' => false, 'message' => 'Admin utama tidak dapat dihapus.']);
            exit();
        }
        $stmt = $pdo->prepare("DELETE FROM arsync_users WHERE id = :id");
        $stmt->execute([':id' => (int)$data['id']]);
        echo json_encode(['success' => true]);
    }
}
?>

