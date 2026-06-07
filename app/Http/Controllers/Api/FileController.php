<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AssetFile;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class FileController extends Controller
{
    use ApiResponser;

    public function index(Request $request, $asset_id)
    {
        $user = auth()->user();

        if (!$user) return $this->errorResponse('Unauthenticated', 401);

        $canManage = $user->roles()->whereHas('permissions', function ($q) {
            $q->where('name', 'asset:manage');
        })->exists();

        if (!$canManage) {
            return $this->errorResponse('Permission Denied', 403);
        }

        $files = AssetFile::where('asset_id', $asset_id)
            ->where('company_id', $user->getCompany())
            ->get();

        return $this->successResponse($files, 'Files retrieved successfully');
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        if (!$user) return $this->errorResponse('Unauthenticated', 401);

        $canManage = $user->roles()->whereHas('permissions', function ($q) {
            $q->where('name', 'asset:manage');
        })->exists();

        if (!$canManage) {
            return $this->errorResponse('Permission Denied', 403);
        }

        $validated = $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'file' => 'required|file|max:5120', // 5MB max
        ]);

        $path = $request->file('file')->store('assets/files', 'public');

        $file = AssetFile::create([
            'asset_id' => $validated['asset_id'],
            'file_name' => $request->file('file')->getClientOriginalName(),
            'file_path' => 'storage/' . $path,
            'file_type' => $request->file('file')->getClientMimeType(),
            'uploaded_by' => $user->name,
            'company_id' => $user->getCompany(),
            'created_by' => $user->id,
        ]);

        return $this->successResponse($file, 'File uploaded successfully', 201);
    }

    public function destroy($id)
    {
        $file = AssetFile::findOrFail($id);
        if (Storage::disk('public')->exists(str_replace('storage/', '', $file->file_path))) {
            Storage::disk('public')->delete(str_replace('storage/', '', $file->file_path));
        }
        $file->delete();

        return $this->successResponse([], 'File deleted successfully');
    }
}
