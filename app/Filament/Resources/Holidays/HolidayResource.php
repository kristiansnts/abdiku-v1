<?php

declare(strict_types=1);

namespace App\Filament\Resources\Holidays;

use App\Domain\Leave\Models\Holiday;
use App\Filament\Resources\Holidays\Pages\CreateHoliday;
use App\Filament\Resources\Holidays\Pages\EditHoliday;
use App\Filament\Resources\Holidays\Pages\ListHolidays;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class HolidayResource extends Resource
{
    protected static ?string $model = Holiday::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Pengaturan Kehadiran';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Hari Libur';

    protected static ?string $pluralModelLabel = 'Hari Libur';

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
                Section::make('Informasi Hari Libur')
                    ->schema([
                        DatePicker::make('date')
                            ->label('Tanggal')
                            ->required()
                            ->native(false)
                            ->unique(ignoreRecord: true, modifyRuleUsing: function ($rule) {
                                return $rule->where('company_id', auth()->user()?->company_id);
                            })
                            ->validationMessages([
                                'unique' => 'Tanggal ini sudah terdaftar sebagai hari libur.',
                            ]),

                        TextInput::make('name')
                            ->label('Nama Hari Libur')
                            ->placeholder('contoh: Tahun Baru, Idul Fitri')
                            ->required()
                            ->maxLength(255),

                        Toggle::make('is_paid')
                            ->label('Hari Libur Dibayar')
                            ->helperText('Aktifkan jika hari libur ini tetap dihitung sebagai hari kerja yang dibayar')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('name')
                    ->label('Nama Hari Libur')
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),

                IconColumn::make('is_paid')
                    ->label('Dibayar')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->alignCenter(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
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
            'index' => ListHolidays::route('/'),
            'create' => CreateHoliday::route('/create'),
            'edit' => EditHoliday::route('/{record}/edit'),
        ];
    }
}
