<?php

declare(strict_types=1);

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveCompanyContext
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $companyId = $request->header('X-Active-Company-Id');

        if (!$companyId && $request->user()) {
            // Fallback to primary company if only one exists and header is missing
            $companies = $request->user()->companies;
            if ($companies->count() === 1) {
                $companyId = $companies->first()->id;
            }
        }

        if (!$companyId && $request->user()) {
             return response()->json([
                'success' => false,
                'message' => 'Konteks perusahaan tidak ditemukan. Silakan pilih perusahaan terlebih dahulu.',
                'code' => 'MISSING_COMPANY_CONTEXT'
            ], 422);
        }

        if ($companyId) {
            // Verify user belongs to this company
            $belongs = $request->user()->companies()->where('companies.id', $companyId)->exists();
            
            if (!$belongs) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses ke perusahaan ini.',
                ], 403);
            }

            // Bind to request for easy access in controllers
            $request->merge(['active_company_id' => (int) $companyId]);
            
            // Set global app context (optional, but useful for scopes)
            app()->instance('active_company_id', (int) $companyId);
        }

        return $next($request);
    }
}
