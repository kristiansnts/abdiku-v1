<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('override_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_decision_id')->constrained()->onDelete('cascade');
            $table->enum('old_classification', ['ATTEND', 'LATE', 'ABSENT', 'PAID_LEAVE', 'UNPAID_LEAVE', 'HOLIDAY', 'PAID_SICK', 'UNPAID_SICK']);
            $table->enum('proposed_classification', ['ATTEND', 'LATE', 'ABSENT', 'PAID_LEAVE', 'UNPAID_LEAVE', 'HOLIDAY', 'PAID_SICK', 'UNPAID_SICK']);
            $table->text('reason');
            $table->foreignId('requested_by')->constrained('users');
            $table->datetime('requested_at');
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING');
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->datetime('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
        });
    }
};
