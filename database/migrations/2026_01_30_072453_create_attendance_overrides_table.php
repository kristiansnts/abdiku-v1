<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_decision_id')->constrained()->onDelete('cascade');
            $table->string('old_classification');
            $table->string('new_classification');
            $table->text('reason');
            $table->foreignId('overridden_by')->constrained('users');
            $table->datetime('overridden_at');
        });
    }
};
