<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmployeeInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('filament.admin.auth.password-reset.reset', [
            'token' => $this->token,
            'email' => $notifiable->email,
        ]);

        return (new MailMessage)
            ->subject('Undangan Akun ' . config('app.name'))
            ->greeting('Halo, ' . $notifiable->name . '!')
            ->line('Akun Anda telah dibuat oleh admin perusahaan.')
            ->line('Klik tombol di bawah untuk mengatur kata sandi dan mulai menggunakan akun Anda.')
            ->action('Atur Kata Sandi', $url)
            ->line('Tautan ini berlaku selama ' . config('auth.passwords.users.expire', 60) . ' menit.')
            ->line('Jika Anda tidak merasa mendaftar, abaikan email ini.');
    }
}
