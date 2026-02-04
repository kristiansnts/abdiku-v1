<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Attendance\Models\ShiftPolicy;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShiftPolicyFactory extends Factory
{
    protected $model = ShiftPolicy::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => $this->faker->randomElement(['Shift Pagi', 'Shift Siang', 'Shift Normal', 'Shift Flexi']),
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'late_after_minutes' => 15,
            'minimum_work_hours' => 8,
        ];
    }

    /**
     * Early morning shift (06:00 - 14:00)
     */
    public function earlyShift(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Shift Pagi',
            'start_time' => '06:00:00',
            'end_time' => '14:00:00',
            'minimum_work_hours' => 8,
        ]);
    }

    /**
     * Standard office shift (09:00 - 17:00)
     */
    public function standardShift(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Shift Kantor',
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'minimum_work_hours' => 8,
        ]);
    }

    /**
     * Late shift (14:00 - 22:00)
     */
    public function lateShift(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Shift Siang',
            'start_time' => '14:00:00',
            'end_time' => '22:00:00',
            'minimum_work_hours' => 8,
        ]);
    }

    /**
     * 7-hour workday (common Indonesian pattern)
     */
    public function sevenHours(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Shift 7 Jam',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'minimum_work_hours' => 7,
        ]);
    }

    /**
     * Strict lateness policy (5 minutes tolerance)
     */
    public function strictLateness(): static
    {
        return $this->state(fn (array $attributes) => [
            'late_after_minutes' => 5,
        ]);
    }

    /**
     * Flexible lateness policy (30 minutes tolerance)
     */
    public function flexibleLateness(): static
    {
        return $this->state(fn (array $attributes) => [
            'late_after_minutes' => 30,
        ]);
    }
}
