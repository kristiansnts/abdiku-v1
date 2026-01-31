<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_period_id')->constrained()->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->date('date');
            $table->enum('classification', ['ATTEND', 'LATE', 'ABSENT', 'PAID_LEAVE', 'UNPAID_LEAVE', 'HOLIDAY', 'PAID_SICK', 'UNPAID_SICK']);
            $table->boolean('payable');
            $table->enum('deduction_type', ['NONE', 'FULL', 'PERCENTAGE']);
            $table->decimal('deduction_value', 8, 2)->nullable();
            $table->string('rule_version');
            $table->datetime('decided_at');
        });
    }
};
