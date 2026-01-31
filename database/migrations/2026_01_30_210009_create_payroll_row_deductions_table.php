<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_row_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_row_id')->constrained('payroll_rows')->onDelete('cascade');
            $table->string('deduction_code', 50);
            $table->decimal('employee_amount', 12, 2);
            $table->decimal('employer_amount', 12, 2)->default(0);
            $table->json('rule_snapshot');
            $table->timestamps();

            // Indexes with explicit shorter names
            $table->index('payroll_row_id', 'row_deductions_row_idx');
            $table->index('deduction_code', 'row_deductions_code_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_row_deductions');
    }
};
