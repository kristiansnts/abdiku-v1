<?php

declare(strict_types=1);

namespace App\Filament\Resources\Employees\Pages;

use App\Auth\Passwords\CompanyScopedDatabaseTokenRepository;
use App\Filament\Resources\Employees\EmployeeResource;
use App\Notifications\EmployeeInvitationNotification;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

final class ViewEmployee extends ViewRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('resend_invitation')
                ->label('Reset Password Karyawan')
                ->icon('heroicon-o-envelope')
                ->color('info')
                ->visible(fn() => $this->record->user_id !== null)
                ->requiresConfirmation()
                ->modalHeading('Reset Password Karyawan')
                ->modalDescription('Apakah Anda yakin ingin mengirim ulang undangan password reset ke karyawan ini?')
                ->modalSubmitActionLabel('Ya, Kirim Ulang')
                ->action(function () {
                    try {
                        $user = $this->record->user;

                        if (!$user) {
                            Notification::make()
                                ->title('Gagal')
                                ->body('Karyawan ini belum memiliki akun pengguna.')
                                ->danger()
                                ->send();
                            return;
                        }

                        // Create custom token repository with company context
                        $key = config('app.key');
                        if (str_starts_with($key, 'base64:')) {
                            $key = base64_decode(substr($key, 7));
                        }

                        $repository = new CompanyScopedDatabaseTokenRepository(
                            app('db')->connection(),
                            app('hash'),
                            'password_reset_tokens',
                            $key,
                            525600, // 365 days
                            0
                        );

                        // Generate new token with company context
                        $token = $repository->create($user);

                        // Send notification
                        $user->notify(new EmployeeInvitationNotification($token));

                        Notification::make()
                            ->title('Undangan Terkirim')
                            ->body('Undangan password reset telah dikirim ke ' . $user->email)
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Gagal Mengirim Undangan')
                            ->body('Terjadi kesalahan: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
