<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfOnboardingNotFinished
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // Jika user login, punya role OWNER/HR, dan perusahaannya belum onboarded
        if ($user && 
            $user->company && 
            !$user->company->is_onboarded && 
            !$request->routeIs('filament.admin.pages.onboarding-wizard') &&
            !$request->routeIs('filament.admin.auth.logout')) {
            
            return redirect()->route('filament.admin.pages.onboarding-wizard');
        }

        return $next($request);
    }
}
