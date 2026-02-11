<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\PasswordReset\ResetPassword as BaseResetPassword;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Support\Facades\Password;

class ResetPassword extends BaseResetPassword
{
    public function mount(?string $email = null, ?string $token = null): void
    {
        parent::mount($email, $token);

        // Validate token immediately on page load
        // If token is invalid or already used, redirect to login
        if ($this->token && $this->email) {
            /** @var PasswordBroker $broker */
            $broker = Password::broker(Filament::getAuthPasswordBroker());
            $user = $broker->getUser(['email' => $this->email]);

            if (!$user || !$broker->tokenExists($user, $this->token)) {
                Notification::make()
                    ->title('Link sudah tidak berlaku')
                    ->body('Link ini sudah digunakan atau tidak berlaku lagi. Silakan hubungi admin untuk mendapatkan undangan baru.')
                    ->danger()
                    ->send();

                redirect(Filament::getLoginUrl());
            }
        }
    }

    protected function getResetPasswordSuccessNotificationTitle(): string
    {
        return 'Password Berhasil Diatur';
    }

    protected function getResetPasswordSuccessNotificationMessage(): string
    {
        return 'Password Anda telah berhasil diatur. Silakan login dengan password baru Anda.';
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title($this->getResetPasswordSuccessNotificationTitle())
            ->body($this->getResetPasswordSuccessNotificationMessage());
    }
}

