<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payroll\Pages;

use App\Filament\Resources\Payroll\OverrideRequestResource;
use Filament\Resources\Pages\ViewRecord;

final class ViewOverrideRequest extends ViewRecord
{
    protected static string $resource = OverrideRequestResource::class;
}
