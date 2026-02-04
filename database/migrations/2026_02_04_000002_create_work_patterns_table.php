<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_patterns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->json('working_days');
            $table->timestamps();

            $table->index(['company_id', 'name'], 'work_patterns_company_name_idx');
            $table->index('company_id', 'work_patterns_company_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_patterns');
    }
};
