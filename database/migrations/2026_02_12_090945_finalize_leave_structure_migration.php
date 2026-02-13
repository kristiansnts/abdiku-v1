<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Data Fix: Dijalankan di luar Schema builder untuk memastikan data bersih dulu
        $defaultLeaveTypeId = \Illuminate\Support\Facades\DB::table('leave_types')->value('id');
        
        if ($defaultLeaveTypeId) {
            \Illuminate\Support\Facades\DB::table('leave_requests')
                ->whereNull('leave_type_id')
                ->update(['leave_type_id' => $defaultLeaveTypeId]);
                
            \Illuminate\Support\Facades\DB::table('leave_records')
                ->whereNull('leave_type_id')
                ->update(['leave_type_id' => $defaultLeaveTypeId]);
        }

        Schema::table('leave_requests', function (Blueprint $table) {
            if (Schema::hasColumn('leave_requests', 'leave_type')) {
                $table->dropColumn('leave_type');
            }
        });

        // Paksa change column secara terpisah - Hanya untuk PostgreSQL
        if (\Illuminate\Support\Facades\DB::getDriverName() === 'pgsql') {
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE leave_requests ALTER COLUMN leave_type_id SET NOT NULL');
        } else {
            Schema::table('leave_requests', function (Blueprint $table) {
                $table->unsignedBigInteger('leave_type_id')->nullable(false)->change();
            });
        }

        Schema::table('leave_records', function (Blueprint $table) {
            if (Schema::hasColumn('leave_records', 'leave_type')) {
                $table->dropColumn('leave_type');
            }
        });

        if (\Illuminate\Support\Facades\DB::getDriverName() === 'pgsql') {
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE leave_records ALTER COLUMN leave_type_id SET NOT NULL');
        } else {
            Schema::table('leave_records', function (Blueprint $table) {
                $table->unsignedBigInteger('leave_type_id')->nullable(false)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->enum('leave_type', ['PAID', 'UNPAID', 'SICK_PAID', 'SICK_UNPAID'])->nullable()->after('employee_id');
            $table->unsignedBigInteger('leave_type_id')->nullable()->change();
        });

        Schema::table('leave_records', function (Blueprint $table) {
            $table->enum('leave_type', ['PAID', 'UNPAID', 'SICK_PAID', 'SICK_UNPAID'])->nullable()->after('date');
            $table->unsignedBigInteger('leave_type_id')->nullable()->change();
        });
    }
};
