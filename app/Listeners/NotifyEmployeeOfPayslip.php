<?php

namespace App\Listeners;

use App\Events\PayslipAvailable;
use App\Helpers\FilamentUrlHelper;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;

class NotifyEmployeeOfPayslip
{
    public function handle(PayslipAvailable $event): void
    {
        $row = $event->payrollRow;
        $employee = $event->employee;
        $user = $employee->user;

        if (!$user) {
            return;
        }

        $period = $row->payrollBatch->payrollPeriod;
        $netSalary = number_format($row->net_salary, 0, ',', '.');

        Notification::make()
            ->title('Slip Gaji Anda Tersedia')
            ->body("Slip gaji untuk periode {$period->period_start} - {$period->period_end} sudah tersedia. Gaji bersih: Rp {$netSalary}")
            ->icon('heroicon-o-document-text')
            ->iconColor('success')
            ->status('success')
            ->actions([
                Action::make('view')
                    ->label('Lihat Slip Gaji')
                    ->button()
                    ->url(FilamentUrlHelper::payslipUrl($row))
                    ->markAsRead(),
            ])
            ->sendToDatabase($user);
    }
}
