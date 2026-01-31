<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Exceptions;

use App\Domain\Payroll\Enums\PayrollState;
use Exception;

class InvalidPayrollStateException extends Exception
{
    public function __construct(
        public readonly PayrollState $currentState,
        public readonly PayrollState $requiredState,
    ) {
        parent::__construct(
            "Payroll is in state [{$currentState->value}], but [{$requiredState->value}] is required."
        );
    }
}
