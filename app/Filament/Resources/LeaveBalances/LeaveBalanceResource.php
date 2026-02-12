<?php

declare(strict_types=1);

namespace App\Filament\Resources\LeaveBalances;

use App\Domain\Leave\Models\LeaveBalance;
use App\Domain\Leave\Models\LeaveType;
use App\Filament\Resources\LeaveBalances\Pages\ManageLeaveBalances;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LeaveBalanceResource extends Resource
{
    protected static ?string $model = LeaveBalance::class;

    protected static ?string $navigationIcon = 'heroicon-o-scale';

    protected static ?string $navigationGroup = 'Manajemen Karyawan';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'Saldo Cuti';

    protected static ?string $pluralModelLabel = 'Saldo Cuti';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('employee', function ($query) {
                $query->where('company_id', auth()->user()?->company_id);
            });
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('employee_id')
                    ->label('Karyawan')
                    ->relationship('employee', 'name', fn(Builder $query) => $query->where('company_id', auth()->user()->company_id))
                    ->searchable()
                    ->required(),

                Select::make('leave_type_id')
                    ->label('Jenis Cuti')
                    ->options(fn() => LeaveType::where('company_id', auth()->user()->company_id)->pluck('name', 'id'))
                    ->required(),

                TextInput::make('year')
                    ->label('Tahun')
                    ->numeric()
                    ->default(now()->year)
                    ->required(),

                TextInput::make('balance')
                    ->label('Saldo (Hari)')
                    ->numeric()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.name')
                    ->label('Karyawan')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('leaveType.name')
                    ->label('Jenis Cuti')
                    ->sortable(),

                TextColumn::make('year')
                    ->label('Tahun')
                    ->sortable(),

                TextColumn::make('balance')
                    ->label('Saldo')
                    ->suffix(' hari')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageLeaveBalances::route('/'),
        ];
    }
}
