<?php

namespace Database\Factories;

use App\Domain\Payroll\Models\PayrollAddition;
use App\Domain\Payroll\Models\PayrollPeriod;
use App\Domain\Payroll\Enums\PayrollAdditionCode;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PayrollAdditionFactory extends Factory
{
    protected $model = PayrollAddition::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'payroll_period_id' => PayrollPeriod::factory(),
            'code' => $this->faker->randomElement(PayrollAdditionCode::cases()),
            'amount' => $this->faker->randomFloat(2, 100000, 5000000),
            'description' => $this->faker->optional()->sentence(),
            'created_by' => User::factory(),
        ];
    }

    public function thr(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => PayrollAdditionCode::THR,
            'amount' => $this->faker->randomFloat(2, 2000000, 8000000),
            'description' => 'Tunjangan Hari Raya',
        ]);
    }

    public function bonus(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => PayrollAdditionCode::BONUS,
            'amount' => $this->faker->randomFloat(2, 500000, 3000000),
            'description' => 'Bonus kinerja',
        ]);
    }

    public function overtime(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => PayrollAdditionCode::OVERTIME,
            'amount' => $this->faker->randomFloat(2, 50000, 500000),
            'description' => 'Lembur',
        ]);
    }
}