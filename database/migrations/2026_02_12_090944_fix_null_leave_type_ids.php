<?php

namespace Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix null values before the main migration attempts to set NOT NULL
        DB::statement("UPDATE leave_records SET leave_type_id = (SELECT id FROM leave_types LIMIT 1) WHERE leave_type_id IS NULL AND EXISTS (SELECT 1 FROM leave_types)");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
