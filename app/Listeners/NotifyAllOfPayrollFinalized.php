<?php

namespace App\Listeners;

use App\Events\PayrollFinalized;
use App\Helpers\FilamentUrlHelper;
use App\Helpers\NotificationRecipientHelper;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;

class NotifyAllOfPayrollFinalized
{
    public function handle(PayrollFinalized $event): void
    {
        $period = $event->payrollPeriod;
        $batch = $event->payrollBatch;

        // Notify stakeholders (HR + owners)
        $stakeholders = NotificationRecipientHelper::getStakeholders($period->company_id);
        foreach ($stakeholders as $stakeholder) {
            Notification::make()
                ->title('Penggajian Berhasil Difinalisasi')
                ->body("Penggajian untuk periode {$period->period_start} - {$period->period_end} telah difinalisasi.")
                ->icon('heroicon-o-banknotes')
                ->iconColor('success')
                ->status('success')
                ->actions([
                    Action::make('view')
                        ->label('Lihat Batch')
                        ->button()
                        ->url(FilamentUrlHelper::payrollBatchUrl($batch))
                        ->markAsRead(),
                ])
                ->sendToDatabase($stakeholder);
        }

        // Notify all employees
        $employees = NotificationRecipientHelper::getAllEmployeeUsers($period->company_id);
        foreach ($employees as $employee) {
            Notification::make()
                ->title('Slip Gaji Tersedia')
                ->body("Penggajian untuk periode {$period->period_start} - {$period->period_end} telah difinalisasi. Cek slip gaji Anda!")
                ->icon('heroicon-o-banknotes')
                ->iconColor('success')
                ->status('success')
                ->sendToDatabase($employee);
        }
    }
}
