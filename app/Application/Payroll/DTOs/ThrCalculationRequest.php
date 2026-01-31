<?php

declare(strict_types=1);

namespace App\Application\Payroll\DTOs;

final readonly class ThrCalculationRequest
{
    public function __construct(
        public int $employeeId,
        public int $periodId,
        public int $companyId,
        public string $employeeType = 'permanent',
        public ?int $workingDaysInYear = 260
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            employeeId: (int) $data['employee_id'],
            periodId: (int) $data['period_id'],
            companyId: (int) $data['company_id'],
            employeeType: $data['employee_type'] ?? 'permanent',
            workingDaysInYear: isset($data['working_days_in_year']) ? (int) $data['working_days_in_year'] : 260
        );
    }

    public function toArray(): array
    {
        return [
            'employee_id' => $this->employeeId,
            'period_id' => $this->periodId,
            'company_id' => $this->companyId,
            'employee_type' => $this->employeeType,
            'working_days_in_year' => $this->workingDaysInYear,
        ];
    }
}