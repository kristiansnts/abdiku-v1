<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Attendance;

use App\Domain\Attendance\DataTransferObjects\ClockOutData;
use App\Domain\Attendance\Services\Mobile\ClockOutService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Attendance\ClockOutRequest;
use App\Http\Resources\Api\V1\AttendanceRawResource;
use Illuminate\Http\JsonResponse;

class ClockOutController extends Controller
{
    public function __construct(
        private readonly ClockOutService $clockOutService,
    ) {}

    public function __invoke(ClockOutRequest $request): JsonResponse
    {
        $employee = $request->user()->employee;

        $data = ClockOutData::fromArray($request->validated());

        $attendance = $this->clockOutService->execute($employee, $data);

        return response()->json([
            'success' => true,
            'data' => new AttendanceRawResource($attendance),
            'message' => 'Clock out berhasil.',
        ]);
    }
}
