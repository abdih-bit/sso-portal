-- CreateTable pt_list
CREATE TABLE IF NOT EXISTS "pt_list" (
    "id" SERIAL NOT NULL,
    "name" VARCHAR(100) NOT NULL,
    "created_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "pt_list_pkey" PRIMARY KEY ("id")
);

CREATE UNIQUE INDEX IF NOT EXISTS "pt_list_name_key" ON "pt_list"("name");

-- CreateTable departemen_list
CREATE TABLE IF NOT EXISTS "departemen_list" (
    "id" SERIAL NOT NULL,
    "name" VARCHAR(100) NOT NULL,
    "created_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "departemen_list_pkey" PRIMARY KEY ("id")
);

CREATE UNIQUE INDEX IF NOT EXISTS "departemen_list_name_key" ON "departemen_list"("name");

-- CreateTable jabatan_list
CREATE TABLE IF NOT EXISTS "jabatan_list" (
    "id" SERIAL NOT NULL,
    "name" VARCHAR(100) NOT NULL,
    "departemen" VARCHAR(100),
    "role" VARCHAR(20),
    "created_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "jabatan_list_pkey" PRIMARY KEY ("id")
);

CREATE UNIQUE INDEX IF NOT EXISTS "jabatan_list_name_key" ON "jabatan_list"("name");

-- Seed default data PT
INSERT INTO "pt_list" ("name") VALUES
    ('MDR 1'), ('MDR 2'), ('MDR 3'), ('MDR 4')
ON CONFLICT (name) DO NOTHING;

-- Seed default data Departemen
INSERT INTO "departemen_list" ("name") VALUES
    ('FAT'), ('TRP'), ('WRH')
ON CONFLICT (name) DO NOTHING;

-- Seed default data Jabatan
INSERT INTO "jabatan_list" ("name", "departemen", "role") VALUES
    ('Head ACC', 'FAT', 'ADMIN'),
    ('Head AR', 'FAT', 'ADMIN'),
    ('KA Admin', 'FAT', 'ADMIN'),
    ('Admin FAT', 'FAT', 'USER'),
    ('KA Transport', 'TRP', 'ADMIN'),
    ('Admin Transport', 'TRP', 'USER'),
    ('KA WRH FG', 'WRH', 'ADMIN'),
    ('KA WRH BS', 'WRH', 'ADMIN'),
    ('Admin FG', 'WRH', 'USER'),
    ('Admin BS', 'WRH', 'USER'),
    ('IT', NULL, NULL)
ON CONFLICT (name) DO NOTHING;
