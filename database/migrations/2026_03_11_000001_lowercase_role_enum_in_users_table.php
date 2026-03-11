<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite doesn't support CHECK constraints via ALTER TABLE — skip on test env
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
        DB::statement("UPDATE users SET role = LOWER(role) WHERE role != LOWER(role)");
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('employee', 'hr', 'owner'))");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
        DB::statement("UPDATE users SET role = UPPER(role) WHERE role != UPPER(role)");
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('EMPLOYEE', 'HR', 'OWNER'))");
    }
};
