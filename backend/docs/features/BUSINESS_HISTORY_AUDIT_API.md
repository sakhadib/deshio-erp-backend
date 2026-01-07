# Business History & Audit Trail API

## Overview

The Business History API provides comprehensive audit trails for critical business operations. All endpoints track **WHO**, **WHEN**, and **WHAT** - showing which user made changes, when they occurred, and the before/after data for updates.

**Base URL:** `/api/business-history`

**Authentication:** All endpoints require `auth:api` middleware (Bearer token)

---

## Standard Response Structure

All history endpoints return activities in this standardized format:

```json
{
  "data": [
    {
      "id": 123,
      "who": {
        "id": 1,
        "type": "Employee",
        "name": "John Doe",
        "email": "john@example.com"
      },
      "when": {
        "timestamp": "2025-12-21T10:30:00.000000Z",
        "formatted": "2025-12-21 10:30:00",
        "human": "2 hours ago"
      },
      "what": {
        "action": "updated",
        "description": "Order #ORD-12345 updated",
        "fields_changed": ["status", "approved_by"],
        "changes": {
          "status": {
            "from": "pending",
            "to": "approved"
          },
          "approved_by": {
            "from": null,
            "to": 1
          }
        }
      },
      "subject": {
        "id": 456,
        "type": "Order",
        "data": { /* related model data */ }
      }
    }
  ],
  "links": { /* pagination links */ },
  "meta": { /* pagination metadata */ }
}
```

---

## 1. Product Dispatch History

**Endpoint:** `GET /api/business-history/product-dispatches`

**Description:** Track all changes to product dispatches including creation, status changes, approvals, and item modifications.

### Query Parameters

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `dispatch_id` | integer | Filter by specific dispatch | `123` |
| `status` | string | Filter by dispatch status | `pending`, `approved`, `in_transit` |
| `source_store_id` | integer | Filter by source store | `5` |
| `destination_store_id` | integer | Filter by destination store | `8` |
| `date_from` | date | Filter from date (Y-m-d) | `2025-01-01` |
| `date_to` | date | Filter to date (Y-m-d) | `2025-12-31` |
| `event` | string | Filter by action type | `created`, `updated`, `deleted` |
| `per_page` | integer | Items per page (default: 15) | `25` |

### Example Request

```bash
GET /api/business-history/product-dispatches?dispatch_id=123&status=approved&per_page=20
Authorization: Bearer {token}
```

### Example Response

```json
{
  "data": [
    {
      "id": 1001,
      "who": {
        "id": 5,
        "type": "Employee",
        "name": "Sarah Manager",
        "email": "sarah@deshio.com"
      },
      "when": {
        "timestamp": "2025-12-21T14:30:00.000000Z",
        "formatted": "2025-12-21 14:30:00",
        "human": "3 hours ago"
      },
      "what": {
        "action": "updated",
        "description": "ProductDispatch #123 status changed to approved",
        "fields_changed": ["status", "approved_by", "approved_at"],
        "changes": {
          "status": {
            "from": "pending",
            "to": "approved"
          },
          "approved_by": {
            "from": null,
            "to": 5
          },
          "approved_at": {
            "from": null,
            "to": "2025-12-21 14:30:00"
          }
        }
      },
      "subject": {
        "id": 123,
        "type": "ProductDispatch",
        "data": {
          "dispatch_number": "DISP-2025-00123",
          "source_store_id": 1,
          "destination_store_id": 3,
          "status": "approved"
        }
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 45
  }
}
```

---

## 2. Order History

**Endpoint:** `GET /api/business-history/orders`

**Description:** Comprehensive order history including changes to Order, OrderItems, and related Customer data.

### Query Parameters

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `order_id` | integer | Filter by specific order | `456` |
| `customer_id` | integer | Filter by customer | `789` |
| `order_number` | string | Filter by order number | `ORD-12345` |
| `date_from` | date | Filter from date | `2025-01-01` |
| `date_to` | date | Filter to date | `2025-12-31` |
| `event` | string | Filter by action | `created`, `updated` |
| `per_page` | integer | Items per page | `15` |

### Example Request

```bash
GET /api/business-history/orders?order_id=456&date_from=2025-12-01
Authorization: Bearer {token}
```

### Example Response

```json
{
  "data": [
    {
      "id": 2001,
      "who": {
        "id": 3,
        "type": "Employee",
        "name": "Mike Sales",
        "email": "mike@deshio.com"
      },
      "when": {
        "timestamp": "2025-12-21T09:15:00.000000Z",
        "formatted": "2025-12-21 09:15:00",
        "human": "8 hours ago"
      },
      "what": {
        "action": "updated",
        "description": "Order #ORD-12345 updated",
        "fields_changed": ["status", "total_amount"],
        "changes": {
          "status": {
            "from": "pending",
            "to": "confirmed"
          },
          "total_amount": {
            "from": "5000.00",
            "to": "5500.00"
          }
        }
      },
      "subject": {
        "id": 456,
        "type": "Order",
        "data": {
          "order_number": "ORD-12345",
          "customer_id": 789,
          "status": "confirmed",
          "total_amount": "5500.00"
        }
      }
    },
    {
      "id": 2002,
      "who": {
        "id": 3,
        "type": "Employee",
        "name": "Mike Sales",
        "email": "mike@deshio.com"
      },
      "when": {
        "timestamp": "2025-12-21T09:16:00.000000Z",
        "formatted": "2025-12-21 09:16:00",
        "human": "8 hours ago"
      },
      "what": {
        "action": "updated",
        "description": "OrderItem for Order #456 updated",
        "fields_changed": ["quantity"],
        "changes": {
          "quantity": {
            "from": 2,
            "to": 3
          }
        }
      },
      "subject": {
        "id": 890,
        "type": "OrderItem",
        "data": {
          "order_id": 456,
          "product_id": 101,
          "quantity": 3
        }
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 28
  }
}
```

---

## 3. Order Comprehensive History

**Endpoint:** `GET /api/business-history/orders/{orderId}/comprehensive`

**Description:** Get complete timeline of a specific order including Order, OrderItems, OrderPayments, Shipments, and Customer changes all in one request.

### URL Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `orderId` | integer | Yes | The order ID |

### Query Parameters

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `per_page` | integer | Items per page | `50` |

### Example Request

```bash
GET /api/business-history/orders/456/comprehensive?per_page=50
Authorization: Bearer {token}
```

### Example Response

```json
{
  "data": [
    {
      "id": 3001,
      "who": { /* ... */ },
      "when": {
        "timestamp": "2025-12-21T08:00:00.000000Z",
        "formatted": "2025-12-21 08:00:00",
        "human": "10 hours ago"
      },
      "what": {
        "action": "created",
        "description": "Order #ORD-12345 created",
        "fields_changed": [],
        "changes": {}
      },
      "subject": {
        "id": 456,
        "type": "Order",
        "data": {
          "order_number": "ORD-12345",
          "customer_id": 789,
          "status": "pending"
        }
      }
    },
    {
      "id": 3002,
      "who": { /* ... */ },
      "when": {
        "timestamp": "2025-12-21T08:05:00.000000Z",
        "formatted": "2025-12-21 08:05:00",
        "human": "9 hours ago"
      },
      "what": {
        "action": "created",
        "description": "OrderItem added to Order #456",
        "fields_changed": [],
        "changes": {}
      },
      "subject": {
        "id": 890,
        "type": "OrderItem",
        "data": {
          "order_id": 456,
          "product_id": 101,
          "quantity": 2
        }
      }
    },
    {
      "id": 3003,
      "who": { /* ... */ },
      "when": {
        "timestamp": "2025-12-21T10:30:00.000000Z",
        "formatted": "2025-12-21 10:30:00",
        "human": "7 hours ago"
      },
      "what": {
        "action": "created",
        "description": "Payment recorded for Order #456",
        "fields_changed": [],
        "changes": {}
      },
      "subject": {
        "id": 234,
        "type": "OrderPayment",
        "data": {
          "order_id": 456,
          "amount": "5500.00",
          "payment_method_id": 1
        }
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 50,
    "total": 12
  }
}
```

---

## 4. Purchase Order History

**Endpoint:** `GET /api/business-history/purchase-orders`

**Description:** Track all purchase order changes including creation, edits, status changes, approvals, and receiving.

### Query Parameters

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `purchase_order_id` | integer | Filter by specific PO | `789` |
| `po_number` | string | Filter by PO number | `PO-2025-001` |
| `vendor_id` | integer | Filter by vendor | `12` |
| `status` | string | Filter by PO status | `pending`, `approved`, `received` |
| `date_from` | date | Filter from date | `2025-01-01` |
| `date_to` | date | Filter to date | `2025-12-31` |
| `event` | string | Filter by action | `created`, `updated` |
| `per_page` | integer | Items per page | `15` |

### Example Request

```bash
GET /api/business-history/purchase-orders?vendor_id=12&status=approved
Authorization: Bearer {token}
```

### Example Response

```json
{
  "data": [
    {
      "id": 4001,
      "who": {
        "id": 7,
        "type": "Employee",
        "name": "Alice Procurement",
        "email": "alice@deshio.com"
      },
      "when": {
        "timestamp": "2025-12-20T15:00:00.000000Z",
        "formatted": "2025-12-20 15:00:00",
        "human": "1 day ago"
      },
      "what": {
        "action": "updated",
        "description": "PurchaseOrder #PO-2025-001 approved",
        "fields_changed": ["status", "approved_by", "approved_at"],
        "changes": {
          "status": {
            "from": "pending",
            "to": "approved"
          },
          "approved_by": {
            "from": null,
            "to": 7
          },
          "approved_at": {
            "from": null,
            "to": "2025-12-20 15:00:00"
          }
        }
      },
      "subject": {
        "id": 789,
        "type": "PurchaseOrder",
        "data": {
          "po_number": "PO-2025-001",
          "vendor_id": 12,
          "status": "approved",
          "total_amount": "150000.00"
        }
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 8
  }
}
```

---

## 5. Store Assignment History

**Endpoint:** `GET /api/business-history/store-assignments`

**Description:** Track when orders are assigned to specific stores. Shows the old store (if any) and the new store assigned.

### Query Parameters

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `order_id` | integer | Filter by specific order | `456` |
| `store_id` | integer | Filter by store | `3` |
| `date_from` | date | Filter from date | `2025-01-01` |
| `date_to` | date | Filter to date | `2025-12-31` |
| `per_page` | integer | Items per page | `15` |

### Example Request

```bash
GET /api/business-history/store-assignments?store_id=3&date_from=2025-12-01
Authorization: Bearer {token}
```

### Example Response

```json
{
  "data": [
    {
      "id": 5001,
      "who": {
        "id": 4,
        "type": "Employee",
        "name": "Tom Logistics",
        "email": "tom@deshio.com"
      },
      "when": {
        "timestamp": "2025-12-21T11:00:00.000000Z",
        "formatted": "2025-12-21 11:00:00",
        "human": "6 hours ago"
      },
      "what": {
        "action": "updated",
        "description": "Order #ORD-12345 assigned to store",
        "fields_changed": ["store_id"],
        "changes": {
          "store_id": {
            "from": null,
            "to": 3
          }
        },
        "old_store": null,
        "new_store": {
          "id": 3,
          "name": "Dhaka Main Branch",
          "code": "DHK-001"
        }
      },
      "subject": {
        "id": 456,
        "type": "Order",
        "data": {
          "order_number": "ORD-12345",
          "store_id": 3,
          "status": "pending_assignment"
        }
      }
    },
    {
      "id": 5002,
      "who": {
        "id": 4,
        "type": "Employee",
        "name": "Tom Logistics",
        "email": "tom@deshio.com"
      },
      "when": {
        "timestamp": "2025-12-21T12:00:00.000000Z",
        "formatted": "2025-12-21 12:00:00",
        "human": "5 hours ago"
      },
      "what": {
        "action": "updated",
        "description": "Order #ORD-67890 reassigned to different store",
        "fields_changed": ["store_id"],
        "changes": {
          "store_id": {
            "from": 2,
            "to": 3
          }
        },
        "old_store": {
          "id": 2,
          "name": "Chittagong Branch",
          "code": "CTG-001"
        },
        "new_store": {
          "id": 3,
          "name": "Dhaka Main Branch",
          "code": "DHK-001"
        }
      },
      "subject": {
        "id": 789,
        "type": "Order",
        "data": {
          "order_number": "ORD-67890",
          "store_id": 3,
          "status": "confirmed"
        }
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 23
  }
}
```

---

## 6. Product History

**Endpoint:** `GET /api/business-history/products`

**Description:** Track product changes including edits to product details and when products are marked as defective.

### Query Parameters

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `product_id` | integer | Filter by specific product | `101` |
| `sku` | string | Filter by product SKU | `PROD-001` |
| `is_defective` | boolean | Show only defective markings | `true`, `false` |
| `date_from` | date | Filter from date | `2025-01-01` |
| `date_to` | date | Filter to date | `2025-12-31` |
| `event` | string | Filter by action | `created`, `updated`, `deleted` |
| `per_page` | integer | Items per page | `15` |

### Example Request

```bash
GET /api/business-history/products?product_id=101&is_defective=true
Authorization: Bearer {token}
```

### Example Response

```json
{
  "data": [
    {
      "id": 6001,
      "who": {
        "id": 6,
        "type": "Employee",
        "name": "Lisa QC",
        "email": "lisa@deshio.com"
      },
      "when": {
        "timestamp": "2025-12-21T13:30:00.000000Z",
        "formatted": "2025-12-21 13:30:00",
        "human": "4 hours ago"
      },
      "what": {
        "action": "updated",
        "description": "Product #PROD-001 updated",
        "fields_changed": ["price", "stock_quantity"],
        "changes": {
          "price": {
            "from": "1500.00",
            "to": "1800.00"
          },
          "stock_quantity": {
            "from": 50,
            "to": 45
          }
        }
      },
      "subject": {
        "id": 101,
        "type": "Product",
        "data": {
          "sku": "PROD-001",
          "name": "Premium Widget",
          "price": "1800.00"
        }
      }
    },
    {
      "id": 6002,
      "who": {
        "id": 6,
        "type": "Employee",
        "name": "Lisa QC",
        "email": "lisa@deshio.com"
      },
      "when": {
        "timestamp": "2025-12-21T14:00:00.000000Z",
        "formatted": "2025-12-21 14:00:00",
        "human": "3 hours ago"
      },
      "what": {
        "action": "created",
        "description": "Product #PROD-001 marked as defective",
        "fields_changed": [],
        "changes": {},
        "marked_as_defective": true,
        "defect_reason": "Scratch on surface",
        "condition": "minor_damage",
        "discount_percentage": "20.00"
      },
      "subject": {
        "id": 234,
        "type": "DefectiveProduct",
        "data": {
          "product_id": 101,
          "defect_reason": "Scratch on surface",
          "condition": "minor_damage",
          "discount_percentage": "20.00"
        }
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 15
  }
}
```

---

## 7. History Statistics

**Endpoint:** `GET /api/business-history/statistics`

**Description:** Get overall statistics about business activities including total activities, breakdown by model, by event type, and most active users.

### Query Parameters

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `date_from` | date | Filter from date | `2025-01-01` |
| `date_to` | date | Filter to date | `2025-12-31` |

### Example Request

```bash
GET /api/business-history/statistics?date_from=2025-12-01
Authorization: Bearer {token}
```

### Example Response

```json
{
  "total_activities": 1523,
  "date_range": {
    "from": "2025-12-01",
    "to": "2025-12-21"
  },
  "by_model": {
    "Order": 456,
    "OrderItem": 234,
    "ProductDispatch": 189,
    "PurchaseOrder": 145,
    "Product": 321,
    "Customer": 89,
    "OrderPayment": 89
  },
  "by_event": {
    "created": 678,
    "updated": 789,
    "deleted": 56
  },
  "most_active_users": [
    {
      "id": 3,
      "type": "Employee",
      "name": "Mike Sales",
      "email": "mike@deshio.com",
      "activity_count": 234
    },
    {
      "id": 5,
      "type": "Employee",
      "name": "Sarah Manager",
      "email": "sarah@deshio.com",
      "activity_count": 189
    },
    {
      "id": 4,
      "type": "Employee",
      "name": "Tom Logistics",
      "email": "tom@deshio.com",
      "activity_count": 156
    }
  ]
}
```

---

## Common Use Cases

### 1. Track Who Approved a Dispatch

```bash
GET /api/business-history/product-dispatches?dispatch_id=123&event=updated
```

Look for changes to `approved_by` and `approved_at` fields in the response.

### 2. See Complete Order Timeline

```bash
GET /api/business-history/orders/456/comprehensive?per_page=100
```

This gives you Order + Items + Payments + Shipments all in chronological order.

### 3. Find When an Order Was Assigned to a Store

```bash
GET /api/business-history/store-assignments?order_id=456
```

Shows when the order got its `store_id` and who assigned it.

### 4. Track Product Price Changes

```bash
GET /api/business-history/products?product_id=101&event=updated
```

Filter the response for `price` in `fields_changed` to see price history.

### 5. Find All Defective Products Marked Today

```bash
GET /api/business-history/products?is_defective=true&date_from=2025-12-21&date_to=2025-12-21
```

### 6. See Who Created a Purchase Order

```bash
GET /api/business-history/purchase-orders?purchase_order_id=789&event=created
```

### 7. Track Customer Information Updates

```bash
GET /api/business-history/orders?customer_id=789
```

This will show when Customer model was updated (included in order history).

---

## Integration Notes

### Frontend Display Tips

1. **Timeline View**: Use the `when.formatted` or `when.human` for display
2. **Change Highlighting**: Use `fields_changed` array to highlight specific fields
3. **Before/After Comparison**: Use `changes` object to show old vs new values
4. **User Attribution**: Display `who.name` and `who.email` for accountability
5. **Filtering UI**: Create dropdowns for status, date ranges, and event types

### Error Handling

All endpoints return standard Laravel error responses:

```json
{
  "message": "Unauthorized",
  "errors": {}
}
```

**Common HTTP Status Codes:**
- `200`: Success
- `401`: Unauthorized (missing/invalid token)
- `403`: Forbidden (insufficient permissions)
- `404`: Resource not found
- `422`: Validation error (invalid parameters)

### Performance Tips

1. **Pagination**: Always use appropriate `per_page` value (default: 15, max: 100)
2. **Date Filtering**: Use `date_from` and `date_to` to limit results
3. **Specific IDs**: Filter by specific IDs when possible (e.g., `order_id`, `product_id`)
4. **Caching**: Consider caching statistics endpoint results for dashboard views

---

## Technical Details

### Underlying System

- **Activity Log Package**: Spatie Laravel Activity Log v4.10
- **Database Table**: `activity_log`
- **Models with Tracking**: Product, Order, OrderItem, Customer, PurchaseOrder, ProductDispatch, OrderPayment, Shipment, DefectiveProduct

### What's Automatically Tracked

All models using the `AutoLogsActivity` trait automatically log:
- **Created**: When a new record is created
- **Updated**: When any field is changed (with before/after values)
- **Deleted**: When a record is soft/hard deleted

### Custom Properties

Some models log additional custom properties:
- **Order**: Tracks related customer changes
- **ProductDispatch**: Tracks approval workflow
- **DefectiveProduct**: Includes defect reason, condition, discount

---

## Support

For questions or issues with the Business History API, contact:
- **Backend Team**: backend@deshio.com
- **API Documentation**: See `/docs` folder for other API references
- **Postman Collection**: Available in project repository

---

**Last Updated**: December 21, 2025
**API Version**: 1.0
