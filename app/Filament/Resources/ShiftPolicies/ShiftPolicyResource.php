<?php

declare(strict_types=1);

namespace App\Filament\Resources\ShiftPolicies;

use App\Domain\Attendance\Models\ShiftPolicy;
use App\Filament\Resources\ShiftPolicies\Pages\CreateShiftPolicy;
use App\Filament\Resources\ShiftPolicies\Pages\EditShiftPolicy;
use App\Filament\Resources\ShiftPolicies\Pages\ListShiftPolicies;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ShiftPolicyResource extends Resource
{
    protected static ?string $model = ShiftPolicy::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'Pengaturan Kehadiran';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Kebijakan Shift';

    protected static ?string $pluralModelLabel = 'Kebijakan Shift';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', auth()->user()?->company_id);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Shift')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Shift')
                            ->placeholder('contoh: Shift Pagi, Shift Normal')
                            ->required()
                            ->maxLength(255),

                        TimePicker::make('start_time')
                            ->label('Jam Masuk')
                            ->required()
                            ->seconds(false)
                            ->native(false),

                        TimePicker::make('end_time')
                            ->label('Jam Pulang')
                            ->required()
                            ->seconds(false)
                            ->native(false),
                    ])
                    ->columns(3),

                Section::make('Aturan Keterlambatan')
                    ->schema([
                        TextInput::make('late_after_minutes')
                            ->label('Toleransi Keterlambatan (menit)')
                            ->helperText('Karyawan dianggap terlambat setelah melewati batas ini')
                            ->numeric()
                            ->required()
                            ->default(15)
                            ->minValue(0)
                            ->maxValue(120)
                            ->suffix('menit'),

                        TextInput::make('minimum_work_hours')
                            ->label('Jam Kerja Minimum')
                            ->helperText('Minimum jam kerja yang diharapkan per hari')
                            ->numeric()
                            ->required()
                            ->default(8)
                            ->minValue(1)
                            ->maxValue(24)
                            ->suffix('jam'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Shift')
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('start_time')
                    ->label('Jam Masuk')
                    ->time('H:i')
                    ->sortable(),

                TextColumn::make('end_time')
                    ->label('Jam Pulang')
                    ->time('H:i')
                    ->sortable(),

                TextColumn::make('late_after_minutes')
                    ->label('Toleransi')
                    ->suffix(' menit')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('minimum_work_hours')
                    ->label('Jam Kerja Min.')
                    ->suffix(' jam')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('work_assignments_count')
                    ->label('Digunakan')
                    ->counts('workAssignments')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('info'),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make()
                    ->before(function (ShiftPolicy $record) {
                        if ($record->workAssignments()->exists()) {
                            throw new \Exception('Tidak dapat menghapus shift yang masih digunakan oleh karyawan.');
                        }
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListShiftPolicies::route('/'),
            'create' => CreateShiftPolicy::route('/create'),
            'edit' => EditShiftPolicy::route('/{record}/edit'),
        ];
    }
}
