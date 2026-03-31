<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attendance_raw', function (Blueprint $table) {
            // Drop the old unique constraint (employee_id, date)
            $table->dropUnique('attendance_raw_employee_date_unique');
            
            // Add new unique constraint with company_id for proper multi-tenancy
            $table->unique(['company_id', 'employee_id', 'date'], 'attendance_raw_company_employee_date_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_raw', function (Blueprint $table) {
            // Revert back to old constraint
            $table->dropUnique('attendance_raw_company_employee_date_unique');
            $table->unique(['employee_id', 'date'], 'attendance_raw_employee_date_unique');
        });
    }
};
