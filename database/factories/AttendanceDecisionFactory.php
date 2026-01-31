<?php

namespace Database\Factories;

use App\Domain\Attendance\Models\AttendanceDecision;
use App\Domain\Attendance\Enums\AttendanceClassification;
use App\Domain\Payroll\Enums\DeductionType;
use App\Domain\Payroll\Models\PayrollPeriod;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceDecisionFactory extends Factory
{
    protected $model = AttendanceDecision::class;

    public function definition(): array
    {
        return [
            'payroll_period_id' => PayrollPeriod::factory(),
            'employee_id' => Employee::factory(),
            'date' => $this->faker->dateTimeThisMonth(),
            'classification' => $this->faker->randomElement(AttendanceClassification::cases()),
            'payable' => $this->faker->boolean(80), // 80% chance of being payable
            'deduction_type' => $this->faker->randomElement(DeductionType::cases()),
            'deduction_value' => $this->faker->optional(0.3)->randomFloat(2, 0, 100), // 30% chance of having deduction
            'rule_version' => '1.0',
            'decided_at' => now(),
        ];
    }

    public function attend(): static
    {
        return $this->state(fn (array $attributes) => [
            'classification' => AttendanceClassification::ATTEND,
            'payable' => true,
            'deduction_type' => null,
            'deduction_value' => null,
        ]);
    }

    public function absent(): static
    {
        return $this->state(fn (array $attributes) => [
            'classification' => AttendanceClassification::ABSENT,
            'payable' => false,
            'deduction_type' => DeductionType::FULL_DAY,
            'deduction_value' => 100.00,
        ]);
    }

    public function late(): static
    {
        return $this->state(fn (array $attributes) => [
            'classification' => AttendanceClassification::LATE,
            'payable' => true,
            'deduction_type' => DeductionType::HOURLY,
            'deduction_value' => $this->faker->randomFloat(2, 10, 50),
        ]);
    }
}