<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\QuotationComparisonService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuotationComparisonController extends Controller
{
    protected $service;

    public function __construct(QuotationComparisonService $service)
    {
        $this->service = $service;
    }

    public function compare(Request $request, $requisitionId)
    {
        $user = auth()->user();

        if (!$user) {
            return $this->errorResponse('Unauthenticated', 401);
        }

        try {
            $data = $this->service->compare($requisitionId, $user);

            return response()->json([
                'message' => 'Comparison completed',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
