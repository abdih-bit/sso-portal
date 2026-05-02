-- CreateTable
CREATE TABLE IF NOT EXISTS "areas" (
    "id" SERIAL NOT NULL,
    "name" VARCHAR(255) NOT NULL,
    "pt" VARCHAR(100),
    "is_ho" BOOLEAN NOT NULL DEFAULT false,
    "created_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "areas_pkey" PRIMARY KEY ("id")
);

-- CreateIndex
CREATE UNIQUE INDEX IF NOT EXISTS "areas_name_key" ON "areas"("name");

-- CreateIndex
CREATE INDEX IF NOT EXISTS "areas_pt_idx" ON "areas"("pt");
