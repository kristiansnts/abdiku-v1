<?php

declare(strict_types=1);

namespace App\Filament\Resources\CompensationRules;

use App\Domain\Payroll\Models\PayrollDeductionRule;
use App\Filament\Resources\CompensationRules\Pages\CreateCompensationRule;
use App\Filament\Resources\CompensationRules\Pages\EditCompensationRule;
use App\Filament\Resources\CompensationRules\Pages\ListCompensationRules;
use App\Filament\Resources\CompensationRules\Pages\ViewCompensationRule;
use App\Filament\Resources\CompensationRules\Schemas\CompensationRuleForm;
use App\Filament\Resources\CompensationRules\Tables\CompensationRulesTable;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class CompensationRuleResource extends Resource
{
    protected static ?string $model = PayrollDeductionRule::class;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Aturan Kompensasi';

    protected static ?string $pluralModelLabel = 'Aturan Kompensasi';

    protected static ?string $slug = 'compensation-rules';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', auth()->user()?->company_id);
    }

    public static function form(Form $form): Form
    {
        return CompensationRuleForm::configure($form);
    }

    public static function table(Table $table): Table
    {
        return CompensationRulesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCompensationRules::route('/'),
            'create' => CreateCompensationRule::route('/create'),
            'edit' => EditCompensationRule::route('/{record}/edit'),
            'view' => ViewCompensationRule::route('/{record}'),
        ];
    }
}
