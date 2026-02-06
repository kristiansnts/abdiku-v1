<?php

declare(strict_types=1);

namespace App\Application\Payroll\Services;

use App\Domain\Payroll\Models\PayrollRow;
use App\Domain\Payroll\ValueObjects\PayslipData;
use Barryvdh\DomPDF\Facade\Pdf;

final class PayslipPdfService
{
    public function generate(PayrollRow $payrollRow): string
    {
        $payrollRow->load([
            'payrollBatch.payrollPeriod',
            'payrollBatch.company',
            'employee.compensations',
            'deductions',
            'additions',
        ]);

        $data = PayslipData::fromPayrollRow($payrollRow);

        $pdf = Pdf::loadView('pdf.payslip', ['payslip' => $data])
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif',
            ]);

        return $pdf->output();
    }

    public function generateFilename(PayrollRow $payrollRow): string
    {
        $period = $payrollRow->payrollBatch->payrollPeriod;
        $monthName = $this->getMonthName($period->month);
        $employeeName = str_replace(' ', '-', strtolower($payrollRow->employee->name));
        $employeeName = preg_replace('/[^a-z0-9\-]/', '', $employeeName);

        return "slip-gaji-{$employeeName}-{$monthName}-{$period->year}.pdf";
    }

    private function getMonthName(?int $month): string
    {
        return [
            1 => 'januari', 2 => 'februari', 3 => 'maret',
            4 => 'april', 5 => 'mei', 6 => 'juni',
            7 => 'juli', 8 => 'agustus', 9 => 'september',
            10 => 'oktober', 11 => 'november', 12 => 'desember',
        ][$month] ?? '';
    }
}
