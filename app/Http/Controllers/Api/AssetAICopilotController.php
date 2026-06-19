<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AssetAIContextService;
use App\Services\GeminiAssetService;
use App\Services\GroqAIService;
use Illuminate\Http\Request;

class AssetAICopilotController extends Controller
{
    public function askGemini(Request $request, AssetAIContextService $contextService, GeminiAssetService $gemini)
    {
        $request->validate([
            'message' => 'required|string|max:5000'
        ]);

        $context = $contextService->build();

        $response = $gemini->ask(
            $request->message,
            $context
        );

        return response()->json([
            'message' => $response,
            'assets_indexed' => $context['assets']['total'],
            'stats' => $context['dashboard']
        ]);
    }

    public function askGroq(Request $request, AssetAIContextService $contextService, GroqAIService $groq)
    {
        $request->validate([
            'message' => 'required|string|max:5000',
            'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240'
        ]);

        $context = $contextService->build();
        $companyId = $context['company_id']; // already resolved in your context service

        try {
            $response = $groq->ask($request->message, $context, $companyId);
            return response()->json([
                'message' => $response,
                'assets_indexed' => $context['assets']['total'],
                'stats' => $context['dashboard']
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'AI service error', 'error' => $e->getMessage()], 500);
        }
    }

    public function dashboard(AssetAIContextService $contextService)
    {
        $context = $contextService->build();

        return response()->json([
            'success' => true,
            'data' => [
                'assets_indexed' => $context['assets']['total'],
                'dashboard' => $context['dashboard'],
                'suggestions' => $context['suggested_questions']
            ]
        ]);
    }
}