<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\UserDeviceResource\Pages;

use App\Filament\Resources\Users\UserDeviceResource;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewUserDevice extends ViewRecord
{
    protected static string $resource = UserDeviceResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Pengguna')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('user.name')
                            ->label('Nama Pengguna'),
                        TextEntry::make('user.email')
                            ->label('Email'),
                    ]),
                Section::make('Informasi Perangkat')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('device_id')
                            ->label('ID Perangkat')
                            ->copyable(),
                        TextEntry::make('device_name')
                            ->label('Nama Perangkat'),
                        TextEntry::make('device_model')
                            ->label('Model'),
                        TextEntry::make('device_os')
                            ->label('Sistem Operasi'),
                        TextEntry::make('app_version')
                            ->label('Versi Aplikasi'),
                        TextEntry::make('last_ip_address')
                            ->label('IP Terakhir'),
                    ]),
                Section::make('Status')
                    ->columns(2)
                    ->schema([
                        IconEntry::make('is_active')
                            ->label('Status Aktif')
                            ->boolean(),
                        IconEntry::make('is_blocked')
                            ->label('Status Blokir')
                            ->boolean()
                            ->trueColor('danger')
                            ->falseColor('success'),
                        TextEntry::make('block_reason')
                            ->label('Alasan Blokir')
                            ->visible(fn ($record) => $record->is_blocked),
                        TextEntry::make('blockedByUser.name')
                            ->label('Diblokir Oleh')
                            ->visible(fn ($record) => $record->is_blocked),
                        TextEntry::make('blocked_at')
                            ->label('Waktu Blokir')
                            ->dateTime('d M Y H:i')
                            ->visible(fn ($record) => $record->is_blocked),
                    ]),
                Section::make('Riwayat')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('last_login_at')
                            ->label('Login Terakhir')
                            ->dateTime('d M Y H:i'),
                        TextEntry::make('created_at')
                            ->label('Pertama Terdaftar')
                            ->dateTime('d M Y H:i'),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('block')
                ->label('Blokir')
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Blokir Perangkat')
                ->form([
                    \Filament\Forms\Components\Textarea::make('block_reason')
                        ->label('Alasan Pemblokiran')
                        ->required()
                        ->maxLength(255),
                ])
                ->action(function (array $data): void {
                    $this->record->block(auth()->user(), $data['block_reason']);
                    $this->refreshFormData(['is_blocked', 'block_reason', 'blocked_by', 'blocked_at']);
                })
                ->visible(fn (): bool => ! $this->record->is_blocked),
            Actions\Action::make('unblock')
                ->label('Buka Blokir')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->record->unblock();
                    $this->refreshFormData(['is_blocked', 'block_reason', 'blocked_by', 'blocked_at']);
                })
                ->visible(fn (): bool => $this->record->is_blocked),
        ];
    }
}
