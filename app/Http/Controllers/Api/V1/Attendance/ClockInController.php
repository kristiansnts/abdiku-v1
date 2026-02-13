<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Attendance;

use App\Domain\Attendance\DataTransferObjects\ClockInData;
use App\Domain\Attendance\Services\Mobile\ClockInService;
use App\Domain\Leave\Models\Holiday;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Attendance\ClockInRequest;
use App\Http\Resources\Api\V1\AttendanceRawResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ClockInController extends Controller
{
    public function __construct(
        private readonly ClockInService $clockInService,
    ) {
    }

    public function __invoke(ClockInRequest $request): JsonResponse
    {
        $employee = $request->user()->employee;

        Log::info('Clock-in request received', [
            'employee_id' => $employee->id,
            'user_id' => $request->user()->id,
        ]);

        // Check if today is a holiday
        $holiday = Holiday::where('company_id', $employee->company_id)
            ->where('date', now()->toDateString())
            ->first();

        if ($holiday !== null) {
            return response()->json([
                'success' => false,
                'message' => "Tidak dapat clock in. Hari ini adalah hari libur: {$holiday->name}",
                'error' => 'holiday',
            ], 422);
        }

        $data = ClockInData::fromArray($request->validated());

        $attendance = $this->clockInService->execute($employee, $data);

        $message = $attendance->isPending()
            ? 'Clock in berhasil. Menunggu verifikasi karena lokasi di luar area.'
            : 'Clock in berhasil.';

        Log::info('Clock-in successful', [
            'employee_id' => $employee->id,
            'attendance_id' => $attendance->id,
            'status' => $attendance->isPending() ? 'pending' : 'approved',
        ]);

        return response()->json([
            'success' => true,
            'data' => new AttendanceRawResource($attendance),
            'message' => $message,
        ], 201);
    }
}
