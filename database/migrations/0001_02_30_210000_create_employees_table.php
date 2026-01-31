<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('name');
            $table->date('join_date');
            $table->date('resign_date')->nullable();
            $table->enum('status', ['ACTIVE', 'INACTIVE', 'RESIGNED'])->default('ACTIVE');
            $table->timestamps();

            // Indexes
            $table->index(['company_id', 'status'], 'employees_company_status_idx');
            $table->index('user_id', 'employees_user_idx');
            $table->index('status', 'employees_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
