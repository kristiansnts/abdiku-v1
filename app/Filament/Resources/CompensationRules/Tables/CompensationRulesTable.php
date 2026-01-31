<?php

declare(strict_types=1);

namespace App\Filament\Resources\CompensationRules\Tables;

use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class CompensationRulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('name')
                    ->label('Nama Aturan')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('basis_type')
                    ->label('Dasar Perhitungan')
                    ->badge()
                    ->sortable(),

                TextColumn::make('employee_rate')
                    ->label('Rate Karyawan')
                    ->suffix('%')
                    ->sortable()
                    ->placeholder('-')
                    ->alignEnd(),

                TextColumn::make('employer_rate')
                    ->label('Rate Perusahaan')
                    ->suffix('%')
                    ->sortable()
                    ->placeholder('-')
                    ->alignEnd(),

                TextColumn::make('salary_cap')
                    ->label('Batas Gaji')
                    ->money('IDR')
                    ->sortable()
                    ->placeholder('-')
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('effective_from')
                    ->label('Berlaku Dari')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('effective_to')
                    ->label('Berlaku Sampai')
                    ->date('d M Y')
                    ->placeholder('Aktif')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->getStateUsing(fn($record) => $record->isActive()),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('effective_from', 'desc')
            ->filters([
                SelectFilter::make('basis_type')
                    ->label('Dasar Perhitungan')
                    ->options([
                        'BASE_SALARY' => 'Base Salary Only',
                        'CAPPED_SALARY' => 'Capped Salary',
                        'GROSS_SALARY' => 'Gross Salary (Base + Allowances)',
                    ]),

                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Aktif',
                        '0' => 'Tidak Aktif',
                    ])
                    ->query(function ($query, $state) {
                        if ($state['value'] === '1') {
                            $now = now()->toDateString();
                            return $query->where('effective_from', '<=', $now)
                                ->where(function ($q) use ($now) {
                                    $q->whereNull('effective_to')
                                        ->orWhere('effective_to', '>=', $now);
                                });
                        } elseif ($state['value'] === '0') {
                            $now = now()->toDateString();
                            return $query->where(function ($q) use ($now) {
                                $q->where('effective_from', '>', $now)
                                    ->orWhere('effective_to', '<', $now);
                            });
                        }
                    }),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
