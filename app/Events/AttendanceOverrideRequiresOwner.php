<?php

namespace App\Events;

use App\Domain\Payroll\Models\OverrideRequest;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AttendanceOverrideRequiresOwner
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public OverrideRequest $overrideRequest,
        public User $requestedBy
    ) {
    }
}
