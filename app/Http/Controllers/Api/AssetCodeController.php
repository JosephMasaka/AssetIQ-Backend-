<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AssetCode;
use Illuminate\Http\Request;
use App\Traits\ApiResponser;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Milon\Barcode\Facades\DNS1D;
use Illuminate\Support\Facades\Storage;

class AssetCodeController extends Controller
{
    use ApiResponser;

    /**
     * Display a listing of asset codes.
     */
    public function index(Request $request)
    {
        try {
            $codes = AssetCode::with('asset')
                ->when($request->company_id, fn($q) => $q->where('company_id', $request->company_id))
                ->paginate(20);

            return $this->successResponse($codes, 'Asset codes retrieved successfully');
        } catch (\Exception $e) {
            Log::error('AssetCode index error: '.$e->getMessage());
            return $this->errorResponse('Failed to retrieve asset codes', 500);
        }
    }

    /**
     * Store a new asset code and auto-generate its QR/Barcode.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'asset_id' => ['required', 'exists:assets,id'],
            'code_type' => ['required', 'in:barcode,rfid,qrcode'],
            'code_value' => ['required', 'string', 'unique:asset_codes,code_value'],
            'company_id' => ['required', 'integer'],
        ]);

        try {
            $data['created_by'] = auth()->id() ?? 1;

            $assetCode = AssetCode::create($data);

            // 🔹 Automatically generate code image and store
            $this->generateCodeImage($assetCode);

            return $this->successResponse($assetCode, 'Asset code created and image generated successfully', 201);
        } catch (\Exception $e) {
            Log::error('AssetCode store error: '.$e->getMessage());
            return $this->errorResponse('Failed to create asset code', 500);
        }
    }

    /**
     * Generate QR or Barcode based on type.
     */
    private function generateCodeImage(AssetCode $assetCode)
    {
        $fileName = "{$assetCode->code_type}_{$assetCode->id}.png";
        $filePath = "asset_codes/{$fileName}";

        if ($assetCode->code_type === 'qrcode') {
            $qrImage = QrCode::format('png')->size(250)->generate($assetCode->code_value);
            Storage::disk('public')->put($filePath, $qrImage);
        } elseif ($assetCode->code_type === 'barcode') {
            $barcode = new DNS1D();
            $barcodeImage = $barcode->getBarcodePNG($assetCode->code_value, 'C128');
            Storage::disk('public')->put($filePath, base64_decode($barcodeImage));
        }

        $assetCode->update(['image_path' => $filePath]);
    }

    /**
     * Retrieve QR or Barcode image.
     */
    public function showImage($id)
    {
        $assetCode = AssetCode::findOrFail($id);

        if (!$assetCode->image_path || !Storage::disk('public')->exists($assetCode->image_path)) {
            return $this->error('Image not found or not generated', 404);
        }

        $file = Storage::disk('public')->get($assetCode->image_path);
        return response($file)->header('Content-Type', 'image/png');
    }

    /**
     * Display a specific asset code.
     */
    public function show($id)
    {
        try {
            $code = AssetCode::with('asset')->findOrFail($id);
            return $this->successResponse($code, 'Asset code retrieved successfully');
        } catch (\Exception $e) {
            Log::error('AssetCode show error: '.$e->getMessage());
            return $this->errorResponse('Asset code not found', 404);
        }
    }

    /**
     * Update the specified asset code.
     */
    public function update(Request $request, $id)
    {
        $assetCode = AssetCode::findOrFail($id);

        $data = $request->validate([
            'code_type' => ['sometimes', Rule::in(['barcode', 'rfid', 'qrcode'])],
            'code_value' => [
                'sometimes',
                'string',
                Rule::unique('asset_codes', 'code_value')->ignore($assetCode->id)
            ],
        ]);

        try {
            $assetCode->update($data);
            return $this->successResponse($assetCode, 'Asset code updated successfully');
        } catch (\Exception $e) {
            Log::error('AssetCode update error: '.$e->getMessage());
            return $this->errorResponse('Failed to update asset code', 500);
        }
    }

    /**
     * Remove the specified asset code.
     */
    public function destroy($id)
    {
        try {
            $code = AssetCode::findOrFail($id);
            $code->delete();
            return $this->successResponse(null, 'Asset code deleted successfully');
        } catch (\Exception $e) {
            Log::error('AssetCode destroy error: '.$e->getMessage());
            return $this->errorResponse('Failed to delete asset code', 500);
        }
    }

}
