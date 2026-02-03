<?php

namespace Database\Factories;

use App\Domain\Attendance\Enums\AttendanceRequestType;
use App\Domain\Attendance\Enums\AttendanceStatus;
use App\Domain\Attendance\Models\AttendanceRaw;
use App\Domain\Attendance\Models\AttendanceRequest;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceRequestFactory extends Factory
{
    protected $model = AttendanceRequest::class;

    public function definition(): array
    {
        $company = Company::factory();
        $employee = Employee::factory()->for($company);
        
        return [
            'employee_id' => $employee,
            'company_id' => $company,
            'attendance_raw_id' => AttendanceRaw::factory()->for($employee, 'employee')->for($company),
            'request_type' => $this->faker->randomElement([
                AttendanceRequestType::LATE,
                AttendanceRequestType::CORRECTION,
                AttendanceRequestType::MISSING
            ]),
            'requested_clock_in_at' => $this->faker->dateTimeThisMonth(),
            'requested_clock_out_at' => $this->faker->optional()->dateTimeThisMonth(),
            'reason' => $this->faker->sentence(10),
            'status' => AttendanceStatus::PENDING,
            'requested_at' => now(),
            'reviewed_by' => null,
            'reviewed_at' => null,
            'review_note' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AttendanceStatus::PENDING,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'review_note' => null,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AttendanceStatus::APPROVED,
            'reviewed_by' => User::factory(),
            'reviewed_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'review_note' => $this->faker->optional()->sentence(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AttendanceStatus::REJECTED,
            'reviewed_by' => User::factory(),
            'reviewed_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'review_note' => $this->faker->sentence(),
        ]);
    }

    public function late(): static
    {
        return $this->state(fn (array $attributes) => [
            'request_type' => AttendanceRequestType::LATE,
            'requested_clock_out_at' => null,
        ]);
    }

    public function correction(): static
    {
        return $this->state(fn (array $attributes) => [
            'request_type' => AttendanceRequestType::CORRECTION,
        ]);
    }

    public function missing(): static
    {
        return $this->state(fn (array $attributes) => [
            'request_type' => AttendanceRequestType::MISSING,
            'attendance_raw_id' => null,
        ]);
    }
}