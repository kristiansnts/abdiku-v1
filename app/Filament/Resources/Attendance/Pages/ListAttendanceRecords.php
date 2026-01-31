<?php

declare(strict_types=1);

namespace App\Filament\Resources\Attendance\Pages;

use App\Filament\Resources\Attendance\AttendanceRecordResource;
use Filament\Resources\Pages\ListRecords;

final class ListAttendanceRecords extends ListRecords
{
    protected static string $resource = AttendanceRecordResource::class;
}
