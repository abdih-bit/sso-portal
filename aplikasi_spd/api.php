<?php
// File: api.php
// Router untuk semua request dari frontend Serah Terima SP Digital.
// Autentikasi menggunakan SSO Portal (tidak ada login lokal).

header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

function json_response(string $status, $data = null): void {
    echo json_encode(['status' => $status, 'data' => $data]);
    exit();
}

/**
 * Map role SSO + divisi ke role aplikasi SPD.
 * SUPERADMIN          → Super Admin
 * divisi TRP          → Admin Transport
 * FAT / WRH / lainnya → Admin Invoice
 */
function mapSsoRole(array $user): string {
    if ($user['role'] === 'SUPERADMIN') return 'Super Admin';
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

        $currentUser = $_SESSION['user'];

        switch ($action) {

            // ── Dokumen ──────────────────────────────────────────────────────
            case 'check_document':
                $doc_id = $input['id'] ?? '';
                $stmt = $pdo->prepare("SELECT doc_id as id, title FROM spd_documents WHERE doc_id = ?");
                $stmt->execute([$doc_id]);
                $document = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($document) {
                    json_response('success', ['status' => 'found', 'document' => $document]);
                } else {
                    json_response('success', ['status' => 'not_found']);
                }
                break;

            case 'get_documents':
                $stmt = $pdo->query("SELECT doc_id as id, title FROM spd_documents ORDER BY created_at DESC");
                json_response('success', $stmt->fetchAll(PDO::FETCH_ASSOC));
                break;

            case 'add_document':
                if ($currentUser['role'] === 'Admin Invoice') {
                    json_response('error', ['message' => 'Akses ditolak.']);
                }
                $stmt = $pdo->prepare("INSERT INTO spd_documents (doc_id, title) VALUES (?, ?)");
                try {
                    $stmt->execute([$input['id'], $input['title']]);
                    json_response('success', $input);
                } catch (PDOException $e) {
                    json_response('error', ['message' => 'ID Dokumen sudah ada.']);
                }
                break;

            case 'delete_document':
                if ($currentUser['role'] === 'Admin Invoice') {
                    json_response('error', ['message' => 'Akses ditolak.']);
                }
                $stmt = $pdo->prepare("DELETE FROM spd_documents WHERE doc_id = ?");
                $stmt->execute([$input['id']]);
                json_response('success');
                break;

            // ── Audit Log ────────────────────────────────────────────────────
            case 'get_audit_log':
                $stmt = $pdo->query("
                    SELECT al.timestamp, al.doc_id, al.action,
                           CONCAT(u.full_name, ' (', u.username, ')') AS actor,
                           al.details
                    FROM spd_audit_log al
                    JOIN users u ON al.user_id = u.id
                    ORDER BY al.timestamp DESC
                ");
                json_response('success', $stmt->fetchAll(PDO::FETCH_ASSOC));
                break;

            case 'add_audit_log':
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
        break;
}

