<?php
// api/sso_callback.php
// Menerima SSO token dari URL parameter, validasi ke SSO Portal,
// buat session PHP, dan auto-create/update local ARsync user.

require_once __DIR__ . '/../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['status' => 'error', 'message' => 'Method not allowed.'], 405);
}

$input    = json_decode(file_get_contents('php://input'), true) ?? [];
$ssoToken = trim($input['sso_token'] ?? '');

if (!$ssoToken) {
    json_response(['status' => 'error', 'message' => 'SSO token tidak ditemukan.'], 400);
}

// --- Validasi token ke SSO Portal (server-to-server) ---
$ch = curl_init(SSO_INTERNAL_URL . '/api/sso/validate');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS     => json_encode([
        'sso_token'     => $ssoToken,
        'client_id'     => SSO_CLIENT_ID,
        'client_secret' => SSO_CLIENT_SECRET,
    ]),
    CURLOPT_TIMEOUT        => 10,
]);
$result   = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($result === false || $httpCode !== 200) {
    json_response(['status' => 'error', 'message' => 'Validasi SSO gagal. Silakan login ulang melalui portal.'], 401);
}

$ssoData = json_decode($result, true);
if (!$ssoData || !isset($ssoData['user'])) {
    json_response(['status' => 'error', 'message' => 'Response SSO tidak valid.'], 401);
}

$ssoUser = $ssoData['user'];

// --- Map SSO role/jabatan ke role ARsync ---
function mapSsoRoleToArsync(array $ssoUser): string {
    $ssoRole = $ssoUser['role']    ?? 'USER';
    $jabatan = trim($ssoUser['jabatan'] ?? '');
    if ($ssoRole === 'SUPERADMIN')               return 'superadmin';
    if ($jabatan === 'ITE')                       return 'superadmin';
    if (in_array($jabatan, ['Head AR', 'Head Admin', 'KA Admin'], true)) return 'admin';
    if ($ssoRole === 'ADMIN')                    return 'admin';
    return 'petugas';
}

$arsyncRole = mapSsoRoleToArsync($ssoUser);
$jabatan    = trim($ssoUser['jabatan'] ?? '');

// --- Auto-create atau update ARsync user berdasarkan SSO user ---
try {
    $stmt = $pdo->prepare(
        "INSERT INTO arsync_users (sso_user_id, username, fullname, role, jabatan)
         VALUES (:sso_id, :username, :fullname, :role, :jabatan)
         ON CONFLICT (sso_user_id) DO UPDATE
           SET username = EXCLUDED.username,
               fullname = EXCLUDED.fullname,
               role     = EXCLUDED.role,
               jabatan  = EXCLUDED.jabatan"
    );
    $stmt->execute([
        ':sso_id'   => (string)$ssoUser['id'],
        ':username' => $ssoUser['username'] ?? $ssoUser['email'],
        ':fullname' => $ssoUser['fullName'] ?? $ssoUser['name'] ?? $ssoUser['username'],
        ':role'     => $arsyncRole,
        ':jabatan'  => $jabatan,
    ]);

    // Ambil data user yang baru tersimpan
    $find = $pdo->prepare("SELECT id, sso_user_id, username, fullname, role, jabatan FROM arsync_users WHERE sso_user_id = :sso_id");
    $find->execute([':sso_id' => (string)$ssoUser['id']]);
    $arsyncUser = $find->fetch();

} catch (PDOException $e) {
    json_response(['status' => 'error', 'message' => 'Gagal menyinkronkan user: ' . $e->getMessage()], 500);
}

// --- Simpan ke session PHP ---
$_SESSION['arsync_user'] = [
    'id'       => $arsyncUser['id'],
    'sso_id'   => $arsyncUser['sso_user_id'],
    'username' => $arsyncUser['username'],
    'fullname' => $arsyncUser['fullname'],
    'role'     => $arsyncUser['role'],
    'jabatan'  => $arsyncUser['jabatan'],
    'area'     => trim($ssoUser['area'] ?? ''),
    'pt'       => trim($ssoUser['pt']   ?? ''),
];

json_response([
    'status' => 'success',
    'user'   => $_SESSION['arsync_user'],
]);
