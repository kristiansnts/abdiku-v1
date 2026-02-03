<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('timezone', 50)->default('Asia/Jakarta')->after('email');
        });

        // Set existing employees to Asia/Jakarta
        DB::table('employees')->update(['timezone' => 'Asia/Jakarta']);
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('timezone');
        });
    }
};
