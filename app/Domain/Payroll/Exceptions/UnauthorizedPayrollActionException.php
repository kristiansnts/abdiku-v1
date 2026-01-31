<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Exceptions;

use Exception;

class UnauthorizedPayrollActionException extends Exception
{
    public function __construct(
        public readonly string $action,
        public readonly string $requiredRole,
    ) {
        parent::__construct(
            "User is not authorized to [{$action}]. Required role: [{$requiredRole}]."
        );
    }
}
