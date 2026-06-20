<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser as PdfParser;

class InvoiceAssetExtractorService
{
    protected $geminiService;

    public function __construct(GeminiAssetService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    /**
     * Extract text from uploaded file
     */
    public function extractTextFromFile($file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());

        try {
            switch ($extension) {
                case 'pdf':
                    return $this->extractTextFromPdf($file);
                case 'txt':
                    return file_get_contents($file->getRealPath());
                case 'jpg':
                case 'jpeg':
                case 'png':
                    // For images, return a placeholder - Gemini can handle image analysis
                    return "[Image file: {$file->getClientOriginalName()}]";
                default:
                    return "[Unsupported file type: {$extension}]";
            }
        } catch (\Exception $e) {
            Log::error("File extraction error: " . $e->getMessage());
            return "[Error extracting text from file]";
        }
    }

    /**
     * Extract text from PDF file
     */
    protected function extractTextFromPdf($file): string
    {
        try {
            $parser = new PdfParser();
            $pdf = $parser->parseFile($file->getRealPath());
            $text = $pdf->getText();

            // Clean up the text
            $text = preg_replace('/\s+/', ' ', $text);
            return trim($text);
        } catch (\Exception $e) {
            Log::error("PDF parsing error: " . $e->getMessage());
            return "[Error parsing PDF file]";
        }
    }

    /**
     * Use AI to extract asset data from invoice text
     */
    public function extractAssetsFromInvoiceText(string $invoiceText, int $companyId, int $userId): array
    {
        $prompt = $this->buildExtractionPrompt($invoiceText);

        // Create context for Gemini
        $context = [
            'task' => 'asset_extraction',
            'company_id' => $companyId,
            'user_id' => $userId
        ];

        try {
            $response = $this->geminiService->ask($prompt, $context);
            return $this->parseAIResponse($response, $companyId, $userId);
        } catch (\Exception $e) {
            Log::error("AI extraction error: " . $e->getMessage());
            throw new \Exception("Failed to extract assets from invoice: " . $e->getMessage());
        }
    }

    /**
     * Build the extraction prompt for AI
     */
    protected function buildExtractionPrompt(string $invoiceText): string
    {
        return "
You are an expert asset extraction AI for AssetIQ Asset Management System.

**TASK**: Extract all assets/items from the invoice/document below and format them as JSON.

**REQUIRED OUTPUT FORMAT**: Respond with ONLY a valid JSON array of assets. Each asset must have these fields:

```json
[
  {
    \"name\": \"Asset name from invoice\",
    \"description\": \"Brief description\",
    \"serial_number\": \"Serial/Model number if available or null\",
    \"purchase_cost\": 0.00,
    \"quantity\": 1,
    \"category\": \"Electronics|Furniture|Vehicles|IT Equipment|Machinery|Office Equipment|Other\",
    \"acquisition_date\": \"YYYY-MM-DD or null\"
  }
]
```

**EXTRACTION RULES**:
1. Extract ALL items/assets mentioned in the invoice
2. For each item, determine the most appropriate category from: Electronics, Furniture, Vehicles, IT Equipment, Machinery, Office Equipment, Other
3. Extract quantity for each item
4. Extract unit price as purchase_cost
5. If serial numbers are mentioned, include them
6. If invoice date exists, use it as acquisition_date
7. Combine item description and specifications into the description field
8. If an item has no clear name, use the description as the name
9. All monetary values should be numeric (no currency symbols)
10. Return ONLY the JSON array, no explanations or markdown

**INVOICE TEXT**:
{$invoiceText}

**IMPORTANT**: Respond with ONLY the JSON array starting with [ and ending with ]. No other text.
";
    }

    /**
     * Parse AI response and validate extracted assets
     */
    protected function parseAIResponse(string $response, int $companyId, int $userId): array
    {
        // Clean the response - remove markdown code blocks if present
        $response = preg_replace('/```json\s*/i', '', $response);
        $response = preg_replace('/```\s*$/i', '', $response);
        $response = trim($response);

        try {
            $extractedAssets = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

            if (!is_array($extractedAssets)) {
                throw new \Exception("AI response is not a valid JSON array");
            }

            // Validate and format each asset
            $validatedAssets = [];
            foreach ($extractedAssets as $index => $asset) {
                try {
                    $validatedAsset = $this->validateAndFormatAsset($asset, $companyId, $userId);
                    $validatedAssets[] = $validatedAsset;
                } catch (\Exception $e) {
                    Log::warning("Skipping invalid asset at index {$index}: " . $e->getMessage());
                    // Continue with other assets
                }
            }

            return $validatedAssets;
        } catch (\JsonException $e) {
            Log::error("JSON parsing error: " . $e->getMessage() . "\nResponse: " . $response);
            throw new \Exception("Failed to parse AI response as JSON");
        }
    }

    /**
     * Validate and format a single asset
     */
    protected function validateAndFormatAsset(array $asset, int $companyId, int $userId): array
    {
        // Required fields
        if (empty($asset['name'])) {
            throw new \Exception("Asset name is required");
        }

        // Get or create category
        $categoryId = $this->resolveCategoryId($asset['category'] ?? 'Other', $companyId);

        // Generate asset code
        $assetCode = $this->generateAssetCode($companyId);

        // Format the asset according to Asset model fillable
        return [
            'asset_code' => $assetCode,
            'name' => $asset['name'],
            'asset_img' => null,
            'description' => $asset['description'] ?? null,
            'category_id' => $categoryId,
            'serial_number' => $asset['serial_number'] ?? null,
            'acquisition_date' => $this->formatDate($asset['acquisition_date'] ?? null),
            'purchase_cost' => $this->formatCost($asset['purchase_cost'] ?? 0),
            'location' => $asset['location'] ?? null,
            'responsible_person' => $asset['responsible_person'] ?? null,
            'status' => 'active',
            'lifecycle_status' => 'in_use',
            'warranty_start_date' => $this->formatDate($asset['warranty_start_date'] ?? null),
            'warranty_end_date' => $this->formatDate($asset['warranty_end_date'] ?? null),
            'disposal_date' => null,
            'disposal_method' => null,
            'disposal_value' => null,
            'disposal_notes' => null,
            'disposed_by' => null,
            'retirement_date' => null,
            'retirement_reason' => null,
            'residual_value' => null,
            'salvage_value' => null,
            'useful_life_years' => $asset['useful_life_years'] ?? null,
            'expected_eol_date' => null,
            'company_id' => $companyId,
            'created_by' => $userId,
            'quantity' => $asset['quantity'] ?? 1,
        ];
    }

    /**
     * Resolve category ID from name
     */
    protected function resolveCategoryId(string $categoryName, int $companyId): int
    {
        // Map common category names
        $categoryMap = [
            'electronics' => 'Electronics',
            'furniture' => 'Furniture',
            'vehicles' => 'Vehicles',
            'vehicle' => 'Vehicles',
            'it equipment' => 'IT Equipment',
            'it' => 'IT Equipment',
            'machinery' => 'Machinery',
            'machine' => 'Machinery',
            'office equipment' => 'Office Equipment',
            'office' => 'Office Equipment',
            'other' => 'Other',
        ];

        $normalizedName = strtolower(trim($categoryName));
        $mappedName = $categoryMap[$normalizedName] ?? $categoryName;

        // Try to find existing category
        $category = AssetCategory::where('company_id', $companyId)
            ->where('name', 'like', "%{$mappedName}%")
            ->first();

        if (!$category) {
            // Create new category
            $category = AssetCategory::create([
                'name' => ucwords($mappedName),
                'description' => "Auto-created from invoice import",
                'company_id' => $companyId,
            ]);
        }

        return $category->id;
    }

    /**
     * Generate unique asset code
     */
    protected function generateAssetCode(int $companyId): string
    {
        $prefix = 'AST';
        $year = date('Y');

        // Get the last asset code for this company
        $lastAsset = Asset::where('company_id', $companyId)
            ->where('asset_code', 'like', "{$prefix}-{$year}-%")
            ->orderBy('id', 'desc')
            ->first();

        if ($lastAsset) {
            // Extract the sequence number and increment
            preg_match('/-(\d+)$/', $lastAsset->asset_code, $matches);
            $sequence = isset($matches[1]) ? intval($matches[1]) + 1 : 1;
        } else {
            $sequence = 1;
        }

        return sprintf("%s-%s-%04d", $prefix, $year, $sequence);
    }

    /**
     * Format date string
     */
    protected function formatDate($date): ?string
    {
        if (empty($date) || $date === 'null') {
            return null;
        }

        try {
            return date('Y-m-d', strtotime($date));
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Format cost value
     */
    protected function formatCost($cost): float
    {
        if (is_string($cost)) {
            // Remove currency symbols and commas
            $cost = preg_replace('/[^0-9.]/', '', $cost);
        }

        return floatval($cost);
    }

    /**
     * Store extracted assets to database
     */
    public function storeAssets(array $assets): array
    {
        $created = [];
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($assets as $assetData) {
                try {
                    // Handle quantity - create multiple assets if quantity > 1
                    $quantity = $assetData['quantity'] ?? 1;
                    unset($assetData['quantity']); // Remove quantity from asset data

                    for ($i = 0; $i < $quantity; $i++) {
                        // Generate unique asset code for each item
                        if ($i > 0) {
                            $assetData['asset_code'] = $this->generateAssetCode($assetData['company_id']);
                        }

                        // Add sequence to name if quantity > 1
                        if ($quantity > 1) {
                            $assetData['name'] = $assetData['name'] . " #" . ($i + 1);
                        }

                        $asset = Asset::create($assetData);
                        $created[] = $asset;
                    }
                } catch (\Exception $e) {
                    $errors[] = [
                        'asset' => $assetData['name'] ?? 'Unknown',
                        'error' => $e->getMessage()
                    ];
                    Log::error("Error creating asset: " . $e->getMessage(), ['asset_data' => $assetData]);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception("Failed to store assets: " . $e->getMessage());
        }

        return [
            'created' => $created,
            'errors' => $errors,
            'total_created' => count($created),
            'total_errors' => count($errors)
        ];
    }
}
