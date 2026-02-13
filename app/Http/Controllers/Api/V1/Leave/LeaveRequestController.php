<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Leave;

use App\Http\Controllers\Controller;
use App\Domain\Leave\Services\CreateLeaveRequestService;
use App\Domain\Leave\Models\LeaveRequest;
use App\Domain\Leave\Models\LeaveBalance;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LeaveRequestController extends Controller
{
    public function __construct(
        protected CreateLeaveRequestService $createService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;
        
        $history = LeaveRequest::with('leaveType')
            ->where('employee_id', $employee->id)
            ->latest()
            ->paginate($request->get('per_page', 10));

        return response()->json($history);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:500',
        ]);

        $employee = $request->user()->employee;
        
        $leaveRequest = $this->createService->execute($employee, $request->all());

        return response()->json([
            'message' => 'Pengajuan cuti berhasil dikirim.',
            'data' => $leaveRequest->load('leaveType'),
        ], 201);
    }

    public function balances(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;
        $year = $request->get('year', now()->year);

        $balances = LeaveBalance::with('leaveType')
            ->where('employee_id', $employee->id)
            ->where('year', $year)
            ->get();

        return response()->json([
            'year' => $year,
            'balances' => $balances,
        ]);
    }
}
