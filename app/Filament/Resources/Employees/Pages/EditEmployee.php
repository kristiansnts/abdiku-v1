<?php

declare(strict_types=1);

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Resources\Employees\EmployeeResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

final class EditEmployee extends EditRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load the current role from Spatie roles
        if ($this->record->user_id && $this->record->user) {
            $data['user_role'] = $this->record->user->roles->first()?->name ?? 'employee';
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Handle role change if user_role is set in the form state
        $userRole = $this->form->getRawState()['user_role'] ?? null;

        if ($userRole && $this->record->user_id && auth()->user()?->hasRole('owner')) {
            $this->record->user->syncRoles([$userRole]);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        // Show success notification
        Notification::make()
            ->title('Data karyawan berhasil diperbarui')
            ->success()
            ->send();
    }
}
