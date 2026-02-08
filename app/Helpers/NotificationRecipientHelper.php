<?php

namespace App\Helpers;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Collection;

class NotificationRecipientHelper
{
    /**
     * Get all HR users in a company
     */
    public static function getHrUsers(int $companyId): Collection
    {
        return User::role('hr')
            ->where('company_id', $companyId)
            ->get();
    }

    /**
     * Get all owner users in a company
     */
    public static function getOwnerUsers(int $companyId): Collection
    {
        return User::role('owner')
            ->where('company_id', $companyId)
            ->get();
    }

    /**
     * Get the user account for an employee
     */
    public static function getEmployeeUser(Employee $employee): ?User
    {
        return $employee->user;
    }

    /**
     * Get all employee users in a company
     */
    public static function getAllEmployeeUsers(int $companyId): Collection
    {
        return User::whereHas('employee', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })->get();
    }

    /**
     * Get all stakeholders (HR + owners) in a company
     */
    public static function getStakeholders(int $companyId): Collection
    {
        return User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['hr', 'owner']);
        })->where('company_id', $companyId)->get();
    }
}
