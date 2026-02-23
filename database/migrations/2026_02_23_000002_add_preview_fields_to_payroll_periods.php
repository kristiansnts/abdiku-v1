<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_periods', function (Blueprint $table) {
            $table->foreignId('previewed_by')->nullable()->after('reviewed_at')->constrained('users');
            $table->timestamp('previewed_at')->nullable()->after('previewed_by');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_periods', function (Blueprint $table) {
            $table->dropConstrainedForeignId('previewed_by');
            $table->dropColumn('previewed_at');
        });
    }
};
