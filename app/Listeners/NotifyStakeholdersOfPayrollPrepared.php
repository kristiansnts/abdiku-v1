<?php

namespace App\Listeners;

use App\Events\PayrollPrepared;
use App\Helpers\FilamentUrlHelper;
use App\Helpers\NotificationRecipientHelper;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;

class NotifyStakeholdersOfPayrollPrepared
{
    public function handle(PayrollPrepared $event): void
    {
        $period = $event->payrollPeriod;
        $employeeCount = $event->employeeCount;
        $stakeholders = NotificationRecipientHelper::getStakeholders($period->company_id);

        foreach ($stakeholders as $stakeholder) {
            Notification::make()
                ->title('Penggajian Siap untuk Ditinjau')
                ->body("Penggajian untuk periode {$period->period_start} - {$period->period_end} telah disiapkan. {$employeeCount} karyawan diproses.")
                ->icon('heroicon-o-clipboard-document-check')
                ->iconColor('info')
                ->status('info')
                ->actions([
                    Action::make('view')
                        ->label('Tinjau')
                        ->button()
                        ->url(FilamentUrlHelper::payrollPeriodUrl($period))
                        ->markAsRead(),
                ])
                ->sendToDatabase($stakeholder);
        }
    }
}
