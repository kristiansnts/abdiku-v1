<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Employee;

use App\Application\Payroll\Services\PayslipPdfService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PayslipDownloadController extends Controller
{
    public function __construct(
        private readonly PayslipPdfService $pdfService,
    ) {}

    public function __invoke(Request $request, int $id): Response|JsonResponse
    {
        $employee = $request->user()->employee;

        $payslip = $employee->payrollRows()
            ->with([
                'payrollBatch.payrollPeriod',
                'payrollBatch.company',
                'employee.compensations',
                'deductions',
                'additions',
            ])
            ->whereHas('payrollBatch', fn ($q) => $q->whereNotNull('finalized_at'))
            ->find($id);

        if (! $payslip) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'PAYSLIP_NOT_FOUND',
                    'message' => 'Slip gaji tidak ditemukan.',
                ],
            ], 404);
        }

        try {
            $pdfContent = $this->pdfService->generate($payslip);
            $filename = $this->pdfService->generateFilename($payslip);

            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                'Cache-Control' => 'private, max-age=3600',
            ]);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'PDF_GENERATION_FAILED',
                    'message' => 'Gagal membuat slip gaji PDF.',
                ],
            ], 500);
        }
    }
}
