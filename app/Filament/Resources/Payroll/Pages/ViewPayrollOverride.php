<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payroll\Pages;

use App\Filament\Resources\Payroll\PayrollOverrideResource;
use Filament\Resources\Pages\ViewRecord;

final class ViewPayrollOverride extends ViewRecord
{
    protected static string $resource = PayrollOverrideResource::class;
}
