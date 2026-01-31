<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payroll;

use App\Domain\Attendance\Models\AttendanceOverride;
use App\Filament\Resources\Payroll\Pages\ListPayrollOverrides;
use App\Filament\Resources\Payroll\Pages\ViewPayrollOverride;
use App\Filament\Resources\Payroll\Tables\PayrollOverridesTable;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class PayrollOverrideResource extends Resource
{
    protected static ?string $model = AttendanceOverride::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static ?string $navigationGroup = 'Penggajian';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'Penyesuaian';

    protected static ?string $pluralModelLabel = 'Penyesuaian';

    protected static ?string $slug = 'payroll-overrides';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('attendanceDecision.payrollPeriod', fn(Builder $query) => $query->where('company_id', auth()->user()?->company_id));
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
        return PayrollOverridesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayrollOverrides::route('/'),
        ];
    }
}
