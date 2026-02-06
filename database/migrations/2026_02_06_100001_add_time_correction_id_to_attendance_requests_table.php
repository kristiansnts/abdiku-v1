<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_requests', function (Blueprint $table) {
            $table->foreignId('time_correction_id')
                ->nullable()
                ->after('attendance_raw_id')
                ->constrained('attendance_time_corrections')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('attendance_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('time_correction_id');
        });
    }
};
