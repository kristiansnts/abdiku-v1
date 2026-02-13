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
        Schema::table('leave_requests', function (Blueprint $table) {
            // Drop it only if it exists
            if (Schema::hasColumn('leave_requests', 'leave_type')) {
                $table->dropColumn('leave_type');
            }

            // Make the new column NOT NULL
            $table->unsignedBigInteger('leave_type_id')->nullable(false)->change();
        });

        Schema::table('leave_records', function (Blueprint $table) {
            // Drop it only if it exists
            if (Schema::hasColumn('leave_records', 'leave_type')) {
                $table->dropColumn('leave_type');
            }

            // Make the new column NOT NULL
            $table->unsignedBigInteger('leave_type_id')->nullable(false)->change();
        });
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
