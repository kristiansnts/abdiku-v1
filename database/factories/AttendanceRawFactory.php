<?php

namespace Database\Factories;

use App\Domain\Attendance\Enums\AttendanceSource;
use App\Domain\Attendance\Enums\AttendanceStatus;
use App\Domain\Attendance\Models\AttendanceRaw;
use App\Models\Company;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceRawFactory extends Factory
{
    protected $model = AttendanceRaw::class;

    public function definition(): array
    {
        $clockIn = $this->faker->dateTimeBetween('-1 month', 'now');
        $clockOut = (clone $clockIn)->modify('+8 hours');

        return [
            'company_id' => Company::factory(),
            'employee_id' => Employee::factory(),
            'date' => $clockIn->format('Y-m-d'),
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'source' => AttendanceSource::MOBILE,
            'status' => AttendanceStatus::APPROVED,
        ];
    }

    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => now()->toDateString(),
            'clock_in' => now()->setTime(8, 0),
            'clock_out' => null,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AttendanceStatus::PENDING,
        ]);
    }

    public function clockedOut(): static
    {
        return $this->state(fn (array $attributes) => [
            'clock_out' => now()->setTime(17, 0),
        ]);
    }

    public function withoutClockOut(): static
    {
        return $this->state(fn (array $attributes) => [
            'clock_out' => null,
        ]);
    }
}
