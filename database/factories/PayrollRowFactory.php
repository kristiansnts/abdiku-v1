<?php

namespace Database\Factories;

use App\Domain\Payroll\Models\PayrollBatch;
use App\Domain\Payroll\Models\PayrollRow;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class PayrollRowFactory extends Factory
{
    protected $model = PayrollRow::class;

    public function definition(): array
    {
        $grossAmount = $this->faker->numberBetween(5000000, 20000000);
        $deductionAmount = $this->faker->numberBetween(500000, 3000000);
        $netAmount = $grossAmount - $deductionAmount;

        return [
            'payroll_batch_id' => PayrollBatch::factory(),
            'employee_id' => Employee::factory(),
            'gross_amount' => $grossAmount,
            'deduction_amount' => $deductionAmount,
            'net_amount' => $netAmount,
        ];
    }
}