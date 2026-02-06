<?php

declare(strict_types=1);

namespace App\Filament\Resources\Attendance\Pages;

use App\Filament\Resources\Attendance\AttendanceRequestResource;
use Filament\Resources\Pages\ListRecords;

final class ListAttendanceRequests extends ListRecords
{
    protected static string $resource = AttendanceRequestResource::class;
}
