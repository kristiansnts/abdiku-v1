<?php

namespace Database\Factories;

use App\Domain\Payroll\Models\EmployeeCompensation;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeCompensationFactory extends Factory
{
    protected $model = EmployeeCompensation::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'base_salary' => $this->faker->randomFloat(2, 3000000, 15000000), // IDR 3M - 15M
            'allowances' => [
                'transport' => $this->faker->randomFloat(2, 200000, 1000000),
                'meal' => $this->faker->randomFloat(2, 300000, 800000),
                'communication' => $this->faker->randomFloat(2, 100000, 500000),
            ],
            'effective_from' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'effective_to' => null, // Active by default
            'notes' => $this->faker->optional()->sentence(),
            'created_by' => User::factory(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'effective_from' => now()->subMonths(6),
            'effective_to' => null,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'effective_from' => now()->subYear(),
            'effective_to' => now()->subMonths(3),
        ]);
    }

    public function withHighSalary(): static
    {
        return $this->state(fn (array $attributes) => [
            'base_salary' => $this->faker->randomFloat(2, 10000000, 25000000), // IDR 10M - 25M
            'allowances' => [
                'transport' => $this->faker->randomFloat(2, 500000, 1500000),
                'meal' => $this->faker->randomFloat(2, 500000, 1200000),
                'communication' => $this->faker->randomFloat(2, 200000, 800000),
                'leadership' => $this->faker->randomFloat(2, 1000000, 3000000),
            ],
        ]);
    }
}