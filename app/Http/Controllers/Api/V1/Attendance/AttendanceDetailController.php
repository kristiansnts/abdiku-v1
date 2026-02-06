<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Attendance;

use App\Domain\Attendance\Models\AttendanceRaw;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AttendanceDetailResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceDetailController extends Controller
{
    public function __invoke(Request $request, int $id): JsonResponse
    {
        $employee = $request->user()->employee;

        $attendance = AttendanceRaw::query()
            ->where('id', $id)
            ->where('employee_id', $employee->id)
            ->with(['employee', 'evidences', 'companyLocation', 'requests.reviewer'])
            ->first();

        if (! $attendance) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'ATTENDANCE_NOT_FOUND',
                    'message' => 'Data kehadiran tidak ditemukan.',
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new AttendanceDetailResource($attendance),
        ]);
    }
}
