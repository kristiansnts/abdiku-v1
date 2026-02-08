<?php

namespace App\Providers;

use App\Events\AttendanceOverrideRequiresOwner;
use App\Events\AttendanceRequestReviewed;
use App\Events\AttendanceRequestSubmitted;
use App\Events\EmployeeAbsentDetected;
use App\Events\PayrollFinalized;
use App\Events\PayrollPrepared;
use App\Events\PayslipAvailable;
use App\Listeners\NotifyAllOfPayrollFinalized;
use App\Listeners\NotifyEmployeeOfPayslip;
use App\Listeners\NotifyEmployeeOfRequestReview;
use App\Listeners\NotifyHrOfAbsentEmployee;
use App\Listeners\NotifyHrOfAttendanceRequest;
use App\Listeners\NotifyOwnerOfOverrideRequest;
use App\Listeners\NotifyStakeholdersOfPayrollPrepared;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        AttendanceRequestSubmitted::class => [
            NotifyHrOfAttendanceRequest::class,
        ],
        AttendanceRequestReviewed::class => [
            NotifyEmployeeOfRequestReview::class,
        ],
        AttendanceOverrideRequiresOwner::class => [
            NotifyOwnerOfOverrideRequest::class,
        ],
        EmployeeAbsentDetected::class => [
            NotifyHrOfAbsentEmployee::class,
        ],
        PayrollPrepared::class => [
            NotifyStakeholdersOfPayrollPrepared::class,
        ],
        PayrollFinalized::class => [
            NotifyAllOfPayrollFinalized::class,
        ],
        PayslipAvailable::class => [
            NotifyEmployeeOfPayslip::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
