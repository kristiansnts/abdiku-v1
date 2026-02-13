<?php

declare(strict_types=1);

namespace App\Filament\Resources\Employees\Tables;

use Filament\Forms\Components\Select;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

final class EmployeesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee_id')
                    ->label('ID Karyawan')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('name')
                    ->label('Nama')
                    ->sortable()
                    ->searchable()
                    ->description(fn($record) => $record->user?->email),

                TextColumn::make('user.email')
                    ->label('Email/Akun')
                    ->searchable()
                    ->placeholder('Tidak terhubung')
                    ->toggleable(),

                TextColumn::make('user.roles.name')
                    ->label('Role')
                    ->badge()
                    ->color(fn(?string $state): string => match ($state) {
                        'owner' => 'success',
                        'hr' => 'info',
                        'employee' => 'gray',
                        'super_admin', 'super-admin' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(?string $state): string => match ($state) {
                        'owner' => 'Owner',
                        'hr' => 'HR',
                        'employee' => 'Employee',
                        'super_admin', 'super-admin' => 'Super Admin',
                        default => $state ?? 'Tidak ada role',
                    })
                    ->placeholder('Tidak ada role')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'ACTIVE' => 'success',
                        'INACTIVE' => 'warning',
                        'RESIGNED' => 'danger',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'ACTIVE' => 'Aktif',
                        'INACTIVE' => 'Tidak Aktif',
                        'RESIGNED' => 'Resign',
                    })
                    ->sortable(),

                TextColumn::make('ptkp_status')
                    ->label('PTKP')
                    ->placeholder('Belum diatur')
                    ->toggleable(),

                TextColumn::make('join_date')
                    ->label('Tanggal Bergabung')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('resign_date')
                    ->label('Tanggal Resign')
                    ->date('d M Y')
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('company.name')
                    ->label('Perusahaan')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'ACTIVE' => 'Aktif',
                        'INACTIVE' => 'Tidak Aktif',
                        'RESIGNED' => 'Resign',
                    ])
                    ->native(false),

                SelectFilter::make('role')
                    ->label('Role')
                    ->relationship('user.roles', 'name')
                    ->options([
                        'owner' => 'Owner',
                        'hr' => 'HR',
                        'employee' => 'Employee',
                    ])
                    ->native(false)
                    ->preload(),
            ])
            ->actions([
                Action::make('change_role')
                    ->label('Ubah Role')
                    ->icon('heroicon-o-shield-check')
                    ->color('warning')
                    ->visible(fn() => auth()->user()?->hasRole('owner'))
                    ->disabled(fn($record) => !$record->user_id)
                    ->form([
                        Select::make('role')
                            ->label('Role Baru')
                            ->options([
                                'hr' => 'HR',
                                'employee' => 'Employee',
                            ])
                            ->required()
                            ->native(false)
                            ->helperText('Pilih role yang akan diberikan kepada karyawan ini'),
                    ])
                    ->action(function ($record, array $data) {
                        if (!$record->user) {
                            \Filament\Notifications\Notification::make()
                                ->title('Gagal')
                                ->body('Karyawan ini belum memiliki akun pengguna.')
                                ->danger()
                                ->send();
                            return;
                        }

                        DB::transaction(function () use ($record, $data) {
                            // Remove all existing roles
                            $record->user->syncRoles([]);

                            // Assign new role
                            $record->user->assignRole($data['role']);
                        });

                        \Filament\Notifications\Notification::make()
                            ->title('Berhasil')
                            ->body("Role berhasil diubah menjadi {$data['role']}.")
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Ubah Role Karyawan')
                    ->modalDescription('Apakah Anda yakin ingin mengubah role karyawan ini? Perubahan ini akan mempengaruhi hak akses mereka.')
                    ->modalSubmitActionLabel('Ubah Role'),
                ViewAction::make(),
                EditAction::make(),
            ])
            ->defaultSort('join_date', 'desc');
    }
}
