<?php

declare(strict_types=1);

namespace App\Filament\Resources\Attendance;

use App\Domain\Attendance\Models\AttendanceDecision;
use App\Filament\Resources\Attendance\Pages\ListAttendanceDecisions;
use App\Filament\Resources\Attendance\Pages\ViewAttendanceDecision;
use App\Filament\Resources\Attendance\Tables\AttendanceDecisionsTable;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class AttendanceDecisionResource extends Resource
{
    protected static ?string $model = AttendanceDecision::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Penggajian';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'Keputusan Kehadiran';

    protected static ?string $pluralModelLabel = 'Keputusan Kehadiran';

    protected static ?string $slug = 'attendance-decisions';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('payrollPeriod', fn(Builder $query) => $query->where('company_id', auth()->user()?->company_id));
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
        return AttendanceDecisionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAttendanceDecisions::route('/'),
            'view' => ViewAttendanceDecision::route('/{record}'),
        ];
    }
}
