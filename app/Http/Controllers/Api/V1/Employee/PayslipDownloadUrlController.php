<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class PayslipDownloadUrlController extends Controller
{
    public function __invoke(Request $request, int $id): JsonResponse
    {
        $employee = $request->user()->employee;

        // Verify the payslip belongs to this employee and is finalized
        $payslip = $employee->payrollRows()
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

        // Generate a signed URL valid for 5 minutes
        $downloadUrl = URL::temporarySignedRoute(
            'payslip.download.signed',
            now()->addMinutes(5),
            ['id' => $id, 'employee_id' => $employee->id]
        );

        return response()->json([
            'success' => true,
            'data' => [
                'download_url' => $downloadUrl,
                'expires_in' => 300, // 5 minutes in seconds
            ],
            'message' => 'URL download valid selama 5 menit.',
        ]);
    }
}
