<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payroll\Pages;

use App\Filament\Resources\Payroll\PayrollAdditionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditPayrollAddition extends EditRecord
{
    protected static string $resource = PayrollAdditionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}