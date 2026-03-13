<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Mail\DemoCredentialsMail;
use App\Models\DemoLead;
use App\Services\DemoSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Throwable;

class DemoRequestController extends Controller
{
    public function __invoke(Request $request, DemoSessionService $service): JsonResponse
    {
        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:100'],
            'email'        => ['required', 'email', 'max:150'],
            'phone'        => ['required', 'string', 'max:20'],
            'company_name' => ['required', 'string', 'max:150'],
        ]);

        // One sandbox per email per week
        $recentLead = DemoLead::where('email', $validated['email'])
            ->where('created_at', '>=', now()->subWeek())
            ->exists();

        if ($recentLead) {
            return response()->json([
                'success' => false,
                'message' => 'Demo untuk email ini sudah dibuat. Silakan cek inbox Anda atau coba lagi minggu depan.',
            ], 429);
        }

        try {
            $result = $service->createSandbox([
                'name'         => $validated['name'],
                'email'        => $validated['email'],
                'company_name' => $validated['company_name'],
            ]);

            DemoLead::create([
                'name'               => $validated['name'],
                'email'              => $validated['email'],
                'phone'              => $validated['phone'],
                'company_name'       => $validated['company_name'],
                'sandbox_company_id' => $result['company']->id,
                'ip_address'         => $request->ip(),
            ]);

            Mail::to($validated['email'])->send(new DemoCredentialsMail(
                ownerName:        $validated['name'],
                ownerEmail:       $validated['email'],
                password:         $result['password'],
                companyName:      $validated['company_name'],
                employeeEmail:    $result['employee_email'],
                employeePassword: $result['employee_password'],
            ));

            return response()->json([
                'success' => true,
                'message' => 'Demo berhasil dibuat! Silakan cek email Anda untuk kredensial login.',
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat sesi demo. Silakan coba lagi.',
            ], 500);
        }
    }
}
