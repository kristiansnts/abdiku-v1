<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use App\Filament\Resources\Users\Pages\CreateUser;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

final class UserForm
{
    public static function configure(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Nama')
                    ->maxLength(255)
                    ->required(),
                TextInput::make('email')
                    ->maxLength(255)
                    ->unique(ignoreRecord: true, modifyRuleUsing: function ($rule, $get) {
                        return $rule->where('company_id', auth()->user()?->company_id);
                    })
                    ->email()
                    ->required(),
                TextInput::make('password')
                    ->label('Kata Sandi')
                    ->password()
                    ->required(fn($livewire): bool => $livewire instanceof CreateUser)
                    ->revealable(filament()->arePasswordsRevealable())
                    ->rule(Password::default())
                    ->autocomplete('new-password')
                    ->dehydrated(fn($state): bool => filled($state))
                    ->dehydrateStateUsing(fn($state): string => Hash::make($state)),
                Select::make('roles')
                    ->searchable()
                    ->label('Role')
                    ->relationship(
                        'roles',
                        'name',
                        fn($query) => auth()->user()?->hasRole(['super_admin', 'super-admin'])
                        ? $query
                        : $query->whereIn('name', ['owner', 'hr', 'employee'])
                    )
                    ->preload()
                    ->required(),

                Hidden::make('company_id')
                    ->default(fn() => auth()->user()?->company_id),
            ]);
    }
}
