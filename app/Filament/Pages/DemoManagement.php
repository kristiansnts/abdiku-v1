<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Company;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;

class DemoManagement extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-beaker';
    protected static ?string $navigationLabel = 'Demo Management';
    protected static ?string $title = 'Demo Management';
    protected static ?string $navigationGroup = 'Super Admin';
    protected static ?int $navigationSort = 99;

    protected static string $view = 'filament.pages.demo-management';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'super-admin']) ?? false;
    }

    public function getDemoCompaniesProperty(): \Illuminate\Database\Eloquent\Collection
    {
        return Company::where('is_demo', true)
            ->withCount(['users', 'employees'])
            ->orderByDesc('created_at')
            ->get();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cleanup_all')
                ->label('Hapus Semua Demo')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Hapus Semua Sesi Demo')
                ->modalDescription('Semua perusahaan demo beserta data karyawan, absensi, dan penggajian akan dihapus permanen.')
                ->modalSubmitActionLabel('Ya, Hapus Semua')
                ->action(function () {
                    Artisan::call('demo:cleanup', ['--no-interaction' => true]);
                    Notification::make()
                        ->title('Berhasil')
                        ->body('Semua sesi demo telah dihapus.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
