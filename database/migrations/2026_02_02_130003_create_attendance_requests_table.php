<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->onDelete('cascade');
            $table->foreignId('company_id')
                ->constrained()
                ->onDelete('cascade');
            $table->foreignId('attendance_raw_id')
                ->nullable()
                ->constrained('attendance_raw')
                ->onDelete('set null');
            $table->enum('request_type', ['LATE', 'CORRECTION', 'MISSING']);
            $table->datetime('requested_clock_in_at')->nullable();
            $table->datetime('requested_clock_out_at')->nullable();
            $table->text('reason');
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING');
            $table->datetime('requested_at');
            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users');
            $table->datetime('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'status']);
            $table->index(['company_id', 'status']);
            $table->index('requested_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_requests');
    }
};
