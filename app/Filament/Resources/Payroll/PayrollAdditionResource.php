<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payroll;

use App\Domain\Payroll\Models\PayrollAddition;
use App\Filament\Resources\Payroll\Pages\CreatePayrollAddition;
use App\Filament\Resources\Payroll\Pages\EditPayrollAddition;
use App\Filament\Resources\Payroll\Pages\ListPayrollAdditions;
use App\Filament\Resources\Payroll\Pages\ViewPayrollAddition;
use App\Filament\Resources\Payroll\Schemas\PayrollAdditionForm;
use App\Filament\Resources\Payroll\Tables\PayrollAdditionsTable;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class PayrollAdditionResource extends Resource
{
    protected static ?string $model = PayrollAddition::class;

    protected static ?string $navigationIcon = 'heroicon-o-plus-circle';

    protected static ?string $navigationGroup = 'Penggajian';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'Tambahan Gaji';

    protected static ?string $pluralModelLabel = 'Tambahan Gaji';

    protected static ?string $slug = 'payroll-additions';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['employee', 'period', 'creator'])
            ->whereHas('employee', function (Builder $query) {
                $query->where('company_id', auth()->user()?->company_id);
            });
    }

    public static function form(Form $form): Form
    {
        return PayrollAdditionForm::configure($form);
    }

    public static function table(Table $table): Table
    {
        return PayrollAdditionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayrollAdditions::route('/'),
            'create' => CreatePayrollAddition::route('/create'),
            'view' => ViewPayrollAddition::route('/{record}'),
            'edit' => EditPayrollAddition::route('/{record}/edit'),
        ];
    }
}