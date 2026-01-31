<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_additions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('payroll_period_id')->constrained()->onDelete('cascade');
            $table->string('code', 50);
            $table->decimal('amount', 12, 2);
            $table->text('description')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            // Indexes with explicit shorter names
            $table->index(['payroll_period_id', 'employee_id'], 'additions_period_employee_idx');
            $table->index('code', 'additions_code_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_additions');
    }
};
