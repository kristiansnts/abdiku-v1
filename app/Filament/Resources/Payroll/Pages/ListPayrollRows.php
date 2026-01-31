<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payroll\Pages;

use App\Filament\Resources\Payroll\PayrollRowResource;
use Filament\Resources\Pages\ListRecords;

final class ListPayrollRows extends ListRecords
{
    protected static string $resource = PayrollRowResource::class;
}
