# Barcode Location Tracking API Documentation

**Version:** 1.0  
**Base URL:** `/api/barcode-tracking`  
**Authentication:** Required (JWT Token)

---

## Table of Contents

1. [Individual Barcode Tracking](#individual-barcode-tracking)
2. [Store-Based Tracking](#store-based-tracking)
3. [Advanced Search](#advanced-search)
4. [Grouped Views](#grouped-views)
5. [Movement History](#movement-history)
6. [Statistics & Analytics](#statistics--analytics)
7. [Complete Use Case Examples](#use-case-examples)

---

## Individual Barcode Tracking

### Get Barcode Current Location

Get current location and status of a specific barcode.

**Endpoint:** `GET /api/barcode-tracking/{barcode}/location`

**Example Request:**
```http
GET /api/barcode-tracking/789012345001/location
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "barcode": "789012345001",
    "product": {
      "id": 10,
      "name": "Blue Silk Saree",
      "sku": "SAREE-001"
    },
    "current_store": {
      "id": 5,
      "name": "Main Retail Store",
      "type": "retail",
      "address": "123 Main Street, Dhaka"
    },
    "current_status": "on_display",
    "status_label": "On Display Floor",
    "is_active": true,
    "is_defective": false,
    "is_available_for_sale": true,
    "location_updated_at": "2025-11-08T14:30:00Z",
    "location_metadata": {
      "shelf": "A-3",
      "section": "Women's Clothing",
      "display_started_at": "2025-11-08 14:30:00"
    },
    "batch": {
      "id": 123,
      "batch_number": "BATCH-2025-001",
      "quantity": 95
    }
  }
}
```

---

### Get Barcode Complete History

Get complete movement history of a specific barcode from creation to current state.

**Endpoint:** `GET /api/barcode-tracking/{barcode}/history`

**Example Request:**
```http
GET /api/barcode-tracking/789012345001/history
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "barcode": "789012345001",
    "product": {
      "id": 10,
      "name": "Blue Silk Saree",
      "sku": "SAREE-001"
    },
    "current_location": {
      "barcode": "789012345001",
      "current_store": {
        "id": 5,
        "name": "Main Retail Store"
      },
      "current_status": "on_display",
      "status_label": "On Display Floor"
    },
    "total_movements": 5,
    "history": [
      {
        "id": 156,
        "date": "2025-11-10T18:45:00Z",
        "from_store": "Main Store",
        "to_store": "Main Store",
        "movement_type": "sale",
        "status_before": "on_display",
        "status_after": "with_customer",
        "reference_type": "order",
        "reference_id": 789,
        "performed_by": "John Doe",
        "notes": "Sold via Order #ORD-2025-0789"
      },
      {
        "id": 142,
        "date": "2025-11-08T14:30:00Z",
        "from_store": "Main Store",
        "to_store": "Main Store",
        "movement_type": "adjustment",
        "status_before": "in_shop",
        "status_after": "on_display",
        "reference_type": null,
        "reference_id": null,
        "performed_by": "Jane Smith",
        "notes": "Placed on display floor - Shelf A-3"
      }
    ]
  }
}
```

---

## Store-Based Tracking

### Get All Barcodes at Store

Get all barcodes currently at a specific store with filtering options.

**Endpoint:** `GET /api/barcode-tracking/store/{storeId}`

**Query Parameters:**
- `status` (string, optional) - Filter by status (in_warehouse, in_shop, on_display, etc.)
- `product_id` (integer, optional) - Filter by specific product
- `batch_id` (integer, optional) - Filter by specific batch
- `available_only` (boolean, optional) - Only show items available for sale
- `search` (string, optional) - Search by barcode pattern
- `per_page` (integer, optional, default: 50) - Items per page

**Example Request:**
```http
GET /api/barcode-tracking/store/5?status=on_display&available_only=true&per_page=20
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "store": {
      "id": 5,
      "name": "Main Retail Store",
      "type": "retail"
    },
    "summary": {
      "total_barcodes": 1250,
      "in_warehouse": 450,
      "in_shop": 600,
      "on_display": 200,
      "in_transit": 0,
      "in_shipment": 0,
      "available_for_sale": 1250
    },
    "filters": {
      "status": "on_display",
      "product_id": null,
      "batch_id": null,
      "available_only": true,
      "search": null
    },
    "barcodes": [
      {
        "id": 1001,
        "barcode": "789012345001",
        "product": {
          "id": 10,
          "name": "Blue Silk Saree",
          "sku": "SAREE-001"
        },
        "batch": {
          "id": 123,
          "batch_number": "BATCH-2025-001"
        },
        "current_status": "on_display",
        "status_label": "On Display Floor",
        "is_active": true,
        "is_defective": false,
        "is_available_for_sale": true,
        "location_updated_at": "2025-11-08T14:30:00Z",
        "location_metadata": {
          "shelf": "A-3",
          "section": "Women's Clothing"
        }
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "total": 200,
      "last_page": 10
    }
  }
}
```

---

## Advanced Search

### Search Barcodes with Multiple Filters

Perform advanced search across all barcodes with complex filtering.

**Endpoint:** `POST /api/barcode-tracking/search`

**Request Body:**
```json
{
  "store_id": 5,
  "store_ids": [5, 6, 7],
  "product_id": 10,
  "product_ids": [10, 11, 12],
  "status": "in_shop",
  "statuses": ["in_shop", "on_display"],
  "batch_id": 123,
  "active_only": true,
  "available_only": true,
  "defective_only": false,
  "barcode_search": "78901234",
  "updated_from": "2025-11-01",
  "updated_to": "2025-11-10",
  "created_from": "2025-10-01",
  "created_to": "2025-11-10",
  "order_by": "location_updated_at",
  "order_direction": "desc",
  "per_page": 50
}
```

**All parameters are optional. Mix and match as needed.**

**Example Request:**
```http
POST /api/barcode-tracking/search
Authorization: Bearer {token}
Content-Type: application/json

{
  "store_ids": [5, 6],
  "statuses": ["in_shop", "on_display"],
  "available_only": true,
  "per_page": 100
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "filters_applied": {
      "store_ids": [5, 6],
      "statuses": ["in_shop", "on_display"],
      "available_only": true
    },
    "total_results": 850,
    "barcodes": [
      {
        "id": 1001,
        "barcode": "789012345001",
        "product": {
          "id": 10,
          "name": "Blue Silk Saree",
          "sku": "SAREE-001"
        },
        "current_store": {
          "id": 5,
          "name": "Main Retail Store",
          "type": "retail"
        },
        "batch": {
          "id": 123,
          "batch_number": "BATCH-2025-001"
        },
        "current_status": "on_display",
        "status_label": "On Display Floor",
        "is_active": true,
        "is_defective": false,
        "is_available_for_sale": true,
        "location_updated_at": "2025-11-08T14:30:00Z",
        "location_metadata": {
          "shelf": "A-3"
        },
        "created_at": "2025-11-01T10:00:00Z"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 100,
      "total": 850,
      "last_page": 9
    }
  }
}
```

---

## Grouped Views

### Group Barcodes by Status

Get barcodes grouped by their current status.

**Endpoint:** `GET /api/barcode-tracking/grouped-by-status`

**Query Parameters:**
- `store_id` (integer, optional) - Filter by store
- `product_id` (integer, optional) - Filter by product

**Example Request:**
```http
GET /api/barcode-tracking/grouped-by-status?store_id=5
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "filters": {
      "store_id": 5,
      "product_id": null
    },
    "total_barcodes": 1250,
    "grouped_by_status": {
      "in_warehouse": {
        "status": "in_warehouse",
        "status_label": "In Warehouse",
        "count": 450,
        "barcodes": [
          {
            "id": 1001,
            "barcode": "789012345001",
            "product_name": "Blue Silk Saree",
            "store_name": "Main Retail Store",
            "location_metadata": {"bin": "A-12"}
          }
        ]
      },
      "in_shop": {
        "status": "in_shop",
        "status_label": "In Shop Inventory",
        "count": 600,
        "barcodes": [...]
      },
      "on_display": {
        "status": "on_display",
        "status_label": "On Display Floor",
        "count": 200,
        "barcodes": [...]
      }
    }
  }
}
```

---

### Group Barcodes by Store

Get barcodes grouped by store location.

**Endpoint:** `GET /api/barcode-tracking/grouped-by-store`

**Query Parameters:**
- `status` (string, optional) - Filter by status
- `product_id` (integer, optional) - Filter by product

**Example Request:**
```http
GET /api/barcode-tracking/grouped-by-store?status=on_display
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "filters": {
      "status": "on_display",
      "product_id": null
    },
    "total_barcodes": 500,
    "total_stores": 3,
    "grouped_by_store": [
      {
        "store": {
          "id": 5,
          "name": "Main Retail Store",
          "type": "retail"
        },
        "count": 200,
        "status_breakdown": [
          {"status": "on_display", "count": 200}
        ],
        "barcodes": [
          {
            "id": 1001,
            "barcode": "789012345001",
            "product_name": "Blue Silk Saree",
            "current_status": "on_display",
            "status_label": "On Display Floor"
          }
        ]
      },
      {
        "store": {
          "id": 6,
          "name": "Branch Store A",
          "type": "retail"
        },
        "count": 150,
        "status_breakdown": [
          {"status": "on_display", "count": 150}
        ],
        "barcodes": [...]
      }
    ]
  }
}
```

---

### Group Barcodes by Product

Get barcodes grouped by product.

**Endpoint:** `GET /api/barcode-tracking/grouped-by-product`

**Query Parameters:**
- `store_id` (integer, optional) - Filter by store
- `status` (string, optional) - Filter by status

**Example Request:**
```http
GET /api/barcode-tracking/grouped-by-product?store_id=5&status=in_shop
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "filters": {
      "store_id": 5,
      "status": "in_shop"
    },
    "total_barcodes": 600,
    "total_products": 45,
    "grouped_by_product": [
      {
        "product": {
          "id": 10,
          "name": "Blue Silk Saree",
          "sku": "SAREE-001"
        },
        "count": 25,
        "available_for_sale": 25,
        "status_breakdown": [
          {"status": "in_shop", "count": 25}
        ],
        "store_breakdown": [
          {"store_name": "Main Retail Store", "count": 25}
        ]
      },
      {
        "product": {
          "id": 11,
          "name": "Red Cotton Saree",
          "sku": "SAREE-002"
        },
        "count": 30,
        "available_for_sale": 30,
        "status_breakdown": [
          {"status": "in_shop", "count": 30}
        ],
        "store_breakdown": [
          {"store_name": "Main Retail Store", "count": 30}
        ]
      }
    ]
  }
}
```

---

## Movement History

### Get Movement History

Get movement history with filtering options.

**Endpoint:** `GET /api/barcode-tracking/movements`

**Query Parameters:**
- `barcode` (string, optional) - Filter by specific barcode
- `barcode_id` (integer, optional) - Filter by barcode ID
- `store_id` (integer, optional) - Filter movements involving this store
- `product_id` (integer, optional) - Filter by product
- `movement_type` (string, optional) - sale, dispatch, return, transfer, adjustment
- `reference_type` (string, optional) - order, dispatch, return, shipment
- `from_date` (date, optional) - Start date
- `to_date` (date, optional) - End date
- `per_page` (integer, optional, default: 50)

**Example Request:**
```http
GET /api/barcode-tracking/movements?store_id=5&movement_type=sale&from_date=2025-11-01&per_page=100
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "filters": {
      "store_id": 5,
      "movement_type": "sale",
      "from_date": "2025-11-01"
    },
    "total_movements": 456,
    "movements": [
      {
        "id": 156,
        "movement_date": "2025-11-10T18:45:00Z",
        "barcode": {
          "id": 1001,
          "barcode": "789012345001",
          "product_name": "Blue Silk Saree"
        },
        "from_store": {
          "id": 5,
          "name": "Main Store"
        },
        "to_store": {
          "id": 5,
          "name": "Main Store"
        },
        "movement_type": "sale",
        "status_before": "on_display",
        "status_after": "with_customer",
        "reference_type": "order",
        "reference_id": 789,
        "quantity": 1,
        "notes": "Sold via Order #ORD-2025-0789",
        "performed_by": {
          "id": 12,
          "name": "John Doe"
        }
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 100,
      "total": 456,
      "last_page": 5
    }
  }
}
```

---

## Statistics & Analytics

### Get Statistics Summary

Get overall statistics and breakdowns.

**Endpoint:** `GET /api/barcode-tracking/statistics`

**Query Parameters:**
- `store_id` (integer, optional) - Filter by store
- `product_id` (integer, optional) - Filter by product

**Example Request:**
```http
GET /api/barcode-tracking/statistics?store_id=5
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "filters": {
      "store_id": 5,
      "product_id": null
    },
    "summary": {
      "total_barcodes": 1250,
      "active": 1200,
      "inactive": 50,
      "defective": 15,
      "available_for_sale": 1185
    },
    "status_breakdown": {
      "in_warehouse": 450,
      "in_shop": 600,
      "on_display": 200,
      "in_transit": 0,
      "in_shipment": 0,
      "with_customer": 35,
      "in_return": 5,
      "defective": 15
    },
    "store_breakdown": [
      {
        "store_id": 5,
        "store_name": "Main Retail Store",
        "count": 1250
      }
    ]
  }
}
```

---

### Get Stagnant Barcodes

Find barcodes that haven't moved in X days.

**Endpoint:** `GET /api/barcode-tracking/stagnant`

**Query Parameters:**
- `days` (integer, optional, default: 90) - Days of no movement
- `store_id` (integer, optional) - Filter by store
- `status` (string, optional) - Filter by status

**Example Request:**
```http
GET /api/barcode-tracking/stagnant?days=90&store_id=5
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "cutoff_days": 90,
    "cutoff_date": "2025-08-12T00:00:00Z",
    "total_stagnant": 45,
    "barcodes": [
      {
        "id": 850,
        "barcode": "789012345050",
        "product": {
          "id": 15,
          "name": "Green Silk Saree",
          "sku": "SAREE-015"
        },
        "current_store": {
          "id": 5,
          "name": "Main Retail Store"
        },
        "current_status": "in_warehouse",
        "status_label": "In Warehouse",
        "location_updated_at": "2025-07-15T10:00:00Z",
        "days_since_last_movement": 118
      }
    ]
  }
}
```

---

### Get Overdue Transit Barcodes

Find barcodes in transit for too long.

**Endpoint:** `GET /api/barcode-tracking/overdue-transit`

**Query Parameters:**
- `days` (integer, optional, default: 7) - Days in transit threshold

**Example Request:**
```http
GET /api/barcode-tracking/overdue-transit?days=7
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "cutoff_days": 7,
    "cutoff_date": "2025-11-03T00:00:00Z",
    "total_overdue": 5,
    "barcodes": [
      {
        "id": 1050,
        "barcode": "789012345075",
        "product": {
          "id": 20,
          "name": "Yellow Cotton Saree"
        },
        "destination_store": {
          "id": 6,
          "name": "Branch Store A"
        },
        "transit_started_at": "2025-10-25T08:00:00Z",
        "days_in_transit": 16,
        "dispatch_id": 45
      }
    ]
  }
}
```

---

## Use Case Examples

### Use Case 1: Find Where a Specific Product Is

**Scenario:** Customer calls asking if "Blue Silk Saree" is available at Main Store.

**Solution:**
```http
POST /api/barcode-tracking/search
{
  "product_id": 10,
  "store_id": 5,
  "available_only": true
}
```

**Result:** Shows all available units of that product at that store.

---

### Use Case 2: Track a Sold Item

**Scenario:** Customer wants to track the specific saree they purchased.

**Solution:**
1. Get barcode from order
2. Check history:
```http
GET /api/barcode-tracking/789012345001/history
```

**Result:** Complete journey from warehouse → display → sold → customer

---

### Use Case 3: Identify Missing Inventory

**Scenario:** Physical count shows 95 items but system shows 100.

**Solution:**
```http
GET /api/barcode-tracking/store/5?product_id=10
```

**Result:** List all 100 barcodes with current status. Identify which 5 are missing.

---

### Use Case 4: Check What's On Display

**Scenario:** Manager wants to see what's currently on display floor.

**Solution:**
```http
GET /api/barcode-tracking/store/5?status=on_display
```

**Result:** All items currently on display with shelf locations.

---

### Use Case 5: Find Slow-Moving Stock

**Scenario:** Identify items that haven't moved in 3 months.

**Solution:**
```http
GET /api/barcode-tracking/stagnant?days=90&store_id=5
```

**Result:** List of stagnant inventory for clearance sale planning.

---

### Use Case 6: Verify Dispatch Delivery

**Scenario:** Check if dispatch arrived at destination.

**Solution:**
```http
GET /api/barcode-tracking/movements?reference_type=dispatch&reference_id=45
```

**Result:** All barcodes in that dispatch with current locations.

---

## Status Reference

All possible status values:

| Status | Description | Typical Use |
|--------|-------------|-------------|
| `in_warehouse` | In backroom/warehouse | Storage |
| `in_shop` | Shop inventory (not displayed) | Ready for sale |
| `on_display` | On display floor | Active selling |
| `in_transit` | Moving between locations | Dispatch in progress |
| `in_shipment` | Packaged for customer | Courier delivery |
| `with_customer` | Sold and delivered | Completed sale |
| `in_return` | Customer returning | Return processing |
| `defective` | Marked defective | Quality issue |
| `repair` | Sent for repair | Repair process |
| `vendor_return` | Returned to vendor | Vendor issue |
| `disposed` | Written off | End of lifecycle |

---

## Error Responses

### Barcode Not Found

```json
{
  "success": false,
  "message": "Barcode not found"
}
```

### Store Not Found

```json
{
  "success": false,
  "message": "Store not found"
}
```

### Unauthorized

```json
{
  "success": false,
  "message": "Unauthenticated"
}
```

---

## Rate Limiting

- **Standard requests:** 60 requests per minute
- **Search/List endpoints:** 30 requests per minute
- **Statistics endpoints:** 30 requests per minute

---

## Best Practices

1. **Use Pagination:** For large datasets, use `per_page` parameter
2. **Filter Early:** Apply filters at API level, not in frontend
3. **Cache Statistics:** Statistics change infrequently, cache for 5-10 minutes
4. **Batch Requests:** Use advanced search instead of multiple single requests
5. **Monitor Stagnant:** Run stagnant check weekly for inventory optimization
6. **Track Transit:** Check overdue transit daily to catch shipping issues

---

## Complete API Summary

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/{barcode}/location` | GET | Current location of barcode |
| `/{barcode}/history` | GET | Complete movement history |
| `/store/{storeId}` | GET | All barcodes at store |
| `/search` | POST | Advanced multi-filter search |
| `/grouped-by-status` | GET | Group by status |
| `/grouped-by-store` | GET | Group by store |
| `/grouped-by-product` | GET | Group by product |
| `/movements` | GET | Movement history |
| `/statistics` | GET | Overall statistics |
| `/stagnant` | GET | Slow-moving inventory |
| `/overdue-transit` | GET | Delayed dispatches |

---

**Need Help?** Contact your system administrator or refer to the main documentation.
