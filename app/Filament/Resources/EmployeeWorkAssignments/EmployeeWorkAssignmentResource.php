<?php

declare(strict_types=1);

namespace App\Filament\Resources\EmployeeWorkAssignments;

use App\Domain\Attendance\Models\EmployeeWorkAssignment;
use App\Domain\Attendance\Models\ShiftPolicy;
use App\Domain\Attendance\Models\WorkPattern;
use App\Filament\Resources\EmployeeWorkAssignments\Pages\CreateEmployeeWorkAssignment;
use App\Filament\Resources\EmployeeWorkAssignments\Pages\EditEmployeeWorkAssignment;
use App\Filament\Resources\EmployeeWorkAssignments\Pages\ListEmployeeWorkAssignments;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EmployeeWorkAssignmentResource extends Resource
{
    protected static ?string $model = EmployeeWorkAssignment::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Pengaturan Kehadiran';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Penugasan Karyawan';

    protected static ?string $pluralModelLabel = 'Penugasan Karyawan';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('employee', function (Builder $query) {
                $query->where('company_id', auth()->user()?->company_id);
            })
            ->with(['employee', 'shiftPolicy', 'workPattern']);
    }

    public static function form(Form $form): Form
    {
        $companyId = auth()->user()?->company_id;

        return $form
            ->schema([
                Section::make('Pilih Karyawan')
                    ->schema([
                        Select::make('employee_id')
                            ->label('Karyawan')
                            ->relationship(
                                'employee',
                                'name',
                                fn (Builder $query) => $query
                                    ->where('company_id', $companyId)
                                    ->where('status', 'ACTIVE')
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false)
                            ->helperText('Pilih karyawan yang akan diatur shift dan pola kerjanya'),
                    ]),

                Section::make('Pengaturan Shift & Pola Kerja')
                    ->schema([
                        Select::make('shift_policy_id')
                            ->label('Kebijakan Shift')
                            ->options(fn () => ShiftPolicy::where('company_id', $companyId)
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false)
                            ->helperText('Aturan jam masuk, pulang, dan keterlambatan'),

                        Select::make('work_pattern_id')
                            ->label('Pola Kerja')
                            ->options(fn () => WorkPattern::where('company_id', $companyId)
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false)
                            ->helperText('Hari-hari kerja dalam seminggu'),
                    ])
                    ->columns(2),

                Section::make('Periode Berlaku')
                    ->schema([
                        DatePicker::make('effective_from')
                            ->label('Mulai Berlaku')
                            ->required()
                            ->default(now())
                            ->native(false),

                        DatePicker::make('effective_to')
                            ->label('Berakhir')
                            ->native(false)
                            ->helperText('Kosongkan jika masih berlaku'),

                        Placeholder::make('status')
                            ->label('Status')
                            ->content(function ($record) {
                                if (!$record) {
                                    return 'Baru';
                                }
                                return $record->isActive() ? 'Aktif' : 'Tidak Aktif';
                            })
                            ->hiddenOn('create'),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.name')
                    ->label('Karyawan')
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('shiftPolicy.name')
                    ->label('Kebijakan Shift')
                    ->sortable()
                    ->searchable()
                    ->description(fn ($record) => $record->shiftPolicy
                        ? $record->shiftPolicy->start_time->format('H:i') . ' - ' . $record->shiftPolicy->end_time->format('H:i')
                        : null
                    ),

                TextColumn::make('workPattern.name')
                    ->label('Pola Kerja')
                    ->sortable()
                    ->searchable()
                    ->description(fn ($record) => $record->workPattern
                        ? $record->workPattern->working_days_count . ' hari kerja'
                        : null
                    ),

                TextColumn::make('effective_from')
                    ->label('Mulai Berlaku')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('effective_to')
                    ->label('Berakhir')
                    ->date('d M Y')
                    ->sortable()
                    ->placeholder('Masih berlaku')
                    ->color(fn ($state) => $state ? 'warning' : 'success'),

                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->getStateUsing(fn ($record) => $record->isActive()),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('effective_from', 'desc')
            ->filters([
                SelectFilter::make('shift_policy_id')
                    ->label('Kebijakan Shift')
                    ->relationship('shiftPolicy', 'name', fn (Builder $query) => $query
                        ->where('company_id', auth()->user()?->company_id))
                    ->native(false),

                SelectFilter::make('work_pattern_id')
                    ->label('Pola Kerja')
                    ->relationship('workPattern', 'name', fn (Builder $query) => $query
                        ->where('company_id', auth()->user()?->company_id))
                    ->native(false),

                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Aktif',
                        '0' => 'Tidak Aktif',
                    ])
                    ->query(function (Builder $query, array $state) {
                        if ($state['value'] === '1') {
                            return $query->whereNull('effective_to');
                        } elseif ($state['value'] === '0') {
                            return $query->whereNotNull('effective_to');
                        }
                    })
                    ->native(false),
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
            'index' => ListEmployeeWorkAssignments::route('/'),
            'create' => CreateEmployeeWorkAssignment::route('/create'),
            'edit' => EditEmployeeWorkAssignment::route('/{record}/edit'),
        ];
    }
}
