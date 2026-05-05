-- ============================================================
-- Skema PostgreSQL untuk Aplikasi ARsync Portal
-- Target database: sso_portal (shared dengan SSO Portal)
-- Semua tabel menggunakan prefix arsync_
-- Jalankan sebagai user: sso_user
-- ============================================================

-- ============================================================
-- 1. ROLES ARSYNC
-- ============================================================
CREATE TABLE IF NOT EXISTS arsync_roles (
    role_id   VARCHAR(20) PRIMARY KEY,
    role_name VARCHAR(100) NOT NULL
);

INSERT INTO arsync_roles (role_id, role_name) VALUES
('superadmin', 'Super Admin'),
('head_ar',    'Head AR'),
('admin',      'KA Admin'),
('petugas',    'Petugas')
ON CONFLICT (role_id) DO UPDATE SET role_name = EXCLUDED.role_name;

-- ============================================================
-- 2. USERS (terhubung ke SSO Portal via sso_user_id)
-- ============================================================
CREATE TABLE IF NOT EXISTS arsync_users (
    id          SERIAL PRIMARY KEY,
    sso_user_id VARCHAR(100) UNIQUE,
    username    VARCHAR(100) NOT NULL UNIQUE,
    fullname    VARCHAR(150) NOT NULL,
    role        VARCHAR(20)  NOT NULL DEFAULT 'petugas' REFERENCES arsync_roles(role_id),
    jabatan     VARCHAR(100) NOT NULL DEFAULT '',
    created_at  TIMESTAMP NOT NULL DEFAULT NOW()
);

-- ============================================================
-- 3. BUSINESS AREAS
-- ============================================================
CREATE TABLE IF NOT EXISTS arsync_business_areas (
    id                 SERIAL PRIMARY KEY,
    area_name          VARCHAR(50)  NOT NULL,   -- kode singkat, mis: "MDN1"
    business_area_name VARCHAR(150) NOT NULL    -- nama lengkap, mis: "Medan 1"
);

-- ============================================================
-- 4. SALES OFFICES
-- ============================================================
CREATE TABLE IF NOT EXISTS arsync_sales_offices (
    id                SERIAL PRIMARY KEY,
    office_name       VARCHAR(50)  NOT NULL,   -- kode singkat, mis: "SO01"
    sales_office_name VARCHAR(150) NOT NULL    -- nama lengkap, mis: "Sales Office Medan"
);

-- ============================================================
-- 5. BATCHES
-- ============================================================
CREATE TABLE IF NOT EXISTS arsync_batches (
    id             SERIAL PRIMARY KEY,
    petugas        VARCHAR(150) NOT NULL,
    business_area  VARCHAR(50)  NOT NULL,
    sales_office   VARCHAR(50)  NOT NULL,
    cutoff_date    VARCHAR(20)  NOT NULL,
    excel_data     TEXT         NOT NULL,  -- JSON blob dari file Excel
    excel_filename VARCHAR(255) NOT NULL DEFAULT '',
    is_finalized   SMALLINT     NOT NULL DEFAULT 0,
    created_at     TIMESTAMP    NOT NULL DEFAULT NOW()
);

-- ============================================================
-- 6. SCAN DATA
-- ============================================================
CREATE TABLE IF NOT EXISTS arsync_scan_data (
    id          SERIAL PRIMARY KEY,
    batch_id    INT          NOT NULL REFERENCES arsync_batches(id) ON DELETE CASCADE,
    barcode     VARCHAR(100) NOT NULL,
    status      VARCHAR(30)  NOT NULL DEFAULT 'Unconfirmed',
    scanned_by  VARCHAR(150) NOT NULL DEFAULT '',
    scan_data   TEXT         NOT NULL DEFAULT '{}',  -- JSON detail scan
    updated_at  TIMESTAMP    NOT NULL DEFAULT NOW(),
    UNIQUE (batch_id, barcode)
);

CREATE INDEX IF NOT EXISTS idx_arsync_scan_batch ON arsync_scan_data (batch_id);

-- ============================================================
-- 7. BERITA ACARA
-- ============================================================
CREATE TABLE IF NOT EXISTS arsync_berita_acara (
    id                 SERIAL PRIMARY KEY,
    batch_id           INT           NOT NULL REFERENCES arsync_batches(id) ON DELETE CASCADE,
    nomor_ba           VARCHAR(100)  NOT NULL,
    creation_date      DATE          NOT NULL,
    business_area      VARCHAR(50)   NOT NULL,
    business_area_name VARCHAR(150)  NOT NULL DEFAULT '',
    sales_office       VARCHAR(50)   NOT NULL,
    sales_office_name  VARCHAR(150)  NOT NULL DEFAULT '',
    petugas            VARCHAR(150)  NOT NULL,
    cutoff_date        VARCHAR(20)   NOT NULL,
    system_qty         INT           NOT NULL DEFAULT 0,
    opname_qty         INT           NOT NULL DEFAULT 0,
    difference_qty     INT           NOT NULL DEFAULT 0,
    system_amount      NUMERIC(18,2) NOT NULL DEFAULT 0,
    opname_amount      NUMERIC(18,2) NOT NULL DEFAULT 0,
    difference_amount  NUMERIC(18,2) NOT NULL DEFAULT 0,
    is_finalized       SMALLINT      NOT NULL DEFAULT 1,
    created_at         TIMESTAMP     NOT NULL DEFAULT NOW()
);

-- ============================================================
-- 8. SIGNATURES (TTD Berita Acara)
-- ============================================================
CREATE TABLE IF NOT EXISTS arsync_signatures (
    id       SERIAL PRIMARY KEY,
    position VARCHAR(50)  NOT NULL UNIQUE,  -- 'dibuat_oleh', 'diperiksa_oleh', 'disetujui_oleh'
    name     VARCHAR(150) NOT NULL DEFAULT '',
    title    VARCHAR(150) NOT NULL DEFAULT ''
);

INSERT INTO arsync_signatures (position, name, title) VALUES
('dibuat_oleh',    '', ''),
('diperiksa_oleh', '', ''),
('disetujui_oleh', '', '')
ON CONFLICT (position) DO NOTHING;
