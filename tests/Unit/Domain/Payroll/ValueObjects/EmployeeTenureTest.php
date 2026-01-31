<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Payroll\ValueObjects;

use App\Domain\Payroll\ValueObjects\EmployeeTenure;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class EmployeeTenureTest extends TestCase
{
    public function test_creates_tenure_from_dates_without_resignation(): void
    {
        $joinDate = Carbon::parse('2023-01-01');
        $endDate = Carbon::parse('2024-01-01');

        $tenure = EmployeeTenure::fromDates($joinDate, $endDate);

        $this->assertEquals($joinDate, $tenure->startDate);
        $this->assertEquals($endDate, $tenure->endDate);
        $this->assertEquals(12.0, $tenure->monthsWorked);
        $this->assertEquals(365, $tenure->daysWorked);
        $this->assertFalse($tenure->isResigned);
    }

    public function test_creates_tenure_with_resignation(): void
    {
        $joinDate = Carbon::parse('2023-01-01');
        $resignDate = Carbon::parse('2023-06-01');
        $endDate = Carbon::parse('2024-01-01');

        $tenure = EmployeeTenure::fromDates($joinDate, $endDate, $resignDate);

        $this->assertEquals($joinDate, $tenure->startDate);
        $this->assertEquals($resignDate, $tenure->endDate);
        $this->assertEquals(5.0, $tenure->monthsWorked);
        $this->assertTrue($tenure->isResigned);
    }

    public function test_has_worked_at_least_one_month(): void
    {
        $joinDate = Carbon::parse('2023-01-01');
        $endDate = Carbon::parse('2023-02-15');

        $tenure = EmployeeTenure::fromDates($joinDate, $endDate);

        $this->assertTrue($tenure->hasWorkedAtLeastOneMonth());
    }

    public function test_has_not_worked_one_month(): void
    {
        $joinDate = Carbon::parse('2023-01-01');
        $endDate = Carbon::parse('2023-01-15');

        $tenure = EmployeeTenure::fromDates($joinDate, $endDate);

        $this->assertFalse($tenure->hasWorkedAtLeastOneMonth());
    }

    public function test_has_worked_full_year(): void
    {
        $joinDate = Carbon::parse('2023-01-01');
        $endDate = Carbon::parse('2024-01-01');

        $tenure = EmployeeTenure::fromDates($joinDate, $endDate);

        $this->assertTrue($tenure->hasWorkedFullYear());
    }

    public function test_proration_factor_calculation(): void
    {
        $joinDate = Carbon::parse('2023-01-01');
        $endDate = Carbon::parse('2023-07-01'); // 6 months

        $tenure = EmployeeTenure::fromDates($joinDate, $endDate);

        $this->assertEquals(0.5, $tenure->getProrationFactor());
    }

    public function test_proration_factor_caps_at_one(): void
    {
        $joinDate = Carbon::parse('2022-01-01');
        $endDate = Carbon::parse('2024-01-01'); // 24 months

        $tenure = EmployeeTenure::fromDates($joinDate, $endDate);

        $this->assertEquals(1.0, $tenure->getProrationFactor());
    }

    public function test_formatted_months_worked(): void
    {
        $joinDate = Carbon::parse('2023-01-01');
        $endDate = Carbon::parse('2023-07-15'); // ~6.5 months

        $tenure = EmployeeTenure::fromDates($joinDate, $endDate);

        $this->assertStringContainsString('6.', $tenure->getFormattedMonthsWorked());
        $this->assertStringContainsString('bulan', $tenure->getFormattedMonthsWorked());
    }
}