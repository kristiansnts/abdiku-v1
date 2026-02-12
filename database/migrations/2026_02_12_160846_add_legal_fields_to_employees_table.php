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
        Schema::table('employees', function (Blueprint $table) {
            $table->string('ptkp_status', 10)->nullable()->comment('TK/0, TK/1, K/0, K/1, etc.');
            $table->string('npwp', 20)->nullable();
            $table->string('nik', 20)->nullable();
            $table->string('bpjs_kesehatan_number', 30)->nullable();
            $table->string('bpjs_ketenagakerjaan_number', 30)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['ptkp_status', 'npwp', 'nik', 'bpjs_kesehatan_number', 'bpjs_ketenagakerjaan_number']);
        });
    }
};
