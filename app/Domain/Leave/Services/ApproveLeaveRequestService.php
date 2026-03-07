<?php

declare(strict_types=1);

namespace App\Domain\Leave\Services;

use App\Domain\Leave\Enums\LeaveRequestStatus;
use App\Domain\Leave\Models\LeaveBalance;
use App\Domain\Leave\Models\LeaveRecord;
use App\Domain\Leave\Models\LeaveRequest;
use App\Domain\Leave\Models\Holiday;
use App\Models\User;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class ApproveLeaveRequestService
{
    public function execute(LeaveRequest $request, User $approver): void
    {
        // Validate request is pending
        if (!$request->isPending()) {
            throw new \RuntimeException('Only pending leave requests can be approved');
        }

        // Validate approver has permission (HR or OWNER)
        if (!in_array($approver->role, ['HR', 'OWNER'])) {
            throw new \RuntimeException('User does not have permission to approve leave requests');
        }

        DB::transaction(function () use ($request, $approver) {
            $leaveType = $request->leaveType;
            $totalDays = $request->total_days ?? $request->calculateBusinessDays();

            if ($leaveType->is_deductible) {
                $balance = LeaveBalance::where('employee_id', $request->employee_id)
                    ->where('leave_type_id', $request->leave_type_id)
                    ->where('year', $request->start_date->year)
                    ->lockForUpdate()
                    ->first();

                if (! $balance || $balance->balance < $totalDays) {
                    throw new \RuntimeException('Saldo cuti tidak mencukupi untuk disetujui.');
                }

                $balance->balance = $balance->balance - $totalDays;
                $balance->save();
            }

            // Update request status
            $request->update([
                'status' => LeaveRequestStatus::APPROVED,
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]);

            // Create leave records for each day
            $period = CarbonPeriod::create($request->start_date, $request->end_date);
            $holidayDates = Holiday::query()
                ->where('company_id', $request->employee->company_id)
                ->whereBetween('date', [$request->start_date->toDateString(), $request->end_date->toDateString()])
                ->pluck('date')
                ->map(fn ($date) => $date->format('Y-m-d'))
                ->all();
            $holidayMap = array_flip($holidayDates);

            foreach ($period as $date) {
                // Skip weekends
                if ($date->isWeekend() || isset($holidayMap[$date->format('Y-m-d')])) {
                    continue;
                }

                LeaveRecord::create([
                    'company_id' => $request->employee->company_id,
                    'employee_id' => $request->employee_id,
                    'date' => $date->format('Y-m-d'),
                    'leave_type_id' => $request->leave_type_id,
                    'approved_by' => $approver->id,
                ]);
            }
        });

        // TODO: Send notification to employee
        // Notification::send($request->employee, new LeaveRequestApprovedNotification($request));
    }
}
