-- Migration: ubah PK spd_documents dari doc_id saja → (doc_id, area)
-- Tujuan: barcode yang sama boleh dipakai di DC berbeda

-- Step 1: Drop FK lama dari spd_audit_log ke spd_documents
ALTER TABLE "spd_audit_log" DROP CONSTRAINT IF EXISTS "spd_audit_log_doc_id_fkey";

-- Step 2: Isi area/pt yang NULL sebelum di-NOT NULL-kan
UPDATE "spd_documents" SET area = 'Unknown' WHERE area IS NULL;
UPDATE "spd_documents" SET pt = 'Unknown' WHERE pt IS NULL;

-- Step 3: Jadikan area dan pt NOT NULL
ALTER TABLE "spd_documents" ALTER COLUMN "area" SET NOT NULL;
ALTER TABLE "spd_documents" ALTER COLUMN "pt" SET NOT NULL;

-- Step 4: Drop PK lama (doc_id saja)
ALTER TABLE "spd_documents" DROP CONSTRAINT "spd_documents_pkey";

-- Step 5: Tambah composite PK (doc_id, area)
ALTER TABLE "spd_documents" ADD CONSTRAINT "spd_documents_pkey" PRIMARY KEY ("doc_id", "area");

-- Step 6: Tambah kolom area ke spd_audit_log
ALTER TABLE "spd_audit_log" ADD COLUMN IF NOT EXISTS "area" TEXT;

-- Step 7: Isi area di audit_log dari dokumen terkait
UPDATE "spd_audit_log" al
SET area = (
    SELECT area FROM spd_documents d WHERE d.doc_id = al.doc_id LIMIT 1
)
WHERE al.area IS NULL;
UPDATE "spd_audit_log" SET area = 'Unknown' WHERE area IS NULL;
ALTER TABLE "spd_audit_log" ALTER COLUMN "area" SET NOT NULL;

-- Step 8: Tambah composite FK (doc_id, area)
ALTER TABLE "spd_audit_log" ADD CONSTRAINT "spd_audit_log_doc_id_area_fkey"
    FOREIGN KEY ("doc_id", "area") REFERENCES "spd_documents"("doc_id", "area")
    ON DELETE CASCADE ON UPDATE CASCADE;
