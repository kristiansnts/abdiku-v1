<?php

namespace App\Listeners;

use App\Events\AttendanceOverrideRequiresOwner;
use App\Helpers\FilamentUrlHelper;
use App\Helpers\NotificationRecipientHelper;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;

class NotifyOwnerOfOverrideRequest
{
    public function handle(AttendanceOverrideRequiresOwner $event): void
    {
        $overrideRequest = $event->overrideRequest;
        $requestedBy = $event->requestedBy;
        $companyId = $overrideRequest->attendanceDecision->employee->company_id;

        $ownerUsers = NotificationRecipientHelper::getOwnerUsers($companyId);

        foreach ($ownerUsers as $owner) {
            Notification::make()
                ->title('Persetujuan Override Diperlukan')
                ->body("HR ({$requestedBy->name}) meminta persetujuan override untuk {$overrideRequest->attendanceDecision->employee->name}")
                ->icon('heroicon-o-exclamation-triangle')
                ->iconColor('warning')
                ->status('warning')
                ->actions([
                    Action::make('view')
                        ->label('Tinjau')
                        ->button()
                        ->url(FilamentUrlHelper::overrideRequestUrl($overrideRequest))
                        ->markAsRead(),
                    Action::make('dismiss')
                        ->label('Nanti')
                        ->link()
                        ->markAsRead()
                ])
                ->sendToDatabase($owner);
        }
    }
}
