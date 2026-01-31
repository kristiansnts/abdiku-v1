<?php

namespace App\Domain\Payroll\Policies;

use App\Models\User;

class PayrollAuthorityPolicy
{
    public function finalize(User $user): bool
    {
        return $user->hasRole('owner');
    }

    public function prepare(User $user): bool
    {
        return $user->hasRole('hr');
    }
}
