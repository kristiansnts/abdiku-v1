<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_time_corrections', function (Blueprint $table) {
            $table->id();

            // Target: existing raw record OR employee+date for MISSING
            $table->foreignId('attendance_raw_id')
                ->nullable()
                ->constrained('attendance_raw')
                ->nullOnDelete();
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();
            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->date('date');

            // Corrected times
            $table->datetime('corrected_clock_in')->nullable();
            $table->datetime('corrected_clock_out')->nullable();

            // Source tracking
            $table->enum('source_type', ['EMPLOYEE_REQUEST', 'HR_CORRECTION']);
            $table->unsignedBigInteger('source_id')->nullable();

            // Audit trail
            $table->text('reason');
            $table->foreignId('approved_by')
                ->constrained('users');
            $table->datetime('approved_at');

            // Indexes for performance
            $table->unique(['employee_id', 'date'], 'unique_employee_date_correction');
            $table->index(['company_id', 'date']);
            $table->index('source_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_time_corrections');
    }
};
