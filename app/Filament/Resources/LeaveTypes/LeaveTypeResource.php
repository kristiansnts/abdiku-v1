<?php

declare(strict_types=1);

namespace App\Filament\Resources\LeaveTypes;

use App\Domain\Leave\Models\LeaveType;
use App\Filament\Resources\LeaveTypes\Pages\ManageLeaveTypes;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LeaveTypeResource extends Resource
{
    protected static ?string $model = LeaveType::class;

    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';

    protected static ?string $navigationGroup = 'Pengaturan Kehadiran';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'Jenis Cuti';

    protected static ?string $pluralModelLabel = 'Jenis Cuti';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', auth()->user()?->company_id);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Jenis Cuti')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('code')
                            ->label('Kode')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true, modifyRuleUsing: function ($rule) {
                                return $rule->where('company_id', auth()->user()?->company_id);
                            }),

                        Toggle::make('is_paid')
                            ->label('Dibayar')
                            ->default(true),

                        Toggle::make('deduct_from_balance')
                            ->label('Potong Saldo')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('code')
                    ->label('Kode')
                    ->sortable()
                    ->searchable(),

                IconColumn::make('is_paid')
                    ->label('Dibayar')
                    ->boolean()
                    ->alignCenter(),

                IconColumn::make('deduct_from_balance')
                    ->label('Potong Saldo')
                    ->boolean()
                    ->alignCenter(),
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
            'index' => ManageLeaveTypes::route('/'),
        ];
    }
}
