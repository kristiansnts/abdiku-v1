<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Attendance\Models\EmployeeWorkAssignment;
use App\Domain\Attendance\Models\ShiftPolicy;
use App\Domain\Attendance\Models\WorkPattern;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeWorkAssignmentFactory extends Factory
{
    protected $model = EmployeeWorkAssignment::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'shift_policy_id' => ShiftPolicy::factory(),
            'work_pattern_id' => WorkPattern::factory(),
            'effective_from' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'effective_to' => null,
        ];
    }

    /**
     * Currently active assignment (no end date).
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'effective_from' => now()->subMonths(6),
            'effective_to' => null,
        ]);
    }

    /**
     * Expired/historical assignment.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'effective_from' => now()->subYear(),
            'effective_to' => now()->subMonths(3),
        ]);
    }

    /**
     * Future assignment (starts next month).
     */
    public function future(): static
    {
        return $this->state(fn (array $attributes) => [
            'effective_from' => now()->addMonth(),
            'effective_to' => null,
        ]);
    }

    /**
     * Assignment starting from employee's join date.
     */
    public function fromJoinDate(): static
    {
        return $this->state(function (array $attributes) {
            $employee = Employee::find($attributes['employee_id']);

            return [
                'effective_from' => $employee?->join_date ?? now()->subYear(),
                'effective_to' => null,
            ];
        });
    }
}
