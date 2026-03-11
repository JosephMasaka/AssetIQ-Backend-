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
use App\Http\Controllers\Api\QuotationController;
use App\Http\Controllers\Api\QuotationComparisonController;
use App\Http\Controllers\Api\PurchaseOrderController;
use App\Http\Controllers\Api\GoodsReceiptController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\DepreciationRuleController;
use App\Http\Controllers\Api\DepreciationKeyController;
use App\Http\Controllers\Api\GLAccountController;
use App\Http\Controllers\Api\AccountGroupController;
use App\Http\Controllers\Api\CountryController;
use App\Http\Controllers\Api\TaxCodeController;
use App\Http\Controllers\Api\DepreciationAreaController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\PlanModuleController;
use App\Http\Controllers\Api\ModuleController;

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
    Route::options('{any}', function () {
        return response()->json([], 200);
    })->where('any', '.*');
    Route::post('auth/logout', [AuthenticatedSessionController::class, 'logout']);
    Route::post('auth/stop-impersonation', [AuthenticatedSessionController::class, 'logout']);
    // Route::get('/check', [AuthController::class, 'checkAuthenticated']);

    // Plans
    Route::get('/plans', [PlanController::class, 'index']);

    Route::get('/planmodule', [PlanModuleController::class, 'index']);
    Route::post('/planmodule/create', [PlanModuleController::class, 'store']);

    Route::get('/modules', [ModuleController::class, 'index']);

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

    // User
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/user', [UserController::class, 'store']);
    Route::delete('/user/{id}', [UserController::class, 'destroy']);
    Route::post('/user/{id}/reset-password', [UserController::class, 'resetPassword']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']); // main dashboard
    Route::get('/dashboard/purchase-orders', [DashboardController::class, 'purchaseOrders']); // year-based

    // Roles
    Route::get('/roles', [RoleController::class, 'index']);
    Route::post('/role/create', [RoleController::class, 'store']);
    Route::get('/roles/permissions', [RoleController::class, 'permissions']);


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
    Route::post('/requisitions/add-vendors', [RequisitionController::class, 'addVendors']);
    Route::get('/requisitions/vendors/{id}', [RequisitionController::class, 'getVendors']);
    Route::get('/requisitions/vendor/{id}/{vendorId}', [RequisitionController::class, 'getVendor']);
    Route::get('/requisitions/requisition/items/{id}', [RequisitionController::class, 'getRequisitionItems']);
    Route::get('/requisitions/requisition/request-for-quotation/generate/{id}/{vendorId}', [RequisitionController::class, 'generateRFQLinks']);
    Route::get(
        'requisitions/requisition/{id}/rfqs',
        [RequisitionController::class, 'getRequisitionRFQs']
    );
    Route::post('/requisitions/{id}/compare-quotations', [QuotationComparisonController::class, 'compare']);


    //Unit of Measure
    Route::get('/unit-of-measure/', [UoMController::class, 'index']);
    Route::get('/unit-of-measure/{id}', [UoMController::class, 'show']);
    Route::post('/unit-of-measure/create', [UoMController::class, 'store']);

    //Requisition Types
    Route::get('/requisition-types/', [RequisitionTypeController::class, 'index']);
    Route::get('/requisition-type/{id}', [RequisitionTypeController::class, 'show']);
    Route::post('/requisition-type/create', [RequisitionTypeController::class, 'store']);

    //Quotations
    Route::get('/quotations/', [QuotationController::class, 'index']);
    Route::get('/quotation/show/{id}', [QuotationController::class, 'show']);
    Route::post('/quotation/{quotation}/award', [QuotationController::class, 'awardQuotation']);

    //Purchase Order
    Route::get('/purchase-orders/', [PurchaseOrderController::class, 'index']);
    Route::get('/purchase-order/show/{id}', [PurchaseOrderController::class, 'show']);

    //Goods Reciept
    Route::get('/goods-receipts/', [GoodsReceiptController::class, 'index']);
    Route::post('/goods-receipt/', [GoodsReceiptController::class, 'store']);
    Route::get('/goods-receipt/{id}', [GoodsReceiptController::class, 'show']);

    //Invoices
    Route::post('invoices/generate-from-gr/{id}', [InvoiceController::class, 'generateFromGR']);
    Route::get('invoices/', [InvoiceController::class, 'index']);
    Route::get('invoice/{id}', [InvoiceController::class, 'show']);

    //Depreciation Rules
    Route::get('/depreciation-rules/{key_id}', [DepreciationRuleController::class, 'index']);
    Route::post('/depreciation-rules', [DepreciationRuleController::class, 'store']);
    Route::put('/depreciation-rules/{id}', [DepreciationRuleController::class, 'update']);
    Route::delete('/depreciation-rules/{id}', [DepreciationRuleController::class, 'destroy']);

    Route::prefix('gl-mapping')->group(function () {
        Route::get('/gl-accounts', [GLAccountController::class, 'index']);
        Route::post('/gl-accounts/create', [GLAccountController::class, 'store']);
    });

    //Depreciation Areas
    Route::get('/depreciation-areas', [DepreciationAreaController::class, 'index']);
    Route::post('/depreciation-area', [DepreciationAreaController::class, 'store']);
    Route::put('/depreciation-area/{id}', [DepreciationAreaController::class, 'update']);
    Route::delete('/depreciation-area/{id}', [DepreciationAreaController::class, 'destroy']);

    //Depreciation Keys
    Route::get('/depreciation-keys', [DepreciationKeyController::class, 'index']);
    Route::post('/depreciation-key', [DepreciationKeyController::class, 'store']);
    Route::put('/depreciation-key/{id}', [DepreciationKeyController::class, 'update']);
    Route::delete('/depreciation-key/{id}', [DepreciationKeyController::class, 'destroy']);

    //Account Groups
    Route::get('/account-groups', [AccountGroupController::class, 'index']);
    Route::post('/account-group/create', [AccountGroupController::class, 'store']);

    //Tax Codes
    Route::get('/tax-codes', [TaxCodeController::class, 'index']);
    Route::post('/tax-codes/create', [TaxCodeController::class, 'store']);

    //COuntries
    Route::get('/countries', [CountryController::class, 'index']);
});

Route::post('/public/quotations/{public_token}', [QuotationController::class, 'store']);
