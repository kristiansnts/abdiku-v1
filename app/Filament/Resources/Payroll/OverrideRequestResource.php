<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payroll;

use App\Domain\Payroll\Models\OverrideRequest;
use App\Filament\Resources\Payroll\Pages\ListOverrideRequests;
use App\Filament\Resources\Payroll\Pages\ViewOverrideRequest;
use App\Filament\Resources\Payroll\Tables\OverrideRequestsTable;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class OverrideRequestResource extends Resource
{
    protected static ?string $model = OverrideRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Penggajian';

    protected static ?int $navigationSort = 6;

    protected static ?string $modelLabel = 'Permintaan Perubahan';

    protected static ?string $pluralModelLabel = 'Permintaan Perubahan';

    protected static ?string $slug = 'override-requests';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas(
                'attendanceDecision.payrollPeriod',
                fn(Builder $query) =>
                $query->where('company_id', auth()->user()?->company_id)
            );
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
        return OverrideRequestsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOverrideRequests::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', 'PENDING')
            ->whereHas(
                'attendanceDecision.payrollPeriod',
                fn(Builder $query) =>
                $query->where('company_id', auth()->user()?->company_id)
            )
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
