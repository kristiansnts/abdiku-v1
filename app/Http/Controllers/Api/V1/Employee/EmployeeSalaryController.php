<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\EmployeeSalaryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeSalaryController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;

        // Get current active compensation
        $currentCompensation = $employee->compensations()
            ->whereDate('effective_from', '<=', now())
            ->where(function ($query) {
                $query->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', now());
            })
            ->orderBy('effective_from', 'desc')
            ->first();

        if (!$currentCompensation) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'Data gaji tidak ditemukan.',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => new EmployeeSalaryResource($currentCompensation),
        ]);
    }
}
