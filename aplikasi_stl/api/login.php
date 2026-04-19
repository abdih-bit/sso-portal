<?php
// Login lokal dinonaktifkan.
// Autentikasi dilakukan melalui SSO Portal.
http_response_code(403);
header('Content-Type: application/json');
echo json_encode([
    'status'  => 'error',
    'message' => 'Login lokal dinonaktifkan. Silakan login melalui SSO Portal.',
]);


// Mengambil data JSON yang dikirim dari aplikasi frontend
$data = json_decode(file_get_contents("php://input"));

// Memastikan data username dan password tidak kosong
if (!empty($data->username) && !empty($data->password)) {
    $username = $conn->real_escape_string($data->username);
    $password = $conn->real_escape_string($data->password);

    // (DIUBAH) Query untuk mencari user dan menyertakan data Induk HO
    $sql = "SELECT 
                u.user_id, u.username, u.full_name, u.role_id, r.role_name, 
                a.area_id, a.area_name, 
                ho.area_id as parent_ho_id, ho.area_name as parent_ho_name
            FROM users u
            JOIN roles r ON u.role_id = r.role_id
            JOIN areas a ON u.area_id = a.area_id
            LEFT JOIN areas ho ON a.parent_ho_id = ho.area_id -- (BARU) Join ke tabel areas lagi untuk dapatkan nama HO
            WHERE u.username = '$username' AND u.password = '$password' AND u.status = 'Aktif'";

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Menyiapkan data response
        $response = [
            'status' => 'success',
            'message' => 'Login berhasil.',
            'user' => [
                'user_id' => $user['user_id'],
                'username' => $user['username'],
                'full_name' => $user['full_name'],
                'role_id' => $user['role_id'],
                'role_name' => $user['role_name'],
                'area_id' => $user['area_id'],
                'area_name' => $user['area_name'],
                'parent_ho_id' => $user['parent_ho_id'], // (BARU) Kirim ID Induk HO
                'parent_ho_name' => $user['parent_ho_name'] // (BARU) Kirim Nama Induk HO
            ]
        ];
    } else {
        $response = [
            'status' => 'error',
            'message' => 'Username atau password salah.'
        ];
    }
} else {
    $response = [
        'status' => 'error',
        'message' => 'Username dan password wajib diisi.'
    ];
}

// Mengirim response dalam format JSON
header('Content-Type: application/json');
echo json_encode($response);
?>