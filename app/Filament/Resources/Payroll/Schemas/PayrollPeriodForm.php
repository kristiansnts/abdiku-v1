<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payroll\Schemas;

use App\Domain\Payroll\Enums\PayrollState;
use App\Domain\Payroll\Models\PayrollPeriod;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;

final class PayrollPeriodForm
{
    public static function configure(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('period_start')
                    ->label('Tanggal Mulai')
                    ->required()
                    ->native(false),
                DatePicker::make('period_end')
                    ->label('Tanggal Selesai')
                    ->required()
                    ->native(false)
                    ->afterOrEqual('period_start'),
                TextInput::make('rule_version')
                    ->label('Versi Aturan')
                    ->default('v1.0')
                    ->required(),
                Placeholder::make('state')
                    ->label('Status Saat Ini')
                    ->content(fn(?PayrollPeriod $record): string => $record?->state?->getLabel() ?? PayrollState::DRAFT->getLabel())
                    ->hiddenOn('create'),
                Placeholder::make('reviewed_at')
                    ->label('Ditinjau Pada')
                    ->content(fn(?PayrollPeriod $record): string => $record?->reviewed_at?->format('Y-m-d H:i:s') ?? '-')
                    ->hiddenOn('create'),
                Placeholder::make('finalized_at')
                    ->label('Difinalisasi Pada')
                    ->content(fn(?PayrollPeriod $record): string => $record?->finalized_at?->format('Y-m-d H:i:s') ?? '-')
                    ->hiddenOn('create'),
            ])
            ->columns(2);
    }
}
