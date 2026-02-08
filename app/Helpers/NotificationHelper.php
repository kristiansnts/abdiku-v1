<?php

namespace App\Helpers;

use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use App\Models\User;

class NotificationHelper
{
    /**
     * Send a simple success notification to a user
     */
    public static function sendSuccess(User $recipient, string $title, ?string $body = null): void
    {
        Notification::make()
            ->title($title)
            ->success()
            ->body($body)
            ->sendToDatabase($recipient);
    }

    /**
     * Send a warning notification to a user
     */
    public static function sendWarning(User $recipient, string $title, ?string $body = null): void
    {
        Notification::make()
            ->title($title)
            ->warning()
            ->body($body)
            ->sendToDatabase($recipient);
    }

    /**
     * Send a danger/error notification to a user
     */
    public static function sendDanger(User $recipient, string $title, ?string $body = null): void
    {
        Notification::make()
            ->title($title)
            ->danger()
            ->body($body)
            ->sendToDatabase($recipient);
    }

    /**
     * Send an info notification to a user
     */
    public static function sendInfo(User $recipient, string $title, ?string $body = null): void
    {
        Notification::make()
            ->title($title)
            ->info()
            ->body($body)
            ->sendToDatabase($recipient);
    }

    /**
     * Send a notification with custom actions
     *
     * Example usage:
     * NotificationHelper::sendWithActions(
     *     $user,
     *     'New Task Assigned',
     *     'You have been assigned a new task.',
     *     [
     *         Action::make('view')
     *             ->button()
     *             ->url(route('tasks.show', $task))
     *             ->markAsRead(),
     *         Action::make('dismiss')
     *             ->link()
     *             ->markAsRead()
     *     ]
     * );
     */
    public static function sendWithActions(
        User $recipient,
        string $title,
        ?string $body = null,
        array $actions = []
    ): void {
        Notification::make()
            ->title($title)
            ->body($body)
            ->actions($actions)
            ->sendToDatabase($recipient);
    }

    /**
     * Send notification to multiple users
     */
    public static function sendToMultiple(
        iterable $recipients,
        string $title,
        ?string $body = null,
        string $status = 'success'
    ): void {
        $notification = Notification::make()
            ->title($title)
            ->body($body);

        match ($status) {
            'success' => $notification->success(),
            'warning' => $notification->warning(),
            'danger' => $notification->danger(),
            'info' => $notification->info(),
            default => $notification->success(),
        };

        foreach ($recipients as $recipient) {
            $notification->sendToDatabase($recipient);
        }
    }

    /**
     * Send a notification with icon
     */
    public static function sendWithIcon(
        User $recipient,
        string $title,
        string $icon,
        ?string $body = null,
        string $iconColor = 'success'
    ): void {
        Notification::make()
            ->title($title)
            ->icon($icon)
            ->iconColor($iconColor)
            ->body($body)
            ->sendToDatabase($recipient);
    }
}
