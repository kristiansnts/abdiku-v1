<?php

declare(strict_types=1);

namespace App\Filament\Resources\Attendance;

use App\Domain\Attendance\Models\AttendanceRaw;
use App\Filament\Resources\Attendance\Pages\ListAttendanceRecords;
use App\Filament\Resources\Attendance\Pages\ViewAttendanceRecord;
use App\Filament\Resources\Attendance\Tables\AttendanceRecordsTable;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class AttendanceRecordResource extends Resource
{
    protected static ?string $model = AttendanceRaw::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'Penggajian';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Jadwal Kehadiran';

    protected static ?string $pluralModelLabel = 'Jadwal Kehadiran';

    protected static ?string $slug = 'attendance-records';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', auth()->user()?->company_id);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return AttendanceRecordsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAttendanceRecords::route('/'),
            'view' => ViewAttendanceRecord::route('/{record}'),
        ];
    }
}
