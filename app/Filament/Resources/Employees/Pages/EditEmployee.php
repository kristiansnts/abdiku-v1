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
        // Load the current role and email from User record
        if ($this->record->user_id && $this->record->user) {
            $data['user_role'] = $this->record->user->roles->first()?->name ?? 'employee';
            $data['email'] = $this->record->user->email;
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $rawState = $this->form->getRawState();
        $userRole = $rawState['user_role'] ?? null;
        $email = $rawState['email'] ?? null;

        if ($this->record->user_id && ($userRole || $email)) {
            $user = $this->record->user;

            // Handle role change
            if ($userRole && auth()->user()?->hasRole('owner')) {
                $user->syncRoles([$userRole]);
            }

            // Handle email change
            if ($email && $user->email !== $email) {
                $user->update(['email' => $email]);
            }
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
