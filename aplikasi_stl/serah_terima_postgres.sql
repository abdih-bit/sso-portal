-- ============================================================
-- Skema PostgreSQL untuk Aplikasi Serah Terima Laporan (STL)
-- Target database: sso_portal (shared dengan SSO Portal)
-- Semua tabel menggunakan prefix stl_
-- ============================================================

-- ============================================================
-- 1. AREAS
-- ============================================================
CREATE TABLE IF NOT EXISTS stl_areas (
    area_id      SERIAL PRIMARY KEY,
    area_name    VARCHAR(100) NOT NULL,
    is_ho        BOOLEAN NOT NULL DEFAULT FALSE,
    parent_ho_id INT REFERENCES stl_areas(area_id) ON DELETE SET NULL
);

INSERT INTO stl_areas (area_id, area_name, is_ho, parent_ho_id) VALUES
(1,  'Head Office MDR 1', TRUE,  NULL),
(2,  'Head Office MDR 2', TRUE,  NULL),
(3,  'Head Office MDR 3', TRUE,  NULL),
(4,  'Head Office MDR 4', TRUE,  NULL),
(5,  'All Access',        TRUE,  NULL),
(6,  'DC Sibolga',        FALSE, 1),
(7,  'DC Padang Sidimpuan', FALSE, 1),
(8,  'DC Rantau Prapat',  FALSE, 1),
(9,  'DC Siantar',        FALSE, 2),
(10, 'DC Kabanjahe',      FALSE, 2),
(11, 'DC Stabat',         FALSE, 3),
(12, 'DC Binjai',         FALSE, 3),
(13, 'DC Tebing Tinggi',  FALSE, 3),
(14, 'DC Kisaran',        FALSE, 4),
(15, 'DC Tanjung Morawa', FALSE, 4),
(16, 'DC Lubuk Pakam',    FALSE, 4)
ON CONFLICT (area_id) DO NOTHING;

SELECT setval('stl_areas_area_id_seq', (SELECT MAX(area_id) FROM stl_areas));

-- ============================================================
-- 2. ROLES
-- ============================================================
CREATE TABLE IF NOT EXISTS stl_roles (
    role_id   VARCHAR(50) PRIMARY KEY,
    role_name VARCHAR(100) NOT NULL
);

INSERT INTO stl_roles (role_id, role_name) VALUES
('superadmin', 'Superadmin'),
('admin_ho',   'Admin HO'),
('admin_dc',   'Admin DC')
ON CONFLICT (role_id) DO NOTHING;

-- ============================================================
-- 3. USERS (terhubung ke SSO Portal via sso_user_id)
-- ============================================================
CREATE TABLE IF NOT EXISTS stl_users (
    user_id     SERIAL PRIMARY KEY,
    sso_user_id VARCHAR(100) UNIQUE,
    username    VARCHAR(50) NOT NULL UNIQUE,
    full_name   VARCHAR(100) NOT NULL,
    role_id     VARCHAR(50) REFERENCES stl_roles(role_id),
    area_id     INT REFERENCES stl_areas(area_id),
    status      VARCHAR(20) NOT NULL DEFAULT 'Aktif',
    created_at  TIMESTAMP NOT NULL DEFAULT NOW()
);

-- ============================================================
-- 4. JASA EKSPEDISI
-- ============================================================
CREATE TABLE IF NOT EXISTS stl_jasa_ekspedisi (
    id        SERIAL PRIMARY KEY,
    nama_jasa VARCHAR(50) NOT NULL UNIQUE
);

INSERT INTO stl_jasa_ekspedisi (id, nama_jasa) VALUES
(1,  'JNE'),
(2,  'J&T Express'),
(3,  'J&T Cargo'),
(4,  'LION PARCEL'),
(5,  'Ninja Xpress'),
(6,  'POS Indonesia'),
(7,  'SICEPAT'),
(8,  'Anteraja'),
(9,  'Indah Logistik Cargo'),
(10, 'JET Express'),
(11, 'TIKI')
ON CONFLICT (id) DO NOTHING;

SELECT setval('stl_jasa_ekspedisi_id_seq', (SELECT MAX(id) FROM stl_jasa_ekspedisi));

-- ============================================================
-- 5. JENIS BERKAS
-- ============================================================
CREATE TABLE IF NOT EXISTS stl_jenis_berkas (
    id          SERIAL PRIMARY KEY,
    nama_berkas VARCHAR(100) NOT NULL UNIQUE
);

INSERT INTO stl_jenis_berkas (id, nama_berkas) VALUES
(1, 'BPK dan EBP'),
(2, 'Laporan'),
(3, 'STNK'),
(4, 'Meterai')
ON CONFLICT (id) DO NOTHING;

SELECT setval('stl_jenis_berkas_id_seq', (SELECT MAX(id) FROM stl_jenis_berkas));

-- ============================================================
-- 6. SALES OFFICES
-- ============================================================
CREATE TABLE IF NOT EXISTS stl_sales_offices (
    so_id      SERIAL PRIMARY KEY,
    so_name    VARCHAR(255) NOT NULL,
    ho_area_id INT REFERENCES stl_areas(area_id)
);

INSERT INTO stl_sales_offices (so_id, so_name, ho_area_id) VALUES
(1, 'SO Panyabungan', 1)
ON CONFLICT (so_id) DO NOTHING;

SELECT setval('stl_sales_offices_so_id_seq', (SELECT MAX(so_id) FROM stl_sales_offices));

-- ============================================================
-- 7. DOCUMENTS (laporan 2 arah: DC <-> HO)
-- ============================================================
CREATE TABLE IF NOT EXISTS stl_documents (
    doc_id              SERIAL PRIMARY KEY,
    barcode_id          VARCHAR(20) NOT NULL UNIQUE,
    sender_user_id      INT REFERENCES stl_users(user_id),
    receiver_ho_user_id INT REFERENCES stl_users(user_id),
    receiver_dc_user_id INT REFERENCES stl_users(user_id),
    receiver_ho_area_id INT REFERENCES stl_areas(area_id),
    doc_type            VARCHAR(100),
    start_period        DATE,
    end_period          DATE,
    notes               TEXT,
    so_id               INT REFERENCES stl_sales_offices(so_id),
    doc_type_so         VARCHAR(255),
    start_period_so     DATE,
    end_period_so       DATE,
    return_notes        TEXT,
    status              VARCHAR(50) NOT NULL DEFAULT 'Dikirim ke HO',
    created_at          TIMESTAMP NOT NULL DEFAULT NOW(),
    received_at_ho      TIMESTAMP,
    check_notes         TEXT,
    checked_at          TIMESTAMP,
    returned_at_ho      TIMESTAMP,
    received_at_dc      TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_stl_documents_sender   ON stl_documents(sender_user_id);
CREATE INDEX IF NOT EXISTS idx_stl_documents_status   ON stl_documents(status);
CREATE INDEX IF NOT EXISTS idx_stl_documents_created  ON stl_documents(created_at DESC);

-- ============================================================
-- 8. EKSPEDISI (resi pengiriman dokumen)
-- ============================================================
CREATE TABLE IF NOT EXISTS stl_ekspedisi (
    id                SERIAL PRIMARY KEY,
    barcode_id        VARCHAR(20) NOT NULL,
    jenis_pengiriman  VARCHAR(20) NOT NULL CHECK (jenis_pengiriman IN ('DC_ke_HO', 'HO_ke_DC')),
    nomor_resi        VARCHAR(50) NOT NULL,
    jasa_ekspedisi_id INT REFERENCES stl_jasa_ekspedisi(id),
    user_id           INT REFERENCES stl_users(user_id),
    created_at        TIMESTAMP NOT NULL DEFAULT NOW(),
    UNIQUE (barcode_id, jenis_pengiriman)
);

-- ============================================================
-- 9. BERKAS SATU ARAH (laporan pengiriman 1 arah)
-- ============================================================
CREATE TABLE IF NOT EXISTS stl_berkas_satu_arah (
    id               SERIAL PRIMARY KEY,
    barcode_id       VARCHAR(20) NOT NULL,
    sender_user_id   INT REFERENCES stl_users(user_id),
    receiver_user_id INT REFERENCES stl_users(user_id),
    receiver_area_id INT REFERENCES stl_areas(area_id),
    jenis_berkas     VARCHAR(100),
    notes            TEXT,
    status           VARCHAR(50) NOT NULL DEFAULT 'Dikirim',
    created_at       TIMESTAMP NOT NULL DEFAULT NOW(),
    received_at      TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_stl_berkas_sender  ON stl_berkas_satu_arah(sender_user_id);
CREATE INDEX IF NOT EXISTS idx_stl_berkas_status  ON stl_berkas_satu_arah(status);

-- ============================================================
-- 10. AUDIT LOG
-- ============================================================
CREATE TABLE IF NOT EXISTS stl_audit_log (
    log_id    SERIAL PRIMARY KEY,
    user_id   INT REFERENCES stl_users(user_id) ON DELETE SET NULL,
    action    VARCHAR(100) NOT NULL,
    details   TEXT,
    timestamp TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_stl_audit_timestamp ON stl_audit_log(timestamp DESC);
