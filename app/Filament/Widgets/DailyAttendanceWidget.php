<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Domain\Attendance\Models\AttendanceRaw;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class DailyAttendanceWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Kehadiran Hari Ini';

    public static function canView(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                AttendanceRaw::query()
                    ->with(['employee', 'companyLocation'])
                    ->whereDate('date', now('Asia/Jakarta')->toDateString())
                    ->latest('clock_in')
            )
            ->columns([
                TextColumn::make('employee.name')
                    ->label('Karyawan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('clock_in')
                    ->label('Masuk')
                    ->dateTime('H:i')
                    ->timezone('Asia/Jakarta')
                    ->sortable(),
                TextColumn::make('clock_out')
                    ->label('Keluar')
                    ->dateTime('H:i')
                    ->timezone('Asia/Jakarta')
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('source')
                    ->label('Sumber')
                    ->badge(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
            ])
            ->defaultSort('clock_in', 'desc')
            ->actions([
                \Filament\Tables\Actions\Action::make('viewLocation')
                    ->label('Lihat Lokasi')
                    ->icon('heroicon-o-map-pin')
                    ->color('info')
                    ->modalHeading('Lokasi Kehadiran')
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->visible(fn($record) => $record->company_location_id !== null)
                    ->form(fn($record) => [
                        \App\Filament\Forms\Components\LocationMapPicker::make('location')
                            ->label('')
                            ->latitude($record->companyLocation?->latitude)
                            ->longitude($record->companyLocation?->longitude)
                            ->radius($record->companyLocation?->geofence_radius_meters)
                            ->address($record->companyLocation?->address)
                            ->disabled()
                    ]),
            ])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading('Belum ada data kehadiran')
            ->emptyStateDescription('Data kehadiran hari ini belum tersedia.');
    }
}
