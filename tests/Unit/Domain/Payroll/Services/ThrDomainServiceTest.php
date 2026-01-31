<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Payroll\Services;

use App\Domain\Payroll\Policies\ThrCalculationPolicy;
use App\Domain\Payroll\Policies\ThrEligibilityPolicy;
use App\Domain\Payroll\Services\ThrDomainService;
use App\Domain\Payroll\ValueObjects\ThrCalculationResult;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class ThrDomainServiceTest extends TestCase
{
    private ThrDomainService $service;
    private ThrCalculationPolicy $calculationPolicy;
    private ThrEligibilityPolicy $eligibilityPolicy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculationPolicy = new ThrCalculationPolicy();
        $this->eligibilityPolicy = new ThrEligibilityPolicy();
        $this->service = new ThrDomainService($this->calculationPolicy, $this->eligibilityPolicy);
    }

    public function test_calculate_thr_for_eligible_permanent_employee(): void
    {
        $joinDate = Carbon::parse('2023-01-01');
        $calculationDate = Carbon::parse('2024-01-01');
        $baseSalary = 5000000;

        $result = $this->service->calculateThr(
            $joinDate,
            null,
            $calculationDate,
            $baseSalary,
            'permanent'
        );

        $this->assertInstanceOf(ThrCalculationResult::class, $result);
        $this->assertTrue($result->isEligible());
        $this->assertEquals($baseSalary, $result->thrAmount);
        $this->assertEquals('permanent_full', $result->calculationMethod);
    }

    public function test_calculate_thr_for_ineligible_employee(): void
    {
        $joinDate = Carbon::parse('2023-12-01');
        $calculationDate = Carbon::parse('2023-12-15'); // Only 2 weeks
        $baseSalary = 5000000;

        $result = $this->service->calculateThr(
            $joinDate,
            null,
            $calculationDate,
            $baseSalary,
            'permanent'
        );

        $this->assertFalse($result->isEligible());
        $this->assertEquals(0, $result->thrAmount);
        $this->assertStringContainsString('Tidak berhak THR', $result->notes);
    }

    public function test_calculate_thr_for_resigned_employee(): void
    {
        $joinDate = Carbon::parse('2023-01-01');
        $resignDate = Carbon::parse('2023-06-01');
        $calculationDate = Carbon::parse('2024-01-01');
        $baseSalary = 6000000;

        $result = $this->service->calculateThr(
            $joinDate,
            $resignDate,
            $calculationDate,
            $baseSalary,
            'permanent'
        );

        $this->assertTrue($result->isEligible());
        $this->assertEquals($baseSalary * 0.5, $result->thrAmount); // 6 months / 12
        $this->assertStringContainsString('mengundurkan diri', $result->notes);
    }

    public function test_calculate_thr_for_contract_employee(): void
    {
        $joinDate = Carbon::parse('2023-03-01');
        $calculationDate = Carbon::parse('2023-11-01'); // 8 months
        $baseSalary = 4000000;

        $result = $this->service->calculateThr(
            $joinDate,
            null,
            $calculationDate,
            $baseSalary,
            'contract'
        );

        $this->assertTrue($result->isEligible());
        $expected = $baseSalary * (8 / 12);
        $this->assertEquals($expected, $result->thrAmount);
        $this->assertEquals('contract_prorated', $result->calculationMethod);
    }

    public function test_calculate_thr_for_daily_employee(): void
    {
        $joinDate = Carbon::parse('2023-01-01');
        $calculationDate = Carbon::parse('2024-01-01');
        $baseSalary = 3000000;
        $workingDaysInYear = 250;

        $result = $this->service->calculateThr(
            $joinDate,
            null,
            $calculationDate,
            $baseSalary,
            'daily',
            $workingDaysInYear
        );

        $this->assertTrue($result->isEligible());
        $this->assertEquals('daily_prorated', $result->calculationMethod);
        // THR should be calculated based on working days formula
        $this->assertGreaterThan(0, $result->thrAmount);
    }

    public function test_throws_exception_for_invalid_calculation_date(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Calculation date cannot be before join date');

        $this->service->calculateThr(
            Carbon::parse('2023-01-01'),
            null,
            Carbon::parse('2022-01-01'), // Before join date
            5000000,
            'permanent'
        );
    }

    public function test_throws_exception_for_invalid_employee_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid employee type: invalid');

        $this->service->calculateThr(
            Carbon::parse('2023-01-01'),
            null,
            Carbon::parse('2024-01-01'),
            5000000,
            'invalid'
        );
    }

    public function test_result_to_array(): void
    {
        $joinDate = Carbon::parse('2023-01-01');
        $calculationDate = Carbon::parse('2024-01-01');
        $baseSalary = 5000000;

        $result = $this->service->calculateThr(
            $joinDate,
            null,
            $calculationDate,
            $baseSalary,
            'permanent'
        );

        $array = $result->toArray();

        $this->assertArrayHasKey('thr_amount', $array);
        $this->assertArrayHasKey('base_salary', $array);
        $this->assertArrayHasKey('months_worked', $array);
        $this->assertArrayHasKey('calculation_method', $array);
        $this->assertArrayHasKey('calculation_notes', $array);
        $this->assertArrayHasKey('calculation_date', $array);
        $this->assertArrayHasKey('is_eligible', $array);
        
        $this->assertEquals($baseSalary, $array['thr_amount']);
        $this->assertTrue($array['is_eligible']);
    }
}