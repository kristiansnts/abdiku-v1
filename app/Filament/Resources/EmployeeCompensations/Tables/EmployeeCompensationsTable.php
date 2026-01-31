<?php

declare(strict_types=1);

namespace App\Filament\Resources\EmployeeCompensations\Tables;

use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class EmployeeCompensationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.name')
                    ->label('Karyawan')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('base_salary')
                    ->label('Gaji Pokok')
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('total_allowances')
                    ->label('Total Tunjangan')
                    ->money('IDR')
                    ->sortable()
                    ->getStateUsing(fn($record) => $record->total_allowances),

                TextColumn::make('total_compensation')
                    ->label('Total Kompensasi')
                    ->money('IDR')
                    ->sortable()
                    ->weight('bold')
                    ->getStateUsing(fn($record) => $record->total_compensation),

                TextColumn::make('effective_from')
                    ->label('Berlaku Dari')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('effective_to')
                    ->label('Berlaku Sampai')
                    ->date('d M Y')
                    ->placeholder('-')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->getStateUsing(fn($record) => $record->isActive()),

                TextColumn::make('creator.name')
                    ->label('Dibuat Oleh')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('effective_from', 'desc')
            ->filters([
                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Aktif',
                        '0' => 'Tidak Aktif',
                    ])
                    ->query(function ($query, $state) {
                        if ($state['value'] === '1') {
                            return $query->whereNull('effective_to');
                        } elseif ($state['value'] === '0') {
                            return $query->whereNotNull('effective_to');
                        }
                    }),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
