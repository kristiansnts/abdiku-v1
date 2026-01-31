<?php

declare(strict_types=1);

namespace App\Filament\Resources\Attendance\Pages;

use App\Filament\Resources\Attendance\AttendanceDecisionResource;
use Filament\Resources\Pages\ListRecords;

final class ListAttendanceDecisions extends ListRecords
{
    protected static string $resource = AttendanceDecisionResource::class;
}
