<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Payroll\Policies;

use App\Domain\Payroll\Policies\ThrCalculationPolicy;
use App\Domain\Payroll\ValueObjects\EmployeeTenure;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class ThrCalculationPolicyTest extends TestCase
{
    private ThrCalculationPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new ThrCalculationPolicy();
    }

    public function test_calculate_permanent_employee_full_year(): void
    {
        $baseSalary = 5000000;
        $tenure = $this->createTenure(12); // 12 months

        $result = $this->policy->calculatePermanentEmployee($baseSalary, $tenure);

        $this->assertEquals($baseSalary, $result);
    }

    public function test_calculate_permanent_employee_partial_year(): void
    {
        $baseSalary = 6000000;
        $tenure = $this->createTenure(6); // 6 months

        $result = $this->policy->calculatePermanentEmployee($baseSalary, $tenure);

        $this->assertEquals(3000000, $result); // 6/12 * 6000000
    }

    public function test_calculate_contract_employee(): void
    {
        $baseSalary = 4000000;
        $tenure = $this->createTenure(8); // 8 months

        $result = $this->policy->calculateContractEmployee($baseSalary, $tenure);

        $expected = (8 / 12) * $baseSalary;
        $this->assertEquals($expected, $result);
    }

    public function test_calculate_daily_employee(): void
    {
        $monthlySalary = 3000000;
        $tenure = $this->createTenure(12);

        $result = $this->policy->calculateDailyEmployee($monthlySalary, $tenure);

        $this->assertEquals($monthlySalary, $result);
    }

    public function test_calculate_daily_employee_with_zero_days(): void
    {
        $monthlySalary = 3000000;
        $tenure = $this->createTenure(0);

        $result = $this->policy->calculateDailyEmployee($monthlySalary, $tenure);

        $this->assertEquals(0, $result);
    }

    public function test_validate_employee_type(): void
    {
        $this->assertTrue($this->policy->isValidEmployeeType('permanent'));
        $this->assertTrue($this->policy->isValidEmployeeType('contract'));
        $this->assertTrue($this->policy->isValidEmployeeType('daily'));
        $this->assertTrue($this->policy->isValidEmployeeType('freelance'));
        $this->assertFalse($this->policy->isValidEmployeeType('invalid'));
    }

    public function test_get_calculation_method(): void
    {
        $fullYearTenure = $this->createTenure(12);
        $partialTenure = $this->createTenure(6);
        $shortTenure = $this->createTenure(0);

        $this->assertEquals('permanent_full', $this->policy->getCalculationMethod('permanent', $fullYearTenure));
        $this->assertEquals('permanent_prorated', $this->policy->getCalculationMethod('permanent', $partialTenure));
        $this->assertEquals('contract_prorated', $this->policy->getCalculationMethod('contract', $partialTenure));
        $this->assertEquals('daily_prorated', $this->policy->getCalculationMethod('daily', $partialTenure));
        $this->assertEquals('ineligible', $this->policy->getCalculationMethod('permanent', $shortTenure));
    }

    public function test_generate_calculation_notes_permanent_full(): void
    {
        $tenure = $this->createTenure(12);
        $baseSalary = 5000000;
        $thrAmount = 5000000;

        $notes = $this->policy->generateCalculationNotes('permanent', $tenure, $baseSalary, $thrAmount);

        $this->assertStringContainsString('Karyawan Tetap', $notes);
        $this->assertStringContainsString('THR penuh', $notes);
        $this->assertStringContainsString('12.0 bulan', $notes);
    }

    public function test_generate_calculation_notes_ineligible(): void
    {
        $tenure = $this->createTenure(0);
        $baseSalary = 5000000;
        $thrAmount = 0;

        $notes = $this->policy->generateCalculationNotes('permanent', $tenure, $baseSalary, $thrAmount);

        $this->assertStringContainsString('Tidak berhak THR', $notes);
        $this->assertStringContainsString('kurang dari 1 bulan', $notes);
    }

    private function createTenure(float $months): EmployeeTenure
    {
        $startDate = Carbon::parse('2023-01-01');
        $endDate = $startDate->copy()->addMonths((int) $months);
        
        return new EmployeeTenure(
            startDate: $startDate,
            endDate: $endDate,
            monthsWorked: $months,
            daysWorked: (int) ($months * 30), // Approximate
            isResigned: false
        );
    }
}