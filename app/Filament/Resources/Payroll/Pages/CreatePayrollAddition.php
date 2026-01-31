<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payroll\Pages;

use App\Filament\Resources\Payroll\PayrollAdditionResource;
use Filament\Resources\Pages\CreateRecord;

final class CreatePayrollAddition extends CreateRecord
{
    protected static string $resource = PayrollAdditionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }
}