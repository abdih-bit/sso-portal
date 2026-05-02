<?php
// api/sso_callback.php
// Menerima SSO token dari URL parameter, validasi ke SSO Portal,
// buat session PHP, dan auto-create/update local STL user.

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

// --- Map SSO jabatan ke role STL ---
function mapSsoRoleToStl(array $ssoUser): string {
    $ssoRole = $ssoUser['role']    ?? 'USER';
    $jabatan = trim($ssoUser['jabatan'] ?? '');
    if ($ssoRole === 'SUPERADMIN') return 'superadmin';
    // Mapping berdasarkan jabatan dari SSO Portal
    if ($jabatan === 'Head AR')   return 'admin_ho';
    if ($jabatan === 'KA Admin')  return 'admin_dc';
    // Fallback ke SSO role
    if ($ssoRole === 'ADMIN') return 'admin_ho';
    return 'admin_dc';
}

$stlRole  = mapSsoRoleToStl($ssoUser);
$jabatan  = trim($ssoUser['jabatan'] ?? '');
$pt       = trim($ssoUser['pt'] ?? '');

// --- Auto-create atau update STL user berdasarkan SSO user ---
try {
    // Pastikan kolom jabatan tersedia (idempotent migration)
    $pdo->exec("ALTER TABLE stl_users ADD COLUMN IF NOT EXISTS jabatan TEXT DEFAULT ''");

    // Update nama role agar sesuai jabatan SSO Portal
    $pdo->exec("UPDATE stl_roles SET role_name = 'Head AR'  WHERE role_id = 'admin_ho' AND role_name != 'Head AR'");
    $pdo->exec("UPDATE stl_roles SET role_name = 'KA Admin' WHERE role_id = 'admin_dc' AND role_name != 'KA Admin'");

    // Cari user berdasarkan sso_user_id
    $stmt = $pdo->prepare("SELECT user_id, role_id, area_id FROM stl_users WHERE sso_user_id = ?");
    $stmt->execute([$ssoUser['id']]);
    $stlUser = $stmt->fetch();

    // Cari area_id berdasarkan nama area dari SSO
    $areaId = null;
    if (!empty($ssoUser['area'])) {
        $stmtArea = $pdo->prepare("SELECT area_id FROM stl_areas WHERE area_name = ?");
        $stmtArea->execute([$ssoUser['area']]);
        $areaRow = $stmtArea->fetch();
        $areaId  = $areaRow ? $areaRow['area_id'] : null;
    }

    if (!$stlUser) {
        // Buat user baru
        $ins = $pdo->prepare(
            "INSERT INTO stl_users (sso_user_id, username, full_name, role_id, area_id, jabatan, status)
             VALUES (?, ?, ?, ?, ?, ?, 'Aktif')
             ON CONFLICT (sso_user_id) DO UPDATE
               SET username  = EXCLUDED.username,
                   full_name = EXCLUDED.full_name,
                   role_id   = EXCLUDED.role_id,
                   area_id   = COALESCE(EXCLUDED.area_id, stl_users.area_id),
                   jabatan   = EXCLUDED.jabatan"
        );
        $ins->execute([
            $ssoUser['id'],
            $ssoUser['username'],
            $ssoUser['fullName'] ?? $ssoUser['username'],
            $stlRole,
            $areaId,
            $jabatan,
        ]);

        $stmt2   = $pdo->prepare("SELECT user_id, role_id, area_id FROM stl_users WHERE sso_user_id = ?");
        $stmt2->execute([$ssoUser['id']]);
        $stlUser = $stmt2->fetch();
    } else {
        // Update nama/role/jabatan jika berubah dari SSO
        $upd = $pdo->prepare(
            "UPDATE stl_users SET username = ?, full_name = ?, role_id = ?, area_id = COALESCE(?, area_id), jabatan = ? WHERE sso_user_id = ?"
        );
        $upd->execute([
            $ssoUser['username'],
            $ssoUser['fullName'] ?? $ssoUser['username'],
            $stlRole,
            $areaId,
            $jabatan,
            $ssoUser['id'],
        ]);
    }

    // Ambil nama area
    $areaName = $ssoUser['area'] ?? null;

} catch (PDOException $e) {
    json_response(['status' => 'error', 'message' => 'Gagal menyinkronkan user.'], 500);
}

// --- Simpan session ---
$roleNameMap = ['superadmin' => 'Superadmin', 'admin_ho' => 'Head AR', 'admin_dc' => 'KA Admin'];
$_SESSION['stl_user'] = [
    'user_id'    => $stlUser['user_id'],
    'sso_id'     => $ssoUser['id'],
    'username'   => $ssoUser['username'],
    'full_name'  => $ssoUser['fullName'] ?? $ssoUser['username'],
    'role_id'    => $stlUser['role_id'],
    'role_name'  => $roleNameMap[$stlUser['role_id']] ?? $stlUser['role_id'],
    'area_id'    => $areaId ?? $stlUser['area_id'],
    'area_name'  => $areaName,
    'jabatan'    => $jabatan,
    'pt'         => $pt,
    'sso_role'   => $ssoUser['role'],
];

json_response(['status' => 'success', 'user' => $_SESSION['stl_user']]);
