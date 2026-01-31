<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_compensations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->decimal('base_salary', 12, 2);
            $table->json('allowances')->nullable();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            // Indexes with explicit shorter names
            $table->index(['employee_id', 'effective_from', 'effective_to'], 'emp_comp_employee_dates_idx');
            $table->index('effective_from', 'emp_comp_effective_from_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_compensations');
    }
};
