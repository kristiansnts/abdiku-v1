<?php

declare(strict_types=1);

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmployeeExists
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->employee) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'EMPLOYEE_NOT_FOUND',
                    'message' => 'User does not have an associated employee record.',
                ],
            ], 403);
        }

        return $next($request);
    }
}
