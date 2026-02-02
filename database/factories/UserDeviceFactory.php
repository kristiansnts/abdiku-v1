<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserDeviceFactory extends Factory
{
    protected $model = UserDevice::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'device_id' => Str::uuid()->toString(),
            'device_name' => $this->faker->randomElement(['iPhone 14 Pro', 'Samsung Galaxy S23', 'Pixel 8']),
            'device_model' => $this->faker->randomElement(['iPhone14,3', 'SM-S911B', 'Pixel 8']),
            'device_os' => $this->faker->randomElement(['iOS 17', 'Android 14', 'Android 13']),
            'app_version' => '1.0.0',
            'is_active' => true,
            'is_blocked' => false,
            'last_login_at' => now(),
            'last_ip_address' => $this->faker->ipv4,
        ];
    }

    public function blocked(string $reason = 'Test block'): static
    {
        return $this->state(fn (array $attributes) => [
            'is_blocked' => true,
            'is_active' => false,
            'block_reason' => $reason,
            'blocked_at' => now(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
