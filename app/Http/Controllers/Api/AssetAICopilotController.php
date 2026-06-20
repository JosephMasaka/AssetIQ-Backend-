<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AssetAIContextService;
use App\Services\GeminiAssetService;
use App\Services\GroqAIService;
use App\Services\InvoiceAssetExtractorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AssetAICopilotController extends Controller
{
    public function askGemini(Request $request, AssetAIContextService $contextService, GeminiAssetService $gemini, InvoiceAssetExtractorService $extractor)
    {
        $request->validate([
            'message' => 'required|string|max:5000',
            'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,txt|max:20480'
        ]);

        $context = $contextService->build();
        $companyId = auth()->user()->company_id;
        $userId = auth()->id();

        try {
            // Check if user wants to extract assets from invoice
            $message = $request->message;
            $isAssetExtraction = $this->isAssetExtractionRequest($message);

            // If file is uploaded and it's an asset extraction request
            if ($request->hasFile('file') && $isAssetExtraction) {
                return $this->handleInvoiceAssetExtraction($request->file('file'), $message, $companyId, $userId, $extractor, $context);
            }

            // If file is uploaded but not for asset extraction, include file content in context
            if ($request->hasFile('file')) {
                $fileText = $extractor->extractTextFromFile($request->file('file'));
                $message = $message . "\n\nFile Content:\n" . substr($fileText, 0, 5000); // Limit file content
            }

            // Regular AI query
            $response = $gemini->ask($message, $context);

            return response()->json([
                'message' => $response,
                'assets_indexed' => $context['assets']['total'],
                'stats' => $context['dashboard']
            ]);
        } catch (\Exception $e) {
            Log::error('AI service error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Unable to process your request. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function askGroq(Request $request, AssetAIContextService $contextService, GroqAIService $groq)
    {
        $request->validate([
            'message' => 'required|string|max:5000',
            'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,txt|max:20480'
        ]);

        $context = $contextService->build();
        $companyId = $context['company_id'];

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

    /**
     * Handle invoice asset extraction
     */
    protected function handleInvoiceAssetExtraction($file, string $message, int $companyId, int $userId, InvoiceAssetExtractorService $extractor, array $context)
    {
        try {
            // Extract text from file
            $invoiceText = $extractor->extractTextFromFile($file);

            if (strpos($invoiceText, '[Error') === 0 || strpos($invoiceText, '[Unsupported') === 0) {
                return response()->json([
                    'message' => "Sorry, I couldn't process the uploaded file. Please ensure it's a valid PDF, image, or text file.",
                    'assets_indexed' => $context['assets']['total']
                ]);
            }

            // Extract assets from invoice using AI
            $extractedAssets = $extractor->extractAssetsFromInvoiceText($invoiceText, $companyId, $userId);

            if (empty($extractedAssets)) {
                return response()->json([
                    'message' => "I analyzed the file but couldn't find any assets to extract. Please ensure the document contains asset/item information like product names, descriptions, and prices.",
                    'assets_indexed' => $context['assets']['total']
                ]);
            }

            // Store the assets
            $result = $extractor->storeAssets($extractedAssets);

            // Build response message
            $responseMessage = $this->buildExtractionResponseMessage($result, $file->getClientOriginalName());

            // Update context with new asset count
            $newContext = app(AssetAIContextService::class)->build();

            return response()->json([
                'message' => $responseMessage,
                'assets_indexed' => $newContext['assets']['total'],
                'stats' => $newContext['dashboard'],
                'extraction_result' => [
                    'total_extracted' => count($extractedAssets),
                    'total_created' => $result['total_created'],
                    'total_errors' => $result['total_errors'],
                    'assets' => $result['created'],
                    'errors' => $result['errors']
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Asset extraction error: ' . $e->getMessage());
            return response()->json([
                'message' => "I encountered an error while extracting assets from the invoice: " . $e->getMessage(),
                'assets_indexed' => $context['assets']['total']
            ], 500);
        }
    }

    /**
     * Check if the message is requesting asset extraction
     */
    protected function isAssetExtractionRequest(string $message): bool
    {
        $keywords = [
            'extract asset',
            'import asset',
            'create asset',
            'add asset',
            'onboard asset',
            'upload invoice',
            'process invoice',
            'import invoice',
            'extract from invoice',
            'import from invoice',
            'add from invoice',
            'onboard',
            'bulk import',
            'bulk add',
            'multiple asset',
        ];

        $messageLower = strtolower($message);
        foreach ($keywords as $keyword) {
            if (strpos($messageLower, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build extraction response message
     */
    protected function buildExtractionResponseMessage(array $result, string $filename): string
    {
        $created = $result['total_created'];
        $errors = $result['total_errors'];

        $message = "✅ **Asset Import Complete**\n\n";
        $message .= "I've analyzed the file **{$filename}** and extracted assets from it.\n\n";

        if ($created > 0) {
            $message .= "**Successfully Created:** {$created} asset(s)\n\n";

            // List created assets
            $message .= "**Assets Added:**\n";
            foreach (array_slice($result['created'], 0, 10) as $asset) {
                $message .= "- {$asset->name} ({$asset->asset_code})";
                if ($asset->purchase_cost) {
                    $message .= " - \${$asset->purchase_cost}";
                }
                $message .= "\n";
            }

            if ($created > 10) {
                $remaining = $created - 10;
                $message .= "- ... and {$remaining} more asset(s)\n";
            }
        }

        if ($errors > 0) {
            $message .= "\n**Errors:** {$errors} asset(s) could not be created\n\n";
            $message .= "**Failed Items:**\n";
            foreach (array_slice($result['errors'], 0, 5) as $error) {
                $message .= "- {$error['asset']}: {$error['error']}\n";
            }
        }

        $message .= "\n**Next Steps:**\n";
        $message .= "- Review the imported assets in the Asset Master\n";
        $message .= "- Update any missing information (location, responsible person, etc.)\n";
        $message .= "- Assign assets to users if needed\n";
        $message .= "- Upload images for visual identification\n";

        return $message;
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