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
            // Keep the old column for now to migrate data
            // $table->dropColumn('leave_type');

            // Add the new foreign key column
            $table->foreignId('leave_type_id')
                ->after('employee_id')
                ->nullable()
                ->constrained('leave_types')
                ->onDelete('restrict');

            // Add index
            $table->index('leave_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            // Drop the foreign key and column
            $table->dropForeign(['leave_type_id']);
            $table->dropColumn('leave_type_id');

            // Restore the old enum column
            $table->enum('leave_type', ['PAID', 'UNPAID', 'SICK_PAID', 'SICK_UNPAID'])
                ->after('employee_id');
        });
    }
};
