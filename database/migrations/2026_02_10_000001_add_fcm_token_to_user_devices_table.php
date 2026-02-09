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
        Schema::table('user_devices', function (Blueprint $table) {
            $table->string('fcm_token')->nullable()->after('app_version');
            $table->timestamp('fcm_token_updated_at')->nullable()->after('fcm_token');

            $table->index(['fcm_token']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_devices', function (Blueprint $table) {
            $table->dropIndex(['fcm_token']);
            $table->dropColumn(['fcm_token', 'fcm_token_updated_at']);
        });
    }
};
