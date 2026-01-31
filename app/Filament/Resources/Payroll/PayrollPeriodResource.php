<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payroll;

use App\Domain\Payroll\Models\PayrollPeriod;
use App\Filament\Resources\Payroll\Pages\CreatePayrollPeriod;
use App\Filament\Resources\Payroll\Pages\ListPayrollPeriods;
use App\Filament\Resources\Payroll\Pages\ViewPayrollPeriod;
use App\Filament\Resources\Payroll\Schemas\PayrollPeriodForm;
use App\Filament\Resources\Payroll\Tables\PayrollPeriodsTable;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class PayrollPeriodResource extends Resource
{
    protected static ?string $model = PayrollPeriod::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Penggajian';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Periode Gaji';

    protected static ?string $pluralModelLabel = 'Periode Gaji';

    protected static ?string $slug = 'payroll-periods';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', auth()->user()?->company_id);
    }

    public static function form(Form $form): Form
    {
        return PayrollPeriodForm::configure($form);
    }

    public static function table(Table $table): Table
    {
        return PayrollPeriodsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayrollPeriods::route('/'),
            'create' => CreatePayrollPeriod::route('/create'),
            'view' => ViewPayrollPeriod::route('/{record}'),
        ];
    }
}
