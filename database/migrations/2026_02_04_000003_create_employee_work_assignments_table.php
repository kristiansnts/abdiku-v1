<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_work_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('shift_policy_id')->constrained('shift_policies')->onDelete('restrict');
            $table->foreignId('work_pattern_id')->constrained('work_patterns')->onDelete('restrict');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->timestamps();

            $table->index(
                ['employee_id', 'effective_from', 'effective_to'],
                'emp_work_assign_employee_dates_idx'
            );
            $table->index('effective_from', 'emp_work_assign_effective_from_idx');
            $table->index('shift_policy_id', 'emp_work_assign_shift_policy_idx');
            $table->index('work_pattern_id', 'emp_work_assign_work_pattern_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_work_assignments');
    }
};
