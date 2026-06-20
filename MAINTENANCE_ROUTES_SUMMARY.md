# Maintenance Routes Summary

## Quick Reference

All routes are prefixed with `/api/maintenance` and require authentication.

| Method | Endpoint | Controller Method | Description |
|--------|----------|------------------|-------------|
| GET | `/api/maintenance` | `index` | Get all maintenance records (paginated, filterable) |
| GET | `/api/maintenance/statistics` | `statistics` | Get maintenance statistics and analytics |
| GET | `/api/maintenance/upcoming` | `upcoming` | Get upcoming scheduled maintenance |
| GET | `/api/maintenance/asset/{asset_id}` | `getByAsset` | Get all maintenance for a specific asset |
| GET | `/api/maintenance/{id}` | `show` | Get single maintenance record details |
| POST | `/api/maintenance` | `store` | Create new maintenance record |
| PUT/PATCH | `/api/maintenance/{id}` | `update` | Update maintenance record |
| DELETE | `/api/maintenance/{id}` | `destroy` | Delete maintenance record |
| POST | `/api/maintenance/bulk-delete` | `bulkDelete` | Delete multiple maintenance records |

## Key Features Implemented

### ✅ Enhanced Controller Features
1. **Comprehensive Filtering**
   - Filter by asset, type, date range, performer
   - General search across multiple fields
   - Sorting by any field

2. **Pagination Support**
   - Configurable items per page
   - Full Laravel pagination with links

3. **Asset Relationship**
   - Eager loading of asset details
   - Asset validation (ensures asset belongs to user's company)

4. **Statistics Dashboard**
   - Total maintenance count and cost
   - Breakdown by maintenance type
   - Monthly trends
   - Top assets by maintenance frequency

5. **Upcoming Maintenance**
   - Configurable look-ahead period
   - Sorted by date

6. **Bulk Operations**
   - Bulk delete for efficiency

7. **Security**
   - Company-level data isolation
   - Authorization checks on all operations

### ✅ Model Enhancements
1. **Type Casting**
   - Date casting for maintenance_date
   - Decimal casting for cost

2. **Relationships**
   - Asset relationship (belongsTo)
   - Creator relationship (belongsTo User)
   - Company relationship (belongsTo)

### ✅ Validation
- Maintenance types: `preventive`, `corrective`, `emergency`, `routine`, `inspection`
- Required fields properly validated
- Cost must be non-negative
- Asset existence verified
- Company ownership verified

## Changes Made

### 1. MaintenanceController.php
**Previous:** Basic CRUD with only 3 methods
- `getByAsset()` - Get maintenance by asset
- `store()` - Create maintenance
- `destroy()` - Delete maintenance

**Updated:** Full-featured RESTful API with 9 methods
- Added `index()` - List all with filtering/pagination
- Added `show()` - Get single record
- Added `update()` - Update existing record
- Added `statistics()` - Analytics dashboard
- Added `upcoming()` - Scheduled maintenance
- Added `bulkDelete()` - Batch operations
- Enhanced `store()` with better validation
- Enhanced `getByAsset()` with company filtering
- Enhanced `destroy()` with company filtering

### 2. Maintenance Model
**Previous:** Basic fillable and single relationship

**Updated:** Enhanced with casting and additional relationships
- Added date and decimal casting
- Added `creator()` relationship
- Added `company()` relationship

### 3. Routes (api.php)
**Previous:** Non-standard routing
```php
Route::prefix('assetmaster/maintenance')->group(function () {
    Route::get('/{asset_id}', [MaintenanceController::class, 'getByAsset']);
    Route::post('/create', [MaintenanceController::class, 'store']);
    Route::delete('/delete/{id}', [MaintenanceController::class, 'destroy']);
});
```

**Updated:** RESTful routing with all CRUD operations
```php
Route::prefix('maintenance')->group(function () {
    Route::get('/', [MaintenanceController::class, 'index']);
    Route::get('/statistics', [MaintenanceController::class, 'statistics']);
    Route::get('/upcoming', [MaintenanceController::class, 'upcoming']);
    Route::get('/asset/{asset_id}', [MaintenanceController::class, 'getByAsset']);
    Route::get('/{id}', [MaintenanceController::class, 'show']);
    Route::post('/', [MaintenanceController::class, 'store']);
    Route::put('/{id}', [MaintenanceController::class, 'update']);
    Route::patch('/{id}', [MaintenanceController::class, 'update']);
    Route::delete('/{id}', [MaintenanceController::class, 'destroy']);
    Route::post('/bulk-delete', [MaintenanceController::class, 'bulkDelete']);
});
```

### 4. Bug Fixes
- Fixed duplicate routes (removed conflicting maintenance-contracts routes)
- Fixed LicenseController syntax error (missing comma)

## Frontend Integration Tips

### 1. List Page with Filters
```javascript
// Get maintenance records with filters
const params = new URLSearchParams({
  asset_id: 5,
  type: 'preventive',
  date_from: '2026-01-01',
  date_to: '2026-12-31',
  search: 'oil change',
  sort_by: 'maintenance_date',
  sort_order: 'desc',
  per_page: 20,
  page: 1
});

const response = await fetch(`/api/maintenance?${params}`, {
  headers: { 'Authorization': `Bearer ${token}` }
});
```

### 2. Statistics Dashboard
```javascript
// Get statistics for current year
const response = await fetch('/api/maintenance/statistics?year=2026', {
  headers: { 'Authorization': `Bearer ${token}` }
});

// Get statistics for specific asset
const response = await fetch('/api/maintenance/statistics?asset_id=5', {
  headers: { 'Authorization': `Bearer ${token}` }
});
```

### 3. Asset Details Page
```javascript
// Show all maintenance for an asset
const response = await fetch(`/api/maintenance/asset/${assetId}`, {
  headers: { 'Authorization': `Bearer ${token}` }
});
```

### 4. Upcoming Maintenance Widget
```javascript
// Get maintenance due in next 7 days
const response = await fetch('/api/maintenance/upcoming?days=7', {
  headers: { 'Authorization': `Bearer ${token}` }
});
```

### 5. Create Maintenance Form
```javascript
const maintenanceData = {
  asset_id: 5,
  maintenance_date: '2026-06-20',
  type: 'preventive',
  description: 'Regular maintenance',
  cost: 150.00,
  performed_by: 'John Doe'
};

const response = await fetch('/api/maintenance', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify(maintenanceData)
});
```

## Testing Checklist

- [ ] Test listing with pagination
- [ ] Test filtering by asset
- [ ] Test filtering by type
- [ ] Test filtering by date range
- [ ] Test search functionality
- [ ] Test sorting
- [ ] Test creating maintenance record
- [ ] Test updating maintenance record
- [ ] Test deleting maintenance record
- [ ] Test bulk delete
- [ ] Test statistics endpoint
- [ ] Test upcoming maintenance
- [ ] Test company isolation (users can only see their company's data)
- [ ] Test asset validation (can't create maintenance for other company's assets)

## Documentation

See `MAINTENANCE_API_DOCUMENTATION.md` for detailed API documentation including:
- Request/response examples
- Validation rules
- Error responses
- JavaScript usage examples
