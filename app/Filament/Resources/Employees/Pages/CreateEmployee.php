<?php

declare(strict_types=1);

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Resources\Employees\EmployeeResource;
use App\Models\User;
use App\Notifications\EmployeeInvitationNotification;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

final class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function afterCreate(): void
    {
        $employee = $this->record;
        $rawState = $this->form->getRawState();

        $email = $rawState['email'] ?? null;

        if (!$email) {
            return;
        }

        try {
            $user = User::create([
                'company_id' => $employee->company_id,
                'name'       => $employee->name,
                'email'      => $email,
                'phone'      => $employee->phone,
                'password'   => Hash::make(Str::random(32)),
            ]);

            $user->assignRole('employee');

            $employee->update(['user_id' => $user->id]);

            $token = Password::broker('invitations')->createToken($user);
            $user->notify(new EmployeeInvitationNotification($token));
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Akun karyawan gagal dibuat')
                ->body('Karyawan berhasil disimpan, tetapi akun pengguna gagal dibuat: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
}
