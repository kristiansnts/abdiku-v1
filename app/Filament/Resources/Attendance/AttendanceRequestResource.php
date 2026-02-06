<?php

declare(strict_types=1);

namespace App\Filament\Resources\Attendance;

use App\Domain\Attendance\Models\AttendanceRequest;
use App\Filament\Resources\Attendance\Pages\ListAttendanceRequests;
use App\Filament\Resources\Attendance\Pages\ViewAttendanceRequest;
use App\Filament\Resources\Attendance\Tables\AttendanceRequestsTable;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class AttendanceRequestResource extends Resource
{
    protected static ?string $model = AttendanceRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Pengajuan';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Pengajuan Kehadiran';

    protected static ?string $pluralModelLabel = 'Pengajuan Kehadiran';

    protected static ?string $slug = 'attendance-requests';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', auth()->user()?->company_id);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return AttendanceRequestsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAttendanceRequests::route('/'),
            'view' => ViewAttendanceRequest::route('/{record}'),
        ];
    }
}
