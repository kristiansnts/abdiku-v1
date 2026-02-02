<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Attendance;

use App\Domain\Attendance\Services\Mobile\AttendanceStatusService;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AttendanceStatusResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceStatusController extends Controller
{
    public function __construct(
        private readonly AttendanceStatusService $statusService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;

        $status = $this->statusService->getTodayStatus($employee);

        return response()->json([
            'success' => true,
            'data' => new AttendanceStatusResource($status),
        ]);
    }
}
