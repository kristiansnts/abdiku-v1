<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payroll\Pages;

use App\Filament\Resources\Payroll\PayrollBatchResource;
use Filament\Resources\Pages\ListRecords;

final class ListPayrollBatches extends ListRecords
{
    protected static string $resource = PayrollBatchResource::class;
}
