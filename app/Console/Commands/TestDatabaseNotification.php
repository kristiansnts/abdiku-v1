<?php

namespace App\Console\Commands;

use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use Illuminate\Console\Command;

class TestDatabaseNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notification:test {user_id? : The ID of the user to send the test notification to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test database notification to verify the setup is working';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $userId = $this->argument('user_id');

        if ($userId) {
            $user = User::find($userId);

            if (!$user) {
                $this->error("User with ID {$userId} not found.");
                return self::FAILURE;
            }
        } else {
            // Get the first user if no ID specified
            $user = User::first();

            if (!$user) {
                $this->error('No users found in the database.');
                return self::FAILURE;
            }

            $this->info("No user ID specified. Using first user: {$user->name} (ID: {$user->id})");
        }

        try {
            // Send a test notification
            Notification::make()
                ->title('Test Notification')
                ->success()
                ->body('This is a test notification to verify database notifications are working correctly.')
                ->icon('heroicon-o-check-circle')
                ->iconColor('success')
                ->actions([
                    Action::make('dismiss')
                        ->button()
                        ->label('Got it!')
                        ->markAsRead(),
                ])
                ->sendToDatabase($user);

            $this->info('✅ Test notification sent successfully!');
            $this->info("Recipient: {$user->name} ({$user->email})");
            $this->line('');
            $this->info('Check the notifications bell icon in the admin panel to see the notification.');
            $this->line('');
            $this->comment('You can also check the database:');
            $this->line('  mysql> SELECT * FROM notifications WHERE notifiable_id = ' . $user->id . ';');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Failed to send notification: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
