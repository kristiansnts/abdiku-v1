<?php

namespace App\Observers;

use App\Domain\Leave\Models\LeaveType;
use App\Models\Company;

class CompanyObserver
{
    /**
     * Handle the Company "created" event.
     * Automatically create default leave types for the new company.
     */
    public function created(Company $company): void
    {
        LeaveType::createDefaultsForCompany($company->id);
    }
}

