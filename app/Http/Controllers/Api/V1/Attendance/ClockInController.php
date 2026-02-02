<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Attendance;

use App\Domain\Attendance\DataTransferObjects\ClockInData;
use App\Domain\Attendance\Services\Mobile\ClockInService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Attendance\ClockInRequest;
use App\Http\Resources\Api\V1\AttendanceRawResource;
use Illuminate\Http\JsonResponse;

class ClockInController extends Controller
{
    public function __construct(
        private readonly ClockInService $clockInService,
    ) {}

    public function __invoke(ClockInRequest $request): JsonResponse
    {
        $employee = $request->user()->employee;

        $data = ClockInData::fromArray($request->validated());

        $attendance = $this->clockInService->execute($employee, $data);

        $message = $attendance->isPending()
            ? 'Clock in berhasil. Menunggu verifikasi karena lokasi di luar area.'
            : 'Clock in berhasil.';

        return response()->json([
            'success' => true,
            'data' => new AttendanceRawResource($attendance),
            'message' => $message,
        ], 201);
    }
}
