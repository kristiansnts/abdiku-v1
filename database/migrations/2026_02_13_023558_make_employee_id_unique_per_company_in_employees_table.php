<?php

declare(strict_types=1);

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

        Schema::table('employees', function (Blueprint $table) use ($driver) {
            // 1. Drop the existing global unique index on 'employee_id'
            if ($driver !== 'sqlite') {
                $table->dropUnique(['employee_id']);
            }

            // 2. Add the composite unique index
            $table->unique(['employee_id', 'company_id'], 'employees_employee_id_company_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        Schema::table('employees', function (Blueprint $table) use ($driver) {
            $table->dropUnique('employees_employee_id_company_unique');

            if ($driver !== 'sqlite') {
                $table->unique('employee_id');
            }
        });
    }
};
