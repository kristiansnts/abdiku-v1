<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payroll\Pages;

use App\Domain\Payroll\Enums\PayrollState;
use App\Filament\Resources\Payroll\PayrollPeriodResource;
use Filament\Resources\Pages\CreateRecord;

final class CreatePayrollPeriod extends CreateRecord
{
    protected static string $resource = PayrollPeriodResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = auth()->user()->company_id;
        $data['state'] = PayrollState::DRAFT;

        return $data;
    }
}
