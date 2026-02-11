<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Clear existing tokens since they don't have company_id
        // Users will need to request new invitation links
        DB::table('password_reset_tokens')->truncate();

        // Drop the existing primary key
        Schema::table('password_reset_tokens', function (Blueprint $table) {
            $table->dropPrimary();
        });

        // Add company_id and create composite primary key
        Schema::table('password_reset_tokens', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->after('email');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');

            // Create composite primary key on email and company_id
            $table->primary(['email', 'company_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('password_reset_tokens', function (Blueprint $table) {
            $table->dropPrimary(['email', 'company_id']);
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });

        // Restore original primary key on email
        Schema::table('password_reset_tokens', function (Blueprint $table) {
            $table->primary('email');
        });
    }
};
