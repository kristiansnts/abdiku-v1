<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Attendance\Models\AttendanceRaw;
use App\Domain\Attendance\Models\AttendanceRequest;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ActivityResource;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ActivityController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;
        $limit = (int) $request->input('limit', 10);

        $query = $this->buildDateFilter($request);

        $attendances = AttendanceRaw::query()
            ->where('employee_id', $employee->id)
            ->when($query['start'], fn ($q) => $q->whereDate('date', '>=', $query['start']))
            ->when($query['end'], fn ($q) => $q->whereDate('date', '<=', $query['end']))
            ->with(['employee.workAssignments.shiftPolicy'])
            ->orderBy('date', 'desc')
            ->orderBy('clock_in', 'desc')
            ->limit($limit)
            ->get();

        $requests = AttendanceRequest::query()
            ->where('employee_id', $employee->id)
            ->when($query['start'], fn ($q) => $q->whereDate('requested_at', '>=', $query['start']))
            ->when($query['end'], fn ($q) => $q->whereDate('requested_at', '<=', $query['end']))
            ->with('employee')
            ->orderBy('requested_at', 'desc')
            ->limit($limit)
            ->get();

        $merged = $this->mergeAndSort($attendances, $requests, $limit);

        return response()->json([
            'success' => true,
            'data' => ActivityResource::collection($merged),
        ]);
    }

    private function buildDateFilter(Request $request): array
    {
        $month = $request->input('month');

        if ($month) {
            $date = Carbon::createFromFormat('Y-m', $month);

            return [
                'start' => $date->copy()->startOfMonth()->toDateString(),
                'end' => $date->copy()->endOfMonth()->toDateString(),
            ];
        }

        return ['start' => null, 'end' => null];
    }

    private function mergeAndSort(Collection $attendances, Collection $requests, int $limit): Collection
    {
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
}
