<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_evidences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_raw_id')
                ->constrained('attendance_raw')
                ->onDelete('cascade');
            $table->enum('type', ['GEOLOCATION', 'DEVICE', 'PHOTO']);
            $table->json('payload');
            $table->datetime('captured_at');
            $table->string('hash')->nullable();
            $table->timestamps();

            $table->index('attendance_raw_id');
            $table->index(['attendance_raw_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_evidences');
    }
};
