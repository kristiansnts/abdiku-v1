<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Payroll\Services;

use App\Domain\Payroll\Services\TaxCalculationService;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxCalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @dataProvider terRateProvider
     */
    public function test_calculate_monthly_tax_rate_and_amount(string $ptkpStatus, float $gross, float $expectedRate): void
    {
        $employee = Employee::factory()->create([
            'ptkp_status' => $ptkpStatus,
        ]);

        $service = app(TaxCalculationService::class);
        $result = $service->calculateMonthlyTax($employee, $gross);

        $this->assertEqualsWithDelta($expectedRate / 100, $result['rate'], 0.000001);
        $this->assertEquals((float) round($gross * ($expectedRate / 100)), $result['tax_amount']);
    }

    public static function terRateProvider(): array
    {
        return [
            // TER A (TK/0)
            ['TK/0', 5400000, 0.00],
            ['TK/0', 5650000, 0.25],
            ['TK/0', 1400000000, 33.00],

            // TER B (TK/2)
            ['TK/2', 6200000, 0.00],
            ['TK/2', 6500000, 0.25],

            // TER C (K/3)
            ['K/3', 6600000, 0.00],
            ['K/3', 6950000, 0.25],

            // Above highest bracket should return 34%
            ['TK/0', 1400000001, 34.00],
        ];
    }
}
