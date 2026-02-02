<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_raw', function (Blueprint $table) {
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED', 'LOCKED'])
                ->default('APPROVED')
                ->after('source');

            $table->foreignId('company_location_id')
                ->nullable()
                ->after('company_id')
                ->constrained('company_locations')
                ->nullOnDelete();

            $table->index(['employee_id', 'date', 'status']);
            $table->index(['company_id', 'status']);
        });

        // Set all existing records to APPROVED
        DB::table('attendance_raw')
            ->whereNull('status')
            ->update(['status' => 'APPROVED']);
    }

    public function down(): void
    {
        Schema::table('attendance_raw', function (Blueprint $table) {
            $table->dropForeign(['company_location_id']);
            $table->dropIndex(['employee_id', 'date', 'status']);
            $table->dropIndex(['company_id', 'status']);
            $table->dropColumn(['status', 'company_location_id']);
        });
    }
};
