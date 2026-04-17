<?php
// File: api.php
// Router untuk semua request dari frontend Serah Terima SP Digital.
// Autentikasi menggunakan SSO Portal (tidak ada login lokal).

// Sembunyikan PHP warnings/notices agar tidak merusak JSON output
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

function json_response(string $status, $data = null): void {
    echo json_encode(['status' => $status, 'data' => $data]);
    exit();
}

/**
 * Map role SSO ke role aplikasi SPD.
 * SUPERADMIN                       → Super Admin
 * jabatan 'Admin Transport'        → Admin Transport
 * jabatan sub-FAT (Invoice, dll)   → jabatan itu sendiri (langsung dari SSO)
 * fallback divisi TRP              → Admin Transport
 * fallback lainnya                 → Admin Invoice
 */
function mapSsoRole(array $user): string {
    if ($user['role'] === 'SUPERADMIN') return 'Super Admin';
    // Sub-jabatan Admin FAT dikirim langsung oleh SSO sebagai jabatan efektif
    $jabatan = trim($user['jabatan'] ?? '');
    if ($jabatan === 'Admin Transport') return 'Admin Transport';
    if (in_array($jabatan, ['Admin Invoice', 'Admin Kasir', 'Admin Collection', 'Admin Settle', 'Admin CSF'])) {
        return $jabatan;
    }
    // Fallback: cek divisi untuk kompatibilitas user lama
    if (isset($user['divisi']) && strtoupper($user['divisi']) === 'TRP') return 'Admin Transport';
    return 'Admin Invoice';
}

$action = $_GET['action'] ?? 'check_session';
$input  = json_decode(file_get_contents('php://input'), true) ?? [];

switch ($action) {

    // ── SSO Login — dipanggil frontend setelah terima sso_token dari URL ──
    case 'sso_login':
        $ssoToken = trim($input['sso_token'] ?? '');
        if (!$ssoToken) {
            json_response('error', ['message' => 'SSO token tidak ditemukan.']);
        }

        // Validasi token ke SSO Portal (server-to-server)
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
            json_response('error', ['message' => 'Validasi SSO gagal. Silakan login ulang melalui portal.']);
        }

        $ssoData = json_decode($result, true);
        if (!$ssoData || !isset($ssoData['user'])) {
            json_response('error', ['message' => 'Response SSO tidak valid.']);
        }

        $ssoUser = $ssoData['user'];
        $_SESSION['user'] = [
            'id'       => $ssoUser['id'],
            'username' => $ssoUser['username'],
            'fullName' => $ssoUser['fullName'],
            'role'     => mapSsoRole($ssoUser),
            'ssoRole'  => $ssoUser['role'],
            'pt'       => $ssoUser['pt'] ?? null,
            'area'     => $ssoUser['area'] ?? null,
            'jabatan'  => $ssoUser['jabatan'] ?? null,
        ];

        json_response('success', $_SESSION['user']);
        break;

    // ── Logout ──
    case 'logout':
        session_destroy();
        json_response('success');
        break;

    // ── Cek sesi PHP aktif ──
    case 'check_session':
        if (isset($_SESSION['user'])) {
            json_response('success', $_SESSION['user']);
        } else {
            json_response('error', ['message' => 'Tidak ada sesi aktif.']);
        }
        break;

    // ── Endpoint yang memerlukan autentikasi ──
    default:
        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            json_response('error', ['message' => 'Akses ditolak. Silakan login melalui portal.']);
        }
        try {

        $currentUser = $_SESSION['user'];

        // ── Helper: scope filter berdasarkan PT dan Area/DC user ──
        // Super Admin & Head ACC : melihat semua data tanpa filter
        // Head AR                : melihat semua area dalam PT-nya
        // Lainnya                : hanya PT + area sendiri
        function getScopeFilter(array $user, string $alias = 'd'): array {
            $role    = $user['role'] ?? '';
            $jabatan = $user['jabatan'] ?? '';
            if ($role === 'Super Admin' || $jabatan === 'Head ACC') {
                return ['sql' => '', 'params' => []];
            }
            if ($jabatan === 'Head AR') {
                return ['sql' => " AND {$alias}.pt = ?", 'params' => [$user['pt']]];
            }
            return ['sql' => " AND {$alias}.pt = ? AND {$alias}.area = ?", 'params' => [$user['pt'], $user['area']]];
        }

        switch ($action) {

            // ── Dokumen ──────────────────────────────────────────────────────
            case 'check_document':
                $doc_id = $input['id'] ?? '';
                $scope  = getScopeFilter($currentUser);
                $stmt = $pdo->prepare(
                    "SELECT doc_id as id, title, pt, area FROM spd_documents d WHERE d.doc_id = ?" . $scope['sql']
                );
                $stmt->execute(array_merge([$doc_id], $scope['params']));
                $document = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($document) {
                    json_response('success', ['status' => 'found', 'document' => $document]);
                } else {
                    json_response('success', ['status' => 'not_found']);
                }
                break;

            case 'get_documents':
                $scope = getScopeFilter($currentUser);
                $stmt  = $pdo->prepare(
                    "SELECT doc_id as id, title, pt, area FROM spd_documents d WHERE 1=1" . $scope['sql'] . " ORDER BY d.created_at DESC"
                );
                $stmt->execute($scope['params']);
                json_response('success', $stmt->fetchAll(PDO::FETCH_ASSOC));
                break;

            case 'add_document':
                if ($currentUser['role'] === 'Admin Invoice') {
                    json_response('error', ['message' => 'Akses ditolak.']);
                }
                $stmt = $pdo->prepare("INSERT INTO spd_documents (doc_id, title, pt, area) VALUES (?, ?, ?, ?)");
                try {
                    $stmt->execute([$input['id'], $input['title'], $currentUser['pt'], $currentUser['area']]);
                    json_response('success', array_merge($input, ['pt' => $currentUser['pt'], 'area' => $currentUser['area']]));
                } catch (PDOException $e) {
                    json_response('error', ['message' => 'ID Dokumen sudah ada.']);
                }
                break;

            case 'delete_document':
                if ($currentUser['role'] === 'Admin Invoice') {
                    json_response('error', ['message' => 'Akses ditolak.']);
                }
                // Pastikan dokumen ada dalam scope user sebelum dihapus
                $scope = getScopeFilter($currentUser);
                $checkStmt = $pdo->prepare("SELECT doc_id FROM spd_documents d WHERE d.doc_id = ?" . $scope['sql']);
                $checkStmt->execute(array_merge([$input['id']], $scope['params']));
                if (!$checkStmt->fetch()) {
                    json_response('error', ['message' => 'Dokumen tidak ditemukan atau akses ditolak.']);
                }
                $stmt = $pdo->prepare("DELETE FROM spd_documents WHERE doc_id = ?");
                $stmt->execute([$input['id']]);
                json_response('success');
                break;

            // ── Audit Log ────────────────────────────────────────────────────
            case 'get_audit_log':
                $scope = getScopeFilter($currentUser);
                // Join ke spd_documents untuk filter pt/area
                // LEFT JOIN ke users karena user mungkin sudah dihapus (user_id nullable)
                $stmt  = $pdo->prepare("
                    SELECT al.timestamp, al.doc_id, al.action,
                           COALESCE(CONCAT(u.full_name, ' (', u.username, ')'), '[User Dihapus]') AS actor,
                           al.details, d.pt, d.area
                    FROM spd_audit_log al
                    LEFT JOIN users u ON al.user_id = u.id
                    JOIN spd_documents d ON al.doc_id = d.doc_id
                    WHERE 1=1" . $scope['sql'] . "
                    ORDER BY al.timestamp DESC
                ");
                $stmt->execute($scope['params']);
                json_response('success', $stmt->fetchAll(PDO::FETCH_ASSOC));
                break;

            case 'add_audit_log':
                // Validasi: dokumen harus dalam scope user
                $scope = getScopeFilter($currentUser);
                $checkStmt = $pdo->prepare("SELECT doc_id FROM spd_documents d WHERE d.doc_id = ?" . $scope['sql']);
                $checkStmt->execute(array_merge([$input['docId']], $scope['params']));
                if (!$checkStmt->fetch()) {
                    json_response('error', ['message' => 'Dokumen tidak ditemukan atau akses ditolak.']);
                }
                $stmt = $pdo->prepare(
                    "INSERT INTO spd_audit_log (doc_id, user_id, action, details) VALUES (?, ?, ?, ?)"
                );
                $stmt->execute([
                    $input['docId'],
                    $currentUser['id'],
                    $input['action'],
                    $input['details'],
                ]);
                json_response('success');
                break;

            default:
                json_response('error', ['message' => 'Action tidak dikenal.']);
        }
        } catch (PDOException $e) {
            http_response_code(500);
            json_response('error', ['message' => 'Terjadi kesalahan database. Pastikan migration sudah dijalankan.']);
        } catch (Throwable $e) {
            http_response_code(500);
            json_response('error', ['message' => 'Terjadi kesalahan server.']);
        }
        break;
}

