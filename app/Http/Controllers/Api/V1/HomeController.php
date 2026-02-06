<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Attendance\Models\AttendanceRaw;
use App\Domain\Attendance\Models\AttendanceRequest;
use App\Domain\Attendance\Services\Mobile\AttendanceStatusService;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ActivityResource;
use App\Http\Resources\Api\V1\PayslipSummaryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class HomeController extends Controller
{
    public function __construct(
        private readonly AttendanceStatusService $statusService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;

        $attendanceStatus = $this->statusService->getTodayStatus($employee);
        $latestActivity = $this->getLatestActivity($employee, 5);
        $latestPayslip = $this->getLatestPayslip($employee);

        $statusArray = $attendanceStatus->toArray();

        return response()->json([
            'success' => true,
            'data' => [
                'today_attendance' => [
                    'status' => $statusArray['today_attendance']['status'] ?? null,
                    'clock_in' => $statusArray['today_attendance']['clock_in'] ?? null,
                    'clock_out' => $statusArray['today_attendance']['clock_out'] ?? null,
                    'shift' => $statusArray['shift'] ? sprintf(
                        '%s–%s',
                        $statusArray['shift']['start_time'],
                        $statusArray['shift']['end_time']
                    ) : null,
                ],
                'can_clock_in' => $statusArray['can_clock_in'],
                'can_clock_out' => $statusArray['can_clock_out'],
                'is_holiday' => $statusArray['is_holiday'],
                'holiday' => $statusArray['holiday'],
                'message' => $statusArray['message'],
                'latest_activity' => ActivityResource::collection($latestActivity),
                'latest_payslip' => $latestPayslip ? new PayslipSummaryResource($latestPayslip) : null,
            ],
        ]);
    }

    private function getLatestActivity($employee, int $limit): Collection
    {
        $attendances = AttendanceRaw::query()
            ->where('employee_id', $employee->id)
            ->with(['employee.workAssignments.shiftPolicy'])
            ->orderBy('date', 'desc')
            ->orderBy('clock_in', 'desc')
            ->limit($limit)
            ->get();

        $requests = AttendanceRequest::query()
            ->where('employee_id', $employee->id)
            ->with('employee')
            ->orderBy('requested_at', 'desc')
            ->limit($limit)
            ->get();

        return $attendances->concat($requests)
            ->sortByDesc(function ($item) {
                if ($item instanceof AttendanceRaw) {
                    return $item->clock_out ?? $item->clock_in ?? $item->date;
                }

                return $item->requested_at;
            })
            ->take($limit)
            ->values();
    }

    private function getLatestPayslip($employee)
    {
        return $employee->payrollRows()
            ->with(['payrollBatch.payrollPeriod'])
            ->whereHas('payrollBatch', function ($query) {
                $query->whereNotNull('finalized_at');
            })
            ->orderBy('payroll_batch_id', 'desc')
            ->first();
    }
}
