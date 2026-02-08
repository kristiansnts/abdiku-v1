<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Attendance;

use App\Domain\Attendance\Enums\AttendanceRequestType;
use App\Domain\Attendance\Enums\AttendanceStatus;
use App\Domain\Attendance\Models\AttendanceRaw;
use App\Domain\Attendance\Models\AttendanceRequest;
use App\Events\AttendanceRequestSubmitted;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Attendance\AttendanceCorrectionRequest;
use App\Http\Resources\Api\V1\AttendanceRequestResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;

        $requests = AttendanceRequest::query()
            ->where('employee_id', $employee->id)
            ->with(['attendanceRaw', 'reviewer'])
            ->when($request->input('status'), function ($query, $status) {
                $query->where('status', $status);
            })
            ->orderBy('requested_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => AttendanceRequestResource::collection($requests),
            'meta' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total(),
            ],
        ]);
    }

    public function store(AttendanceCorrectionRequest $request): JsonResponse
    {
        $employee = $request->user()->employee;
        $validated = $request->validated();

        // If attendance_raw_id is provided, verify it belongs to this employee
        if (isset($validated['attendance_raw_id'])) {
            $attendance = AttendanceRaw::where('id', $validated['attendance_raw_id'])
                ->where('employee_id', $employee->id)
                ->first();

            if (! $attendance) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_ATTENDANCE',
                        'message' => 'Data kehadiran tidak ditemukan atau bukan milik Anda.',
                    ],
                ], 422);
            }

            // Check if attendance is locked
            if ($attendance->isLocked()) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'ATTENDANCE_LOCKED',
                        'message' => 'Data kehadiran sudah terkunci dan tidak dapat dikoreksi.',
                    ],
                ], 422);
            }
        }

        $attendanceRequest = AttendanceRequest::create([
            'employee_id' => $employee->id,
            'company_id' => $employee->company_id,
            'attendance_raw_id' => $validated['attendance_raw_id'] ?? null,
            'request_type' => $validated['request_type'],
            'requested_clock_in_at' => $validated['requested_clock_in_at'] ?? null,
            'requested_clock_out_at' => $validated['requested_clock_out_at'] ?? null,
            'reason' => $validated['reason'],
            'status' => AttendanceStatus::PENDING,
            'requested_at' => now(),
        ]);

        // Load relationships before dispatching event
        $attendanceRequest->load(['employee', 'attendanceRaw']);

        // Dispatch event for notification
        event(new AttendanceRequestSubmitted($attendanceRequest));

        return response()->json([
            'success' => true,
            'data' => new AttendanceRequestResource($attendanceRequest->load('attendanceRaw')),
            'message' => 'Permintaan koreksi berhasil diajukan.',
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $employee = $request->user()->employee;

        $attendanceRequest = AttendanceRequest::query()
            ->where('id', $id)
            ->where('employee_id', $employee->id)
            ->with(['attendanceRaw.evidences', 'reviewer'])
            ->first();

        if (! $attendanceRequest) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Permintaan tidak ditemukan.',
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new AttendanceRequestResource($attendanceRequest),
        ]);
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $employee = $request->user()->employee;

        $attendanceRequest = AttendanceRequest::query()
            ->where('id', $id)
            ->where('employee_id', $employee->id)
            ->first();

        if (! $attendanceRequest) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Permintaan tidak ditemukan.',
                ],
            ], 404);
        }

        if (! $attendanceRequest->isPending()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'CANNOT_CANCEL',
                    'message' => 'Hanya permintaan dengan status pending yang dapat dibatalkan.',
                ],
            ], 422);
        }

        $attendanceRequest->delete();

        return response()->json([
            'success' => true,
            'message' => 'Permintaan berhasil dibatalkan.',
        ]);
    }
}
