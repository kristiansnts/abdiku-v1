<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payroll\Pages;

use App\Filament\Resources\Payroll\PayrollRowResource;
use Filament\Resources\Pages\ViewRecord;

final class ViewPayrollRow extends ViewRecord
{
    protected static string $resource = PayrollRowResource::class;
}
