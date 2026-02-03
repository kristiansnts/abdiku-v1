<?php

namespace Database\Factories;

use App\Domain\Payroll\Models\PayrollBatch;
use App\Domain\Payroll\Models\PayrollPeriod;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PayrollBatchFactory extends Factory
{
    protected $model = PayrollBatch::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'payroll_period_id' => PayrollPeriod::factory(),
            'total_amount' => $this->faker->numberBetween(50000000, 500000000),
            'finalized_by' => User::factory(),
            'finalized_at' => $this->faker->dateTimeBetween('-1 month', 'now'), // Default to finalized
        ];
    }

    public function finalized(): static
    {
        return $this->state(fn (array $attributes) => [
            'finalized_by' => User::factory(),
            'finalized_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'finalized_at' => null, // Not finalized
        ]);
    }
}