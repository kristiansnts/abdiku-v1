<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_batch_id')->constrained()->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->decimal('gross_amount', 10, 2);
            $table->decimal('deduction_amount', 10, 2);
            $table->decimal('net_amount', 10, 2);
        });
    }
};
