<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payroll\Pages;

use App\Filament\Resources\Payroll\PayrollBatchResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

final class ViewPayrollBatch extends ViewRecord
{
    protected static string $resource = PayrollBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
