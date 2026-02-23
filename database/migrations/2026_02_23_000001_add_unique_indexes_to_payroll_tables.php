<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_batches', function (Blueprint $table) {
            $table->unique('payroll_period_id', 'payroll_batches_period_unique');
        });

        Schema::table('payroll_rows', function (Blueprint $table) {
            $table->unique(['payroll_batch_id', 'employee_id'], 'payroll_rows_batch_employee_unique');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_rows', function (Blueprint $table) {
            $table->dropUnique('payroll_rows_batch_employee_unique');
        });

        Schema::table('payroll_batches', function (Blueprint $table) {
            $table->dropUnique('payroll_batches_period_unique');
        });
    }
};
