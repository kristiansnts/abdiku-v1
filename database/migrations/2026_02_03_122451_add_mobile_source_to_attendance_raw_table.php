<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the existing check constraint
        DB::statement('ALTER TABLE attendance_raw DROP CONSTRAINT IF EXISTS attendance_raw_source_check');

        // Add new check constraint with MOBILE included
        DB::statement("ALTER TABLE attendance_raw ADD CONSTRAINT attendance_raw_source_check CHECK (source IN ('MACHINE', 'REQUEST', 'IMPORT', 'MOBILE'))");
    }

    public function down(): void
    {
        // Drop the new check constraint
        DB::statement('ALTER TABLE attendance_raw DROP CONSTRAINT IF EXISTS attendance_raw_source_check');

        // Restore old check constraint without MOBILE
        DB::statement("ALTER TABLE attendance_raw ADD CONSTRAINT attendance_raw_source_check CHECK (source IN ('MACHINE', 'REQUEST', 'IMPORT'))");
    }
};
