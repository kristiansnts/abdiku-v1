<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Application\Payroll\Services\PayslipPdfService;
use App\Domain\Payroll\Models\PayrollRow;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PayslipSignedDownloadController extends Controller
{
    public function __construct(
        private readonly PayslipPdfService $pdfService,
    ) {}

    public function __invoke(Request $request, int $id, int $employee_id): Response
    {
        // The signature is already validated by the 'signed' middleware
        // We just need to verify the payslip exists and belongs to the employee

        $payslip = PayrollRow::with([
            'payrollBatch.payrollPeriod',
            'payrollBatch.company',
            'employee.compensations',
            'deductions',
            'additions',
        ])
            ->where('employee_id', $employee_id)
            ->whereHas('payrollBatch', fn ($q) => $q->whereNotNull('finalized_at'))
            ->find($id);

        if (! $payslip) {
            abort(404, 'Slip gaji tidak ditemukan.');
        }

        $pdfContent = $this->pdfService->generate($payslip);
        $filename = $this->pdfService->generateFilename($payslip);

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'no-store',
        ]);
    }
}
