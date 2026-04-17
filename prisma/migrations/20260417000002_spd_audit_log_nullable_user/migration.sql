-- Make user_id nullable in spd_audit_log so users can be deleted
-- while preserving audit history (user_id set to NULL on delete)

-- 1. Drop existing FK constraint
ALTER TABLE "spd_audit_log" DROP CONSTRAINT "spd_audit_log_user_id_fkey";

-- 2. Make user_id nullable
ALTER TABLE "spd_audit_log" ALTER COLUMN "user_id" DROP NOT NULL;

-- 3. Re-add FK with ON DELETE SET NULL
ALTER TABLE "spd_audit_log" ADD CONSTRAINT "spd_audit_log_user_id_fkey"
    FOREIGN KEY ("user_id") REFERENCES "users"("id") ON DELETE SET NULL ON UPDATE CASCADE;
