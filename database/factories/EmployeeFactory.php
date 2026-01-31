<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'user_id' => User::factory(),
            'name' => $this->faker->name,
            'join_date' => $this->faker->dateTimeBetween('-2 years', '-1 month'),
            'resign_date' => null,
            'status' => 'ACTIVE',
        ];
    }

    public function resigned(): static
    {
        return $this->state(fn (array $attributes) => [
            'resign_date' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'status' => 'RESIGNED',
        ]);
    }

    public function withTenure(int $years): static
    {
        return $this->state(fn (array $attributes) => [
            'join_date' => now()->subYears($years)->subMonths(rand(1, 11)),
        ]);
    }
}