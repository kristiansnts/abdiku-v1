<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Domain\Payroll\Enums\PayrollState;
use App\Domain\Payroll\Models\PayrollPeriod;
use Filament\Widgets\Widget;

class PayrollStatusWidget extends Widget
{
    protected static ?int $sort = 2;

    protected static string $view = 'filament.widgets.payroll-status';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        if (!$user || $user->hasRole(['super_admin', 'super-admin'])) {
            return false;
        }

        return $user->company !== null;
    }

    public function getCurrentPeriodProperty(): ?PayrollPeriod
    {
        return PayrollPeriod::query()
            ->where('company_id', auth()->user()?->company_id)
            ->whereYear('period_start', now()->year)
            ->whereMonth('period_start', now()->month)
            ->first();
    }

    public function getPayrollIndexUrlProperty(): string
    {
        return route('filament.admin.resources.payroll-periods.index');
    }
}
