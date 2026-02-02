<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Company;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CompanyLocationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyLocationController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $company = $request->user()->company;

        $locations = $company->locations()
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => CompanyLocationResource::collection($locations),
        ]);
    }
}
