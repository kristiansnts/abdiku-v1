<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_raw', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('payroll_rows', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('attendance_overrides', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('attendance_decisions', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('attendance_raw', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('payroll_rows', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('attendance_overrides', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('attendance_decisions', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
