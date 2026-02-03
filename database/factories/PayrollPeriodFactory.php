<?php

namespace Database\Factories;

use App\Domain\Payroll\Models\PayrollPeriod;
use App\Domain\Payroll\Enums\PayrollState;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class PayrollPeriodFactory extends Factory
{
    protected $model = PayrollPeriod::class;

    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('-3 months', '-1 month');
        $end = clone $start;
        $end->modify('last day of this month');

        return [
            'company_id' => Company::factory(),
            'period_start' => $start,
            'period_end' => $end,
            'year' => (int) $start->format('Y'),
            'month' => (int) $start->format('n'),
            'state' => PayrollState::DRAFT,
            'rule_version' => '1.0',
            'reviewed_at' => null,
            'finalized_by' => null,
            'finalized_at' => null,
        ];
    }

    public function inReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'state' => PayrollState::REVIEW,
        ]);
    }

    public function finalized(): static
    {
        return $this->state(fn (array $attributes) => [
            'state' => PayrollState::FINALIZED,
            'finalized_by' => \App\Models\User::factory(),
            'finalized_at' => now(),
        ]);
    }

    public function currentMonth(): static
    {
        return $this->state(fn (array $attributes) => [
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'year' => now()->year,
            'month' => now()->month,
        ]);
    }
}