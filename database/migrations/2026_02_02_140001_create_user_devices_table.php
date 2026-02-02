<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('device_id')->comment('Unique device identifier from mobile');
            $table->string('device_name')->nullable()->comment('User-friendly name like "iPhone 14"');
            $table->string('device_model')->nullable();
            $table->string('device_os')->nullable();
            $table->string('app_version')->nullable();
            $table->boolean('is_active')->default(true)->comment('Currently active device for user');
            $table->boolean('is_blocked')->default(false);
            $table->string('block_reason')->nullable();
            $table->foreignId('blocked_by')->nullable()->constrained('users');
            $table->datetime('blocked_at')->nullable();
            $table->datetime('last_login_at')->nullable();
            $table->string('last_ip_address')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'device_id']);
            $table->index(['user_id', 'is_active']);
            $table->index(['device_id']);
            $table->index(['is_blocked']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_devices');
    }
};
