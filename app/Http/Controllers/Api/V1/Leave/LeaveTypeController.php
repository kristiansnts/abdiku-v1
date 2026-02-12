<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Leave;

use App\Domain\Leave\Models\LeaveType;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class LeaveTypeController extends Controller
{
    /**
     * Get all leave types for the authenticated user's company.
     * 
     * @return JsonResponse
     */
    public function __invoke(): JsonResponse
    {
        $user = Auth::user();
        $companyId = $user->company_id;

        $leaveTypes = LeaveType::where('company_id', $companyId)
            ->orderBy('id')
            ->get(['id', 'name', 'code', 'is_paid', 'deduct_from_balance']);

        return response()->json([
            'data' => $leaveTypes,
        ]);
    }
}
