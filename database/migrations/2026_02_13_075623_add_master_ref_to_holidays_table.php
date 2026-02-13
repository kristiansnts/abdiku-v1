<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('holidays', function (Blueprint $table) {
            $table->foreignId('master_holiday_id')->nullable()->constrained('master_holidays')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('holidays', function (Blueprint $table) {
            $table->dropConstrainedForeignId('master_holiday_id');
        });
    }
};
