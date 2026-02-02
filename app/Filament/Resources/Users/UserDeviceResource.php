<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\UserDeviceResource\Pages\ListUserDevices;
use App\Filament\Resources\Users\UserDeviceResource\Pages\ViewUserDevice;
use App\Models\UserDevice;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;

final class UserDeviceResource extends Resource
{
    protected static ?string $model = UserDevice::class;

    protected static ?string $modelLabel = 'Perangkat';

    protected static ?string $pluralModelLabel = 'Perangkat Pengguna';

    protected static ?string $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static ?string $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'device_name';

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'device_name',
            'device_id',
            'user.name',
            'user.email',
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('user', function (Builder $query) {
                $query->where('company_id', auth()->user()?->company_id);
            });
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Pengguna')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('device_name')
                    ->label('Nama Perangkat')
                    ->searchable(),
                Tables\Columns\TextColumn::make('device_model')
                    ->label('Model')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('device_os')
                    ->label('OS')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('app_version')
                    ->label('Versi App')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_blocked')
                    ->label('Diblokir')
                    ->boolean()
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_login_at')
                    ->label('Login Terakhir')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_ip_address')
                    ->label('IP Terakhir')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_blocked')
                    ->label('Status Blokir')
                    ->placeholder('Semua')
                    ->trueLabel('Diblokir')
                    ->falseLabel('Tidak Diblokir'),
                TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->placeholder('Semua')
                    ->trueLabel('Aktif')
                    ->falseLabel('Tidak Aktif'),
                SelectFilter::make('user')
                    ->relationship('user', 'name')
                    ->label('Pengguna')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('block')
                    ->label('Blokir')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Blokir Perangkat')
                    ->modalDescription('Apakah Anda yakin ingin memblokir perangkat ini? Pengguna akan otomatis logout.')
                    ->form([
                        \Filament\Forms\Components\Textarea::make('block_reason')
                            ->label('Alasan Pemblokiran')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->action(function (UserDevice $record, array $data): void {
                        $record->block(auth()->user(), $data['block_reason']);
                    })
                    ->visible(fn (UserDevice $record): bool => ! $record->is_blocked),
                Tables\Actions\Action::make('unblock')
                    ->label('Buka Blokir')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Buka Blokir Perangkat')
                    ->modalDescription('Apakah Anda yakin ingin membuka blokir perangkat ini?')
                    ->action(function (UserDevice $record): void {
                        $record->unblock();
                    })
                    ->visible(fn (UserDevice $record): bool => $record->is_blocked),
                Tables\Actions\Action::make('deactivate')
                    ->label('Nonaktifkan')
                    ->icon('heroicon-o-x-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Nonaktifkan Perangkat')
                    ->modalDescription('Pengguna harus login ulang untuk mengaktifkan perangkat.')
                    ->action(function (UserDevice $record): void {
                        $record->update(['is_active' => false]);
                        $record->user->tokens()->delete();
                    })
                    ->visible(fn (UserDevice $record): bool => $record->is_active && ! $record->is_blocked),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('block_selected')
                    ->label('Blokir Terpilih')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        \Filament\Forms\Components\Textarea::make('block_reason')
                            ->label('Alasan Pemblokiran')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->action(function ($records, array $data): void {
                        foreach ($records as $record) {
                            if (! $record->is_blocked) {
                                $record->block(auth()->user(), $data['block_reason']);
                            }
                        }
                    }),
            ])
            ->defaultSort('last_login_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUserDevices::route('/'),
            'view' => ViewUserDevice::route('/{record}'),
        ];
    }
}
