<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        Schema::table('users', function (Blueprint $table) use ($driver) {
            if ($driver === 'pgsql') {
                $constraintExists = collect(DB::select(
                    "SELECT constraint_name FROM information_schema.table_constraints
                     WHERE table_name = 'users' AND constraint_name = 'users_email_unique'"
                ))->isNotEmpty();
                if ($constraintExists) {
                    $table->dropUnique(['email']);
                }
            } elseif ($driver !== 'sqlite') {
                // MySQL and others — attempt drop, ignore if missing
                try {
                    $table->dropUnique(['email']);
                } catch (\Exception $e) {
                    // Constraint didn't exist, safe to continue
                }
            }

            $indexName = 'users_email_company_unique';
            
            // Determine if index exists using driver-specific logic
            $indexExists = false;
            if ($driver === 'sqlite') {
                $indexExists = collect(DB::select("SELECT name FROM sqlite_master WHERE type='index' AND name='{$indexName}'"))->isNotEmpty();
            } elseif ($driver === 'pgsql') {
                $indexExists = collect(DB::select("SELECT indexname FROM pg_indexes WHERE indexname = ?", [$indexName]))->isNotEmpty();
            } else {
                // MySQL/Others - Default to letting Laravel try to handle it or assuming false
                $indexExists = false; 
            }

            if (!$indexExists) {
                $table->unique(['email', 'company_id'], $indexName);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        Schema::table('users', function (Blueprint $table) use ($driver) {
            $table->dropUnique('users_email_company_unique');

            if ($driver !== 'sqlite') {
                $table->unique('email');
            }
        });
    }
};
