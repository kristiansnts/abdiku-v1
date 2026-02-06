<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Attendance;

use App\Domain\Attendance\Models\AttendanceRaw;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AttendanceHistoryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceHistoryController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;

        $attendances = AttendanceRaw::query()
            ->where('employee_id', $employee->id)
            ->with('employee')
            ->orderBy('date', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => AttendanceHistoryResource::collection($attendances),
            'meta' => [
                'current_page' => $attendances->currentPage(),
                'last_page' => $attendances->lastPage(),
                'per_page' => $attendances->perPage(),
                'total' => $attendances->total(),
            ],
        ]);
    }
}
