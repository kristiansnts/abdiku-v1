<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Drop the existing uppercase check constraint
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');

        // Step 2: Update any existing uppercase values to lowercase
        DB::statement("UPDATE users SET role = LOWER(role) WHERE role != LOWER(role)");

        // Step 3: Add new lowercase check constraint
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('employee', 'hr', 'owner'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
        DB::statement("UPDATE users SET role = UPPER(role) WHERE role != UPPER(role)");
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('EMPLOYEE', 'HR', 'OWNER'))");
    }
};
