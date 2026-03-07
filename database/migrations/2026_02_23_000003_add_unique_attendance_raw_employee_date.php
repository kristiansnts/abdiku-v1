<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_raw', function (Blueprint $table) {
            $table->unique(['employee_id', 'date'], 'attendance_raw_employee_date_unique');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_raw', function (Blueprint $table) {
            $table->dropUnique('attendance_raw_employee_date_unique');
        });
    }
};
