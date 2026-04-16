-- CreateTable: spd_documents
CREATE TABLE "spd_documents" (
    "doc_id" TEXT NOT NULL,
    "title" TEXT NOT NULL,
    "created_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "spd_documents_pkey" PRIMARY KEY ("doc_id")
);

-- CreateTable: spd_audit_log
CREATE TABLE "spd_audit_log" (
    "log_id" SERIAL NOT NULL,
    "doc_id" TEXT NOT NULL,
    "user_id" TEXT NOT NULL,
    "action" VARCHAR(50) NOT NULL,
    "details" TEXT,
    "timestamp" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "spd_audit_log_pkey" PRIMARY KEY ("log_id")
);

-- AddForeignKey
ALTER TABLE "spd_audit_log" ADD CONSTRAINT "spd_audit_log_doc_id_fkey"
    FOREIGN KEY ("doc_id") REFERENCES "spd_documents"("doc_id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "spd_audit_log" ADD CONSTRAINT "spd_audit_log_user_id_fkey"
    FOREIGN KEY ("user_id") REFERENCES "users"("id") ON DELETE RESTRICT ON UPDATE CASCADE;
