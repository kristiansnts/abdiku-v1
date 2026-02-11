<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Domain\Attendance\Models\AttendanceRaw;
use App\Domain\Payroll\Enums\PayrollState;
use App\Domain\Payroll\Models\PayrollPeriod;
use App\Models\Employee;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        // Only show widget if user has a company and is not a super admin
        $user = auth()->user();

        if (!$user || $user->hasRole(['super_admin', 'super-admin'])) {
            return false;
        }

        return $user->company !== null;
    }

    protected function getStats(): array
    {
        $companyId = auth()->user()?->company_id;

        // 1. Total daily attendance (today)
        $todayAttendanceCount = AttendanceRaw::query()
            ->where('company_id', $companyId)
            ->whereDate('date', now('Asia/Jakarta'))
            ->count();

        $attendanceTrend = AttendanceRaw::query()
            ->where('company_id', $companyId)
            ->where('date', '>=', now('Asia/Jakarta')->subDays(7))
            ->selectRaw('DATE(date) as day, count(*) as count')
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('count')
            ->toArray();

        // 2. Total employee (in company)
        $employeeCount = Employee::query()
            ->where('company_id', $companyId)
            ->where('status', 'ACTIVE')
            ->count();

        // Simple trend for employee (last 7 months growth)
        $employeeTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subMonths($i)->endOfMonth();
            $employeeTrend[] = Employee::query()
                ->where('company_id', $companyId)
                ->where('join_date', '<=', $date)
                ->where(function ($query) use ($date) {
                    $query->whereNull('resign_date')
                        ->orWhere('resign_date', '>', $date);
                })
                ->count();
        }

        // 3. Total last period cashout for employee salary
        $lastFinalizedPeriod = PayrollPeriod::query()
            ->where('company_id', $companyId)
            ->where('state', PayrollState::FINALIZED)
            ->latest('finalized_at')
            ->first();

        $lastCashoutAmount = 0;
        if ($lastFinalizedPeriod && $lastFinalizedPeriod->payrollBatch) {
            $lastCashoutAmount = $lastFinalizedPeriod->payrollBatch->rows()->sum('net_amount');
        }

        $cashoutTrend = PayrollPeriod::query()
            ->where('company_id', $companyId)
            ->where('state', PayrollState::FINALIZED)
            ->latest('finalized_at')
            ->take(7)
            ->get()
            ->map(fn($p) => (float) ($p->payrollBatch?->total_amount ?? 0))
            ->reverse()
            ->values()
            ->toArray();

        return [
            Stat::make('Kehadiran Hari Ini', $todayAttendanceCount)
                ->description('Total karyawan yang sudah absen hari ini')
                ->descriptionIcon('heroicon-m-clock')
                ->chart($attendanceTrend ?: [0])
                ->color('success'),
            Stat::make('Total Karyawan', $employeeCount)
                ->description('Jumlah karyawan aktif saat ini')
                ->descriptionIcon('heroicon-m-users')
                ->chart($employeeTrend)
                ->color('info'),
            Stat::make('Cashout Gaji Terakhir', 'Rp ' . number_format((float) $lastCashoutAmount, 0, ',', '.'))
                ->description($lastFinalizedPeriod ? 'Periode: ' . $lastFinalizedPeriod->period_start->format('M Y') : 'Belum ada periode finalized')
                ->descriptionIcon('heroicon-m-banknotes')
                ->chart($cashoutTrend ?: [0])
                ->color('primary'),
        ];
    }
}
