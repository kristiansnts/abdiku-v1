<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_deduction_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('code', 50);
            $table->string('name');
            $table->enum('basis_type', ['BASE_SALARY', 'CAPPED_SALARY', 'GROSS_SALARY']);
            $table->decimal('employee_rate', 5, 2)->nullable();
            $table->decimal('employer_rate', 5, 2)->nullable();
            $table->decimal('salary_cap', 12, 2)->nullable();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes with explicit shorter names
            $table->index(['company_id', 'code', 'effective_from'], 'deduction_rules_lookup_idx');
            $table->index(['effective_from', 'effective_to'], 'deduction_rules_dates_idx');
            $table->unique(['company_id', 'code', 'effective_from'], 'deduction_rules_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_deduction_rules');
    }
};
