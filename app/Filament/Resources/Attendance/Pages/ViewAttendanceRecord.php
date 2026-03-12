<?php

declare(strict_types=1);

namespace App\Filament\Resources\Attendance\Pages;

use App\Filament\Resources\Attendance\AttendanceRecordResource;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

final class ViewAttendanceRecord extends ViewRecord
{
    protected static string $resource = AttendanceRecordResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Kehadiran')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('employee.name')
                            ->label('Karyawan'),
                        TextEntry::make('date')
                            ->label('Tanggal')
                            ->date('d M Y'),
                        TextEntry::make('clock_in')
                            ->label('Jam Masuk')
                            ->dateTime('H:i')
                            ->timezone('Asia/Jakarta'),
                        TextEntry::make('clock_out')
                            ->label('Jam Keluar')
                            ->dateTime('H:i')
                            ->timezone('Asia/Jakarta')
                            ->placeholder('-'),
                        TextEntry::make('source')
                            ->label('Sumber')
                            ->badge(),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge(),
                    ]),
                Section::make('Lokasi Kehadiran')
                    ->visible(fn ($record) => $record->company_location_id !== null || $record->evidences()->where('type', 'GEOLOCATION')->exists())
                    ->schema([
                        TextEntry::make('companyLocation.name')
                            ->label('Nama Lokasi')
                            ->placeholder('Di luar area terdaftar'),
                        TextEntry::make('companyLocation.address')
                            ->label('Alamat')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        ViewEntry::make('map')
                            ->label('')
                            ->view('filament.infolists.entries.location-map')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Riwayat')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Dibuat')
                            ->dateTime('d M Y H:i')
                            ->timezone('Asia/Jakarta'),
                        TextEntry::make('updated_at')
                            ->label('Diperbarui')
                            ->dateTime('d M Y H:i')
                            ->timezone('Asia/Jakarta'),
                    ]),
            ]);
    }
}
