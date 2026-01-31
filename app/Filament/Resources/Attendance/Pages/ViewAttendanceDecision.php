<?php

declare(strict_types=1);

namespace App\Filament\Resources\Attendance\Pages;

use App\Filament\Resources\Attendance\AttendanceDecisionResource;
use Filament\Resources\Pages\ViewRecord;

final class ViewAttendanceDecision extends ViewRecord
{
    protected static string $resource = AttendanceDecisionResource::class;
}
