<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // For PostgreSQL: Update enum type
        if (DB::getDriverName() === 'pgsql') {
            // First update any existing HOLIDAY to HOLIDAY_PAID
            DB::statement("UPDATE attendance_decisions SET classification = 'HOLIDAY_PAID' WHERE classification = 'HOLIDAY'");

            // Alter the column to use string temporarily, then add check constraint
            DB::statement("ALTER TABLE attendance_decisions ALTER COLUMN classification TYPE VARCHAR(50)");
            DB::statement("ALTER TABLE attendance_decisions DROP CONSTRAINT IF EXISTS attendance_decisions_classification_check");
            DB::statement("ALTER TABLE attendance_decisions ADD CONSTRAINT attendance_decisions_classification_check CHECK (classification IN ('ATTEND', 'LATE', 'ABSENT', 'PAID_LEAVE', 'UNPAID_LEAVE', 'HOLIDAY_PAID', 'HOLIDAY_UNPAID', 'PAID_SICK', 'UNPAID_SICK'))");
        }

        // For SQLite (testing): Recreate table with new enum
        if (DB::getDriverName() === 'sqlite') {
            // SQLite doesn't support ALTER COLUMN, so we need to handle it differently
            // The test database is recreated fresh each time, so we just need to ensure
            // the migration file is correct for fresh installs
        }

        // For MySQL: Modify enum
        if (DB::getDriverName() === 'mysql') {
            DB::statement("UPDATE attendance_decisions SET classification = 'HOLIDAY_PAID' WHERE classification = 'HOLIDAY'");
            DB::statement("ALTER TABLE attendance_decisions MODIFY COLUMN classification ENUM('ATTEND', 'LATE', 'ABSENT', 'PAID_LEAVE', 'UNPAID_LEAVE', 'HOLIDAY_PAID', 'HOLIDAY_UNPAID', 'PAID_SICK', 'UNPAID_SICK')");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("UPDATE attendance_decisions SET classification = 'HOLIDAY' WHERE classification IN ('HOLIDAY_PAID', 'HOLIDAY_UNPAID')");
            DB::statement("ALTER TABLE attendance_decisions DROP CONSTRAINT IF EXISTS attendance_decisions_classification_check");
            DB::statement("ALTER TABLE attendance_decisions ADD CONSTRAINT attendance_decisions_classification_check CHECK (classification IN ('ATTEND', 'LATE', 'ABSENT', 'PAID_LEAVE', 'UNPAID_LEAVE', 'HOLIDAY', 'PAID_SICK', 'UNPAID_SICK'))");
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("UPDATE attendance_decisions SET classification = 'HOLIDAY' WHERE classification IN ('HOLIDAY_PAID', 'HOLIDAY_UNPAID')");
            DB::statement("ALTER TABLE attendance_decisions MODIFY COLUMN classification ENUM('ATTEND', 'LATE', 'ABSENT', 'PAID_LEAVE', 'UNPAID_LEAVE', 'HOLIDAY', 'PAID_SICK', 'UNPAID_SICK')");
        }
    }
};
