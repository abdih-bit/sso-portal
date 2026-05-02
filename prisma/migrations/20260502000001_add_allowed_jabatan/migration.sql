-- AlterTable
ALTER TABLE "applications" ADD COLUMN     "allowed_jabatan" TEXT[] DEFAULT ARRAY[]::TEXT[];
