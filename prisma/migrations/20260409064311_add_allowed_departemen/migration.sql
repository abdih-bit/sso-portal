-- AlterTable
ALTER TABLE "applications" ADD COLUMN     "allowed_departemen" TEXT[] DEFAULT ARRAY[]::TEXT[];
