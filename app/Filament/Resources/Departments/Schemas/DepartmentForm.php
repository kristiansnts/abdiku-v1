<?php

declare(strict_types=1);

namespace App\Filament\Resources\Departments\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;

final class DepartmentForm
{
    public static function configure(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Nama Departemen')
                    ->maxLength(255)
                    ->required(),

                Textarea::make('description')
                    ->label('Deskripsi')
                    ->nullable()
                    ->rows(3)
                    ->maxLength(500),

                Hidden::make('company_id')
                    ->default(fn() => auth()->user()?->company_id),
            ]);
    }
}
