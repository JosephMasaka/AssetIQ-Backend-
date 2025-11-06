<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Api\ResellerController; 
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\AssetCategoryController;
use App\Http\Controllers\Api\VendorController;
use App\Http\Controllers\Api\AssetController;
use App\Http\Controllers\Api\AssetAttributeController;
use App\Http\Controllers\Api\AssetCodeController;
use App\Http\Controllers\Api\LicenseController;
use App\Http\Controllers\Api\ComponentController;
use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\Api\MaintenanceController;
use App\Http\Controllers\Api\AssetHistoryController;
use App\Http\Controllers\Api\RequisitionController;
use App\Http\Controllers\Api\RequisitionTypeController;
use App\Http\Controllers\Api\UoMController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Authentication (JWT based)
|--------------------------------------------------------------------------
*/

Route::get('/test', fn() => response()->json(['message' => 'Laravel API is working 🚀']));

// ✅ CSRF route (optional if SPA + cookies)
Route::get('/jwt/csrf-cookie', fn() => response()->json(['csrf_token' => csrf_token()]));

// ------------------------
// Public Auth Routes
// ------------------------
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthenticatedSessionController::class, 'login'])
        ->middleware('throttle:5,1'); // 5 requests per minute
    Route::post('/register', [AuthenticatedSessionController::class, 'register']);
    Route::post('/refresh', [AuthenticatedSessionController::class, 'refresh']);
    Route::post('/login-as', [AuthenticatedSessionController::class, 'loginAs']);
});

// ------------------------
// Protected Auth Routes (JWT middleware)
// ------------------------
Route::middleware('auth:api')->group(function () {
    Route::post('auth/logout', [AuthenticatedSessionController::class, 'logout']);
    Route::post('auth/stop-impersonation', [AuthenticatedSessionController::class, 'logout']);
    // Route::get('/check', [AuthController::class, 'checkAuthenticated']);

    // ✅ Reseller route
    Route::get('/resellers', [ResellerController::class, 'getResellers']);
    Route::post('/reseller/create', [ResellerController::class, 'createReseller']);
    Route::put('/reseller/update', [ResellerController::class, 'updateReseller']);
    Route::delete('/reseller/delete', [ResellerController::class, 'deleteReseller']);

    // ✅ Company route
    Route::get('/companies', [CompanyController::class, 'getCompanies']);
    Route::post('/company/create', [CompanyController::class, 'createCompany']);
    Route::put('/company/update', [CompanyController::class, 'updateCompany']);
    Route::delete('/company/delete', [CompanyController::class, 'deleteCompany']);

    //Asset Category
    Route::get('/assetcategories', [AssetCategoryController::class, 'index']);
    Route::post('/assetcategory/create', [AssetCategoryController::class, 'store']);

    //Vendor
    Route::get('/vendors', [VendorController::class, 'index']);
    Route::post('/vendor/create', [VendorController::class, 'store']);

    //Asset Master
    Route::get('/assetmaster', [AssetController::class, 'index']);
    Route::post('/assetmaster/create', [AssetController::class, 'store']);
    Route::get('/assetmaster/{id}', [AssetController::class, 'show']);

    //Asset Attributes
    Route::get('/assetmaster/attributes/{id}', [AssetAttributeController::class, 'index']);
    Route::post('/assetmaster/attribute/create', [AssetAttributeController::class, 'store']);
    Route::get('/assetmaster/attribute/{id}', [AssetAttributeController::class, 'show']);

    //Asset Code
    Route::apiResource('asset-codes', AssetCodeController::class);

    //License
    Route::get('/assetmaster/license/{asset_id}', [LicenseController::class, 'index']);
    Route::post('/assetmaster/license/create', [LicenseController::class, 'store']);
    // Route::get('/assetmaster/assetmaster/assetmaster/license/view/{id}', [LicenseController::class, 'show']);
    // Route::put('/assetmaster/assetmaster/license/update/{id}', [LicenseController::class, 'update']);
    // Route::delete('/assetmaster/license/delete/{id}', [LicenseController::class, 'destroy']);

    //Components
    Route::get('/assetmaster/component/{asset_id}', [ComponentController::class, 'index']);
    Route::post('/assetmaster/component/create', [ComponentController::class, 'store']);
    // Route::get('/assetmaster/assetmaster/assetmaster/license/view/{id}', [LicenseController::class, 'show']);
    // Route::put('/assetmaster/assetmaster/license/update/{id}', [LicenseController::class, 'update']);
    // Route::delete('/assetmaster/license/delete/{id}', [LicenseController::class, 'destroy']);

    //Files
    Route::get('/assetmaster/file/{asset_id}', [FileController::class, 'index']);
    Route::post('/assetmaster/file/create', [FileController::class, 'store']);
    Route::get('/assetmaster/file/view/{id}', [FileController::class, 'show']);
    Route::delete('/assetmaster/file/delete/{id}', [FileController::class, 'destroy']);

    Route::prefix('assetmaster/maintenance')->group(function () {
        Route::get('/{asset_id}', [MaintenanceController::class, 'getByAsset']);
        Route::post('/create', [MaintenanceController::class, 'store']);
        Route::delete('/delete/{id}', [MaintenanceController::class, 'destroy']);
    });

    //Histories
    Route::get('/assetmaster/histories/{asset_id}', [AssetHistoryController::class, 'index']);

    //Requisition
    Route::get('/requisitions/', [RequisitionController::class, 'index']);
    Route::get('/requisitions/{id}', [RequisitionController::class, 'show']);
    Route::post('/requisition/create', [RequisitionController::class, 'store']);

    //Unit of Measure
    Route::get('/unit-of-measure/', [UoMController::class, 'index']);
    Route::get('/unit-of-measure/{id}', [UoMController::class, 'show']);
    Route::post('/unit-of-measure/create', [UoMController::class, 'store']);

    //Requisition Types
    Route::get('/requisition-types/', [RequisitionTypeController::class, 'index']);
    Route::get('/requisition-type/{id}', [RequisitionTypeController::class, 'show']);
    Route::post('/requisition-type/create', [RequisitionTypeController::class, 'store']);
});
