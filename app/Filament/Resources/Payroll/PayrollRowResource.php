<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payroll;

use App\Domain\Payroll\Models\PayrollRow;
use App\Filament\Resources\Payroll\Pages\ListPayrollRows;
use App\Filament\Resources\Payroll\Pages\ViewPayrollRow;
use App\Filament\Resources\Payroll\Schemas\PayrollRowForm;
use App\Filament\Resources\Payroll\Tables\PayrollRowsTable;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class PayrollRowResource extends Resource
{
    protected static ?string $model = PayrollRow::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Keuangan';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Baris Gaji';

    protected static ?string $pluralModelLabel = 'Baris Gaji';

    protected static ?string $slug = 'payroll-rows';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('payrollBatch', fn(Builder $query) => $query->where('company_id', auth()->user()?->company_id));
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

    public static function form(Form $form): Form
    {
        return PayrollRowForm::configure($form);
    }

    public static function table(Table $table): Table
    {
        return PayrollRowsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayrollRows::route('/'),
            'view' => ViewPayrollRow::route('/{record}'),
        ];
    }
}
