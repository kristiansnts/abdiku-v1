<?php

declare(strict_types=1);

namespace App\Filament\Resources\EmployeeCompensations;

use App\Domain\Payroll\Models\EmployeeCompensation;
use App\Filament\Resources\EmployeeCompensations\Pages\CreateEmployeeCompensation;
use App\Filament\Resources\EmployeeCompensations\Pages\EditEmployeeCompensation;
use App\Filament\Resources\EmployeeCompensations\Pages\ListEmployeeCompensations;
use App\Filament\Resources\EmployeeCompensations\Pages\ViewEmployeeCompensation;
use App\Filament\Resources\EmployeeCompensations\Schemas\EmployeeCompensationForm;
use App\Filament\Resources\EmployeeCompensations\Tables\EmployeeCompensationsTable;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class EmployeeCompensationResource extends Resource
{
    protected static ?string $model = EmployeeCompensation::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Keuangan';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Kompensasi Karyawan';

    protected static ?string $pluralModelLabel = 'Kompensasi Karyawan';

    protected static ?string $slug = 'employee-compensations';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('employee', fn(Builder $query) =>
                $query->where('company_id', auth()->user()?->company_id)
            );
    }

    public static function form(Form $form): Form
    {
        return EmployeeCompensationForm::configure($form);
    }

    public static function table(Table $table): Table
    {
        return EmployeeCompensationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployeeCompensations::route('/'),
            'create' => CreateEmployeeCompensation::route('/create'),
            'edit' => EditEmployeeCompensation::route('/{record}/edit'),
            'view' => ViewEmployeeCompensation::route('/{record}'),
        ];
    }
}
