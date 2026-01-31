<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_row_additions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_row_id')->constrained('payroll_rows')->onDelete('cascade');
            $table->string('addition_code', 50);
            $table->decimal('amount', 12, 2);
            $table->foreignId('source_reference')->nullable()->constrained('payroll_additions')->onDelete('set null');
            $table->text('description')->nullable();
            $table->timestamps();

            // Indexes with explicit shorter names
            $table->index('payroll_row_id', 'row_additions_row_idx');
            $table->index('addition_code', 'row_additions_code_idx');
            $table->index('source_reference', 'row_additions_source_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_row_additions');
    }
};
