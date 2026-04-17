-- Add pt and area columns to spd_documents for multi-tenant data partitioning
ALTER TABLE "spd_documents" ADD COLUMN "pt" TEXT;
ALTER TABLE "spd_documents" ADD COLUMN "area" TEXT;

-- Index for efficient filtering by pt and area
CREATE INDEX "spd_documents_pt_area_idx" ON "spd_documents"("pt", "area");
