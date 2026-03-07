<?php

declare(strict_types=1);

namespace App\Domain\Leave\Services;

use App\Domain\Leave\Enums\LeaveRequestStatus;
use App\Domain\Leave\Models\LeaveBalance;
use App\Domain\Leave\Models\LeaveRequest;
use App\Domain\Leave\Models\LeaveType;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateLeaveRequestService
{
    public function execute(Employee $employee, array $data): LeaveRequest
    {
        return DB::transaction(function () use ($employee, $data) {
            $leaveType = LeaveType::findOrFail($data['leave_type_id']);
            $startDate = Carbon::parse($data['start_date']);
            $endDate = Carbon::parse($data['end_date']);

            $overlap = LeaveRequest::query()
                ->where('employee_id', $employee->id)
                ->whereIn('status', [LeaveRequestStatus::PENDING, LeaveRequestStatus::APPROVED])
                ->whereDate('start_date', '<=', $endDate->toDateString())
                ->whereDate('end_date', '>=', $startDate->toDateString())
                ->exists();

            if ($overlap) {
                throw ValidationException::withMessages([
                    'start_date' => ['Pengajuan cuti bertabrakan dengan pengajuan lain yang masih pending atau sudah disetujui.'],
                ]);
            }

            // 1. Hitung hari kerja (business days)
            $leaveRequest = new LeaveRequest([
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'reason' => $data['reason'] ?? null,
                'status' => LeaveRequestStatus::PENDING,
            ]);
            
            $totalDays = $leaveRequest->calculateBusinessDays();
            $leaveRequest->total_days = $totalDays;

            // 2. Validasi Saldo (jika tipe cuti memotong saldo)
            if ($leaveType->is_deductible) {
                $balance = LeaveBalance::where('employee_id', $employee->id)
                    ->where('leave_type_id', $leaveType->id)
                    ->where('year', $startDate->year)
                    ->first();

                if (!$balance || $balance->balance < $totalDays) {
                    throw ValidationException::withMessages([
                        'total_days' => ['Saldo cuti tidak mencukupi. Sisa saldo: ' . ($balance->balance ?? 0) . ' hari.'],
                    ]);
                }
            }

            // 3. Simpan
            if (isset($data['attachment_path'])) {
                $leaveRequest->attachment_path = $data['attachment_path'];
            }
            $leaveRequest->save();

            return $leaveRequest;
        });
    }
}
