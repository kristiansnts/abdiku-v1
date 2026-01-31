<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payroll\Pages;

use App\Filament\Resources\Payroll\PayrollAdditionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

final class ViewPayrollAddition extends ViewRecord
{
    protected static string $resource = PayrollAdditionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}