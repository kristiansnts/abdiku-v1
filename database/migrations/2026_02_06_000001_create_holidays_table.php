<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->string('name');
            $table->boolean('is_paid')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'date'], 'holidays_company_date_unique');
            $table->index('date', 'holidays_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
