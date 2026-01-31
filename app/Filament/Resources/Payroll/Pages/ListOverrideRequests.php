<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payroll\Pages;

use App\Filament\Resources\Payroll\OverrideRequestResource;
use Filament\Resources\Pages\ListRecords;

final class ListOverrideRequests extends ListRecords
{
    protected static string $resource = OverrideRequestResource::class;
}
