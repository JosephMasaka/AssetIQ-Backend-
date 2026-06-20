# Maintenance API Documentation

## Base URL
All endpoints are prefixed with: `/api/maintenance`

## Authentication
All endpoints require authentication. Include the Bearer token in the Authorization header:
```
Authorization: Bearer {your-token}
```

---

## Endpoints

### 1. Get All Maintenance Records
**GET** `/api/maintenance`

Get a paginated list of all maintenance records with filtering and sorting options.

#### Query Parameters:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `asset_id` | integer | No | Filter by specific asset ID |
| `type` | string | No | Filter by maintenance type (preventive, corrective, emergency, routine, inspection) |
| `date_from` | date | No | Filter records from this date (YYYY-MM-DD) |
| `date_to` | date | No | Filter records up to this date (YYYY-MM-DD) |
| `performed_by` | string | No | Search by technician/performer name |
| `search` | string | No | General search across description, performed_by, and asset details |
| `sort_by` | string | No | Field to sort by (default: maintenance_date) |
| `sort_order` | string | No | Sort direction: asc or desc (default: desc) |
| `per_page` | integer | No | Items per page (default: 15) |
| `page` | integer | No | Page number |

#### Response:
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "asset_id": 5,
        "maintenance_date": "2026-06-15",
        "type": "preventive",
        "description": "Regular oil change and filter replacement",
        "cost": "150.00",
        "performed_by": "John Doe",
        "company_id": 1,
        "created_by": 2,
        "created_at": "2026-06-15T10:00:00.000000Z",
        "updated_at": "2026-06-15T10:00:00.000000Z",
        "asset": {
          "id": 5,
          "asset_tag": "AST-2024-005",
          "asset_name": "Vehicle - Toyota Hilux"
        }
      }
    ],
    "first_page_url": "http://api.example.com/api/maintenance?page=1",
    "from": 1,
    "last_page": 5,
    "last_page_url": "http://api.example.com/api/maintenance?page=5",
    "next_page_url": "http://api.example.com/api/maintenance?page=2",
    "path": "http://api.example.com/api/maintenance",
    "per_page": 15,
    "prev_page_url": null,
    "to": 15,
    "total": 72
  }
}
```

---

### 2. Get Single Maintenance Record
**GET** `/api/maintenance/{id}`

Get detailed information about a specific maintenance record.

#### URL Parameters:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | Maintenance record ID |

#### Response:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "asset_id": 5,
    "maintenance_date": "2026-06-15",
    "type": "preventive",
    "description": "Regular oil change and filter replacement",
    "cost": "150.00",
    "performed_by": "John Doe",
    "company_id": 1,
    "created_by": 2,
    "created_at": "2026-06-15T10:00:00.000000Z",
    "updated_at": "2026-06-15T10:00:00.000000Z",
    "asset": {
      "id": 5,
      "asset_tag": "AST-2024-005",
      "asset_name": "Vehicle - Toyota Hilux"
    }
  }
}
```

---

### 3. Get Maintenance Records by Asset
**GET** `/api/maintenance/asset/{asset_id}`

Get all maintenance records for a specific asset.

#### URL Parameters:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `asset_id` | integer | Yes | Asset ID |

#### Response:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "asset_id": 5,
      "maintenance_date": "2026-06-15",
      "type": "preventive",
      "description": "Regular oil change and filter replacement",
      "cost": "150.00",
      "performed_by": "John Doe",
      "company_id": 1,
      "created_by": 2,
      "created_at": "2026-06-15T10:00:00.000000Z",
      "updated_at": "2026-06-15T10:00:00.000000Z"
    }
  ]
}
```

---

### 4. Create Maintenance Record
**POST** `/api/maintenance`

Create a new maintenance record.

#### Request Body:
```json
{
  "asset_id": 5,
  "maintenance_date": "2026-06-20",
  "type": "corrective",
  "description": "Fixed brake system issue",
  "cost": 350.50,
  "performed_by": "Jane Smith"
}
```

#### Validation Rules:
| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `asset_id` | integer | Yes | Must exist in assets table |
| `maintenance_date` | date | Yes | Valid date format (YYYY-MM-DD) |
| `type` | string | Yes | One of: preventive, corrective, emergency, routine, inspection |
| `description` | string | No | Text description |
| `cost` | decimal | No | Minimum: 0 |
| `performed_by` | string | No | Max 255 characters |

#### Response:
```json
{
  "success": true,
  "message": "Maintenance record created successfully",
  "data": {
    "id": 25,
    "asset_id": 5,
    "maintenance_date": "2026-06-20",
    "type": "corrective",
    "description": "Fixed brake system issue",
    "cost": "350.50",
    "performed_by": "Jane Smith",
    "company_id": 1,
    "created_by": 2,
    "created_at": "2026-06-19T14:30:00.000000Z",
    "updated_at": "2026-06-19T14:30:00.000000Z",
    "asset": {
      "id": 5,
      "asset_tag": "AST-2024-005",
      "asset_name": "Vehicle - Toyota Hilux"
    }
  }
}
```

---

### 5. Update Maintenance Record
**PUT/PATCH** `/api/maintenance/{id}`

Update an existing maintenance record.

#### URL Parameters:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | Maintenance record ID |

#### Request Body:
```json
{
  "maintenance_date": "2026-06-21",
  "type": "emergency",
  "description": "Emergency brake system repair - updated details",
  "cost": 425.00,
  "performed_by": "Jane Smith & Team"
}
```

#### Validation Rules:
All fields are optional, but if provided must follow the same rules as create.

#### Response:
```json
{
  "success": true,
  "message": "Maintenance record updated successfully",
  "data": {
    "id": 25,
    "asset_id": 5,
    "maintenance_date": "2026-06-21",
    "type": "emergency",
    "description": "Emergency brake system repair - updated details",
    "cost": "425.00",
    "performed_by": "Jane Smith & Team",
    "company_id": 1,
    "created_by": 2,
    "created_at": "2026-06-19T14:30:00.000000Z",
    "updated_at": "2026-06-19T15:45:00.000000Z",
    "asset": {
      "id": 5,
      "asset_tag": "AST-2024-005",
      "asset_name": "Vehicle - Toyota Hilux"
    }
  }
}
```

---

### 6. Delete Maintenance Record
**DELETE** `/api/maintenance/{id}`

Delete a maintenance record.

#### URL Parameters:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | Maintenance record ID |

#### Response:
```json
{
  "success": true,
  "message": "Maintenance record deleted successfully"
}
```

---

### 7. Get Maintenance Statistics
**GET** `/api/maintenance/statistics`

Get comprehensive statistics about maintenance records.

#### Query Parameters:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `year` | integer | No | Year to filter statistics (default: current year) |
| `asset_id` | integer | No | Filter statistics for specific asset |

#### Response:
```json
{
  "success": true,
  "data": {
    "total_count": 156,
    "total_cost": 45780.50,
    "by_type": [
      {
        "type": "preventive",
        "count": 89,
        "total_cost": "25340.00"
      },
      {
        "type": "corrective",
        "count": 45,
        "total_cost": "15230.50"
      },
      {
        "type": "emergency",
        "count": 15,
        "total_cost": "4210.00"
      },
      {
        "type": "routine",
        "count": 7,
        "total_cost": "1000.00"
      }
    ],
    "monthly_breakdown": [
      {
        "month": 1,
        "count": 12,
        "total_cost": "3450.00"
      },
      {
        "month": 2,
        "count": 15,
        "total_cost": "4120.50"
      }
    ],
    "top_assets": [
      {
        "id": 5,
        "asset_tag": "AST-2024-005",
        "asset_name": "Vehicle - Toyota Hilux",
        "maintenance_count": 24,
        "total_cost": "8750.00"
      }
    ]
  }
}
```

---

### 8. Get Upcoming Maintenance
**GET** `/api/maintenance/upcoming`

Get maintenance records scheduled for the near future.

#### Query Parameters:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `days` | integer | No | Number of days to look ahead (default: 30) |

#### Response:
```json
{
  "success": true,
  "data": [
    {
      "id": 45,
      "asset_id": 12,
      "maintenance_date": "2026-06-25",
      "type": "preventive",
      "description": "Scheduled preventive maintenance",
      "cost": "200.00",
      "performed_by": "Service Team A",
      "company_id": 1,
      "created_by": 2,
      "created_at": "2026-06-19T10:00:00.000000Z",
      "updated_at": "2026-06-19T10:00:00.000000Z",
      "asset": {
        "id": 12,
        "asset_tag": "AST-2024-012",
        "asset_name": "Generator - Caterpillar"
      }
    }
  ]
}
```

---

### 9. Bulk Delete Maintenance Records
**POST** `/api/maintenance/bulk-delete`

Delete multiple maintenance records at once.

#### Request Body:
```json
{
  "ids": [1, 5, 12, 24]
}
```

#### Validation Rules:
| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `ids` | array | Yes | Array of maintenance record IDs |
| `ids.*` | integer | Yes | Each ID must exist in maintenances table |

#### Response:
```json
{
  "success": true,
  "message": "4 maintenance record(s) deleted successfully",
  "deleted_count": 4
}
```

---

## Maintenance Types
The following maintenance types are supported:
- `preventive` - Scheduled preventive maintenance
- `corrective` - Corrective maintenance to fix issues
- `emergency` - Emergency repairs
- `routine` - Routine checks and servicing
- `inspection` - Regular inspections

---

## Error Responses

### Validation Error (422)
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "asset_id": [
      "The asset id field is required."
    ],
    "type": [
      "The selected type is invalid."
    ]
  }
}
```

### Not Found (404)
```json
{
  "message": "No query results for model [App\\Models\\Maintenance] 999"
}
```

### Unauthorized (401)
```json
{
  "message": "Unauthenticated."
}
```

### Server Error (500)
```json
{
  "message": "Server Error",
  "error": "Detailed error message"
}
```

---

## Example Usage (JavaScript/Fetch)

### Get All Maintenance Records
```javascript
const response = await fetch('/api/maintenance?per_page=20&sort_by=maintenance_date&sort_order=desc', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
});
const data = await response.json();
```

### Create Maintenance Record
```javascript
const response = await fetch('/api/maintenance', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  body: JSON.stringify({
    asset_id: 5,
    maintenance_date: '2026-06-20',
    type: 'preventive',
    description: 'Regular maintenance',
    cost: 150.00,
    performed_by: 'John Doe'
  })
});
const data = await response.json();
```

### Update Maintenance Record
```javascript
const response = await fetch('/api/maintenance/25', {
  method: 'PUT',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  body: JSON.stringify({
    cost: 175.50,
    description: 'Updated maintenance details'
  })
});
const data = await response.json();
```

### Delete Maintenance Record
```javascript
const response = await fetch('/api/maintenance/25', {
  method: 'DELETE',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
});
const data = await response.json();
```

### Get Statistics
```javascript
const response = await fetch('/api/maintenance/statistics?year=2026&asset_id=5', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
});
const data = await response.json();
```

---

## Notes for Frontend Developers

1. **Authentication**: Always include the Bearer token in the Authorization header
2. **Pagination**: Use the pagination links provided in the response for navigation
3. **Date Format**: Use YYYY-MM-DD format for all date fields
4. **Decimal Values**: Cost is returned as string but can be sent as number
5. **Company Filtering**: The API automatically filters by the authenticated user's company
6. **Asset Validation**: The API verifies that assets belong to the user's company
7. **Error Handling**: Always check the `success` field and handle errors appropriately
8. **Search**: The search parameter performs a LIKE search across multiple fields
9. **Filtering**: Multiple filters can be combined for more specific results
10. **Bulk Operations**: Use bulk-delete for efficiency when removing multiple records
