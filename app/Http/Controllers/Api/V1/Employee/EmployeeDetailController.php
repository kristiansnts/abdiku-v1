<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\EmployeeDetailResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeDetailController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;

        $employee->load([
            'company',
            'activeWorkAssignment.shiftPolicy',
        ]);

        return response()->json([
            'success' => true,
            'data' => new EmployeeDetailResource($employee),
        ]);
    }
}
