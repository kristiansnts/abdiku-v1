<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Attendance\Enums\DayOfWeek;
use App\Domain\Attendance\Models\WorkPattern;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkPatternFactory extends Factory
{
    protected $model = WorkPattern::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => '5 Hari Kerja',
            'working_days' => DayOfWeek::fiveDayPattern(),
        ];
    }

    /**
     * 5-day work pattern (Monday - Friday)
     */
    public function fiveDay(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => '5 Hari Kerja',
            'working_days' => DayOfWeek::fiveDayPattern(),
        ]);
    }

    /**
     * 6-day work pattern (Monday - Saturday)
     */
    public function sixDay(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => '6 Hari Kerja',
            'working_days' => DayOfWeek::sixDayPattern(),
        ]);
    }

    /**
     * All days work pattern (Monday - Sunday)
     */
    public function allDays(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Semua Hari',
            'working_days' => DayOfWeek::allDaysPattern(),
        ]);
    }

    /**
     * Custom pattern with specific days.
     */
    public function withDays(array $days): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Pola Kustom',
            'working_days' => $days,
        ]);
    }
}
