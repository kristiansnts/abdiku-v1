<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Attendance\Models\ShiftPolicy;
use App\Domain\Attendance\Models\WorkPattern;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'logo_path',
        'npwp',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(CompanyLocation::class);
    }

    public function shiftPolicies(): HasMany
    {
        return $this->hasMany(ShiftPolicy::class);
    }

    public function workPatterns(): HasMany
    {
        return $this->hasMany(WorkPattern::class);
    }
}
