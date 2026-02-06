<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\EmployeePayslipResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayslipController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;

        $payslips = $employee->payrollRows()
            ->with([
                'payrollBatch.payrollPeriod',
                'deductions',
                'additions',
            ])
            ->whereHas('payrollBatch', function ($query) {
                $query->whereNotNull('finalized_at');
            })
            ->orderBy('payroll_batch_id', 'desc')
            ->paginate($request->input('per_page', 10));

        if ($payslips->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [],
                'meta' => [
                    'current_page' => 1,
                    'total' => 0,
                    'per_page' => 10,
                    'last_page' => 1,
                ],
                'message' => 'Slip gaji tidak ditemukan.',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => EmployeePayslipResource::collection($payslips->items()),
            'meta' => [
                'current_page' => $payslips->currentPage(),
                'total' => $payslips->total(),
                'per_page' => $payslips->perPage(),
                'last_page' => $payslips->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $employee = $request->user()->employee;

        $payslip = $employee->payrollRows()
            ->with([
                'payrollBatch.payrollPeriod',
                'deductions',
                'additions',
            ])
            ->whereHas('payrollBatch', function ($query) {
                $query->whereNotNull('finalized_at');
            })
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

        return response()->json([
            'success' => true,
            'data' => new EmployeePayslipResource($payslip),
        ]);
    }
}
