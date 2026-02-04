<?php

declare(strict_types=1);

namespace App\Filament\Resources\WorkPatterns;

use App\Domain\Attendance\Enums\DayOfWeek;
use App\Domain\Attendance\Models\WorkPattern;
use App\Filament\Resources\WorkPatterns\Pages\CreateWorkPattern;
use App\Filament\Resources\WorkPatterns\Pages\EditWorkPattern;
use App\Filament\Resources\WorkPatterns\Pages\ListWorkPatterns;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WorkPatternResource extends Resource
{
    protected static ?string $model = WorkPattern::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Pengaturan Kehadiran';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Pola Kerja';

    protected static ?string $pluralModelLabel = 'Pola Kerja';

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
                Section::make('Informasi Pola Kerja')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Pola Kerja')
                            ->placeholder('contoh: 5 Hari Kerja, 6 Hari Kerja')
                            ->required()
                            ->maxLength(255),
                    ]),

                Section::make('Hari Kerja')
                    ->description('Pilih hari-hari yang dianggap sebagai hari kerja')
                    ->schema([
                        CheckboxList::make('working_days')
                            ->label('')
                            ->options([
                                1 => DayOfWeek::MONDAY->getLabel() . ' (Senin)',
                                2 => DayOfWeek::TUESDAY->getLabel() . ' (Selasa)',
                                3 => DayOfWeek::WEDNESDAY->getLabel() . ' (Rabu)',
                                4 => DayOfWeek::THURSDAY->getLabel() . ' (Kamis)',
                                5 => DayOfWeek::FRIDAY->getLabel() . ' (Jumat)',
                                6 => DayOfWeek::SATURDAY->getLabel() . ' (Sabtu)',
                                7 => DayOfWeek::SUNDAY->getLabel() . ' (Minggu)',
                            ])
                            ->required()
                            ->columns(4)
                            ->gridDirection('row')
                            ->bulkToggleable()
                            ->default([1, 2, 3, 4, 5]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Pola Kerja')
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('working_days')
                    ->label('Hari Kerja')
                    ->formatStateUsing(function ($state) {
                        if (empty($state)) {
                            return '-';
                        }

                        $days = collect($state)->map(function ($day) {
                            return DayOfWeek::from((int) $day)->getShortLabel();
                        })->implode(', ');

                        return $days;
                    })
                    ->wrap(),

                TextColumn::make('working_days_count')
                    ->label('Jumlah Hari')
                    ->getStateUsing(fn ($record) => count($record->working_days ?? []))
                    ->suffix(' hari')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state <= 5 => 'success',
                        $state == 6 => 'warning',
                        default => 'danger',
                    }),

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
                    ->before(function (WorkPattern $record) {
                        if ($record->workAssignments()->exists()) {
                            throw new \Exception('Tidak dapat menghapus pola kerja yang masih digunakan oleh karyawan.');
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
            'index' => ListWorkPatterns::route('/'),
            'create' => CreateWorkPattern::route('/create'),
            'edit' => EditWorkPattern::route('/{record}/edit'),
        ];
    }
}
