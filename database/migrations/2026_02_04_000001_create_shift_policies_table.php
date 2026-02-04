<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedTinyInteger('late_after_minutes')->default(15);
            $table->unsignedTinyInteger('minimum_work_hours')->default(8);
            $table->timestamps();

            $table->index(['company_id', 'name'], 'shift_policies_company_name_idx');
            $table->index('company_id', 'shift_policies_company_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_policies');
    }
};
