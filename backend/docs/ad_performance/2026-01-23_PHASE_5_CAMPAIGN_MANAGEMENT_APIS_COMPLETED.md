# Phase 5: Campaign Management APIs - COMPLETED

**Date:** January 23, 2026  
**Status:** ✅ COMPLETED  
**Files Created:** 1 controller, routes registered  
**Frontend Documentation:** Complete API reference below

---

## Overview

Campaign Management APIs provide complete CRUD operations for advertising campaigns, including product targeting with effective dating. These endpoints allow campaign managers to create, update, and manage campaigns with flexible lifecycle controls.

---

## Authentication

All endpoints require JWT authentication:

```
Authorization: Bearer <your_jwt_token>
```

Middleware: `auth:api`

---

## API Endpoints

Base URL: `/api/ad-campaigns`

### 1. List Campaigns

**Endpoint:** `GET /api/ad-campaigns`

**Description:** List all campaigns with optional filters and pagination.

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| status | string | No | Filter by status: DRAFT, RUNNING, PAUSED, ENDED |
| platform | string | No | Filter by platform: facebook, instagram, google, tiktok, youtube, other |
| search | string | No | Search by campaign name (partial match) |
| from | date | No | Filter campaigns starting from this date |
| to | date | No | Filter campaigns starting before this date |
| per_page | integer | No | Items per page (default: 15) |
| page | integer | No | Page number |

**Response:**

```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "name": "Spring Sale 2026",
        "platform": "facebook",
        "status": "RUNNING",
        "starts_at": "2026-01-20T00:00:00.000000Z",
        "ends_at": "2026-02-20T00:00:00.000000Z",
        "budget_type": "DAILY",
        "budget_amount": "100.00",
        "notes": "Q1 campaign targeting new customers",
        "created_by": 1,
        "updated_by": null,
        "created_at": "2026-01-23T10:00:00.000000Z",
        "updated_at": "2026-01-23T10:00:00.000000Z",
        "created_by": {
          "id": 1,
          "name": "Admin User",
          "email": "admin@example.com"
        },
        "targeted_products": [
          {
            "id": 1,
            "campaign_id": 1,
            "product_id": 10,
            "effective_from": "2026-01-20T00:00:00.000000Z",
            "effective_to": null,
            "created_by": 1,
            "created_at": "2026-01-23T10:05:00.000000Z",
            "product": {
              "id": 10,
              "name": "Premium Widget",
              "sku": "WDG-001"
            }
          }
        ]
      }
    ],
    "first_page_url": "http://api.example.com/api/ad-campaigns?page=1",
    "from": 1,
    "last_page": 3,
    "last_page_url": "http://api.example.com/api/ad-campaigns?page=3",
    "next_page_url": "http://api.example.com/api/ad-campaigns?page=2",
    "per_page": 15,
    "prev_page_url": null,
    "to": 15,
    "total": 42
  }
}
```

---

### 2. Create Campaign

**Endpoint:** `POST /api/ad-campaigns`

**Description:** Create a new campaign. Status is automatically set to DRAFT.

**Request Body:**

```json
{
  "name": "Spring Sale 2026",
  "platform": "facebook",
  "starts_at": "2026-01-20",
  "ends_at": "2026-02-20",
  "budget_type": "DAILY",
  "budget_amount": 100.00,
  "notes": "Q1 campaign targeting new customers"
}
```

**Validation Rules:**

| Field | Type | Rules |
|-------|------|-------|
| name | string | required, max:255 |
| platform | string | required, in: facebook, instagram, google, tiktok, youtube, other |
| starts_at | date | required |
| ends_at | date | nullable, must be after starts_at |
| budget_type | string | nullable, in: DAILY, LIFETIME |
| budget_amount | decimal | nullable, min:0 |
| notes | text | nullable |

**Response (201 Created):**

```json
{
  "success": true,
  "message": "Campaign created successfully",
  "data": {
    "id": 1,
    "name": "Spring Sale 2026",
    "platform": "facebook",
    "status": "DRAFT",
    "starts_at": "2026-01-20T00:00:00.000000Z",
    "ends_at": "2026-02-20T00:00:00.000000Z",
    "budget_type": "DAILY",
    "budget_amount": "100.00",
    "notes": "Q1 campaign targeting new customers",
    "created_by": 1,
    "updated_by": null,
    "created_at": "2026-01-23T10:00:00.000000Z",
    "updated_at": "2026-01-23T10:00:00.000000Z",
    "created_by": {
      "id": 1,
      "name": "Admin User",
      "email": "admin@example.com"
    }
  }
}
```

**Error Response (422 Validation Error):**

```json
{
  "success": false,
  "errors": {
    "platform": ["The platform field is required."],
    "ends_at": ["The ends at must be a date after starts at."]
  }
}
```

---

### 3. Get Campaign Details

**Endpoint:** `GET /api/ad-campaigns/{id}`

**Description:** Get complete campaign details including relationships.

**Response:**

```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Spring Sale 2026",
    "platform": "facebook",
    "status": "RUNNING",
    "starts_at": "2026-01-20T00:00:00.000000Z",
    "ends_at": "2026-02-20T00:00:00.000000Z",
    "budget_type": "DAILY",
    "budget_amount": "100.00",
    "notes": "Q1 campaign targeting new customers",
    "created_by": 1,
    "updated_by": 1,
    "created_at": "2026-01-23T10:00:00.000000Z",
    "updated_at": "2026-01-23T11:30:00.000000Z",
    "created_by": {
      "id": 1,
      "name": "Admin User",
      "email": "admin@example.com"
    },
    "updated_by": {
      "id": 1,
      "name": "Admin User",
      "email": "admin@example.com"
    },
    "targeted_products": [
      {
        "id": 1,
        "campaign_id": 1,
        "product_id": 10,
        "effective_from": "2026-01-20T00:00:00.000000Z",
        "effective_to": null,
        "created_by": 1,
        "created_at": "2026-01-23T10:05:00.000000Z",
        "product": {
          "id": 10,
          "name": "Premium Widget",
          "sku": "WDG-001",
          "price": "99.99"
        },
        "created_by": {
          "id": 1,
          "name": "Admin User"
        }
      }
    ]
  }
}
```

**Error Response (404 Not Found):**

```json
{
  "success": false,
  "message": "Campaign not found"
}
```

---

### 4. Update Campaign

**Endpoint:** `PUT /api/ad-campaigns/{id}`

**Description:** Update campaign fields. All fields are optional (partial update).

**Request Body:**

```json
{
  "name": "Spring Sale 2026 - Extended",
  "ends_at": "2026-03-20",
  "budget_amount": 150.00,
  "notes": "Extended due to high performance"
}
```

**Validation Rules:**

| Field | Type | Rules |
|-------|------|-------|
| name | string | sometimes, max:255 |
| platform | string | sometimes, in: facebook, instagram, google, tiktok, youtube, other |
| starts_at | date | sometimes |
| ends_at | date | nullable, must be after starts_at |
| budget_type | string | nullable, in: DAILY, LIFETIME |
| budget_amount | decimal | nullable, min:0 |
| notes | text | nullable |

**Response:**

```json
{
  "success": true,
  "message": "Campaign updated successfully",
  "data": {
    "id": 1,
    "name": "Spring Sale 2026 - Extended",
    "platform": "facebook",
    "status": "RUNNING",
    "starts_at": "2026-01-20T00:00:00.000000Z",
    "ends_at": "2026-03-20T00:00:00.000000Z",
    "budget_type": "DAILY",
    "budget_amount": "150.00",
    "notes": "Extended due to high performance",
    "created_by": 1,
    "updated_by": 1,
    "created_at": "2026-01-23T10:00:00.000000Z",
    "updated_at": "2026-01-23T14:20:00.000000Z"
  }
}
```

---

### 5. Change Campaign Status

**Endpoint:** `PATCH /api/ad-campaigns/{id}/status`

**Description:** Change campaign status. Validates allowed status transitions.

**Request Body:**

```json
{
  "status": "RUNNING"
}
```

**Validation Rules:**

| Field | Type | Rules |
|-------|------|-------|
| status | string | required, in: DRAFT, RUNNING, PAUSED, ENDED |

**Status Transition Rules:**

| From Status | Can Transition To |
|-------------|-------------------|
| DRAFT | RUNNING |
| RUNNING | PAUSED, ENDED |
| PAUSED | RUNNING, ENDED |
| ENDED | (no transitions allowed) |

**Response:**

```json
{
  "success": true,
  "message": "Campaign status updated successfully",
  "data": {
    "id": 1,
    "name": "Spring Sale 2026",
    "platform": "facebook",
    "status": "RUNNING",
    "starts_at": "2026-01-20T00:00:00.000000Z",
    "ends_at": "2026-02-20T00:00:00.000000Z",
    "budget_type": "DAILY",
    "budget_amount": "100.00",
    "notes": "Q1 campaign targeting new customers",
    "created_by": 1,
    "updated_by": 1,
    "created_at": "2026-01-23T10:00:00.000000Z",
    "updated_at": "2026-01-23T11:30:00.000000Z"
  }
}
```

**Error Response (422 Invalid Transition):**

```json
{
  "success": false,
  "message": "Cannot transition from ENDED to RUNNING"
}
```

---

### 6. Delete Campaign

**Endpoint:** `DELETE /api/ad-campaigns/{id}`

**Description:** Delete a campaign. Only DRAFT campaigns can be deleted.

**Response:**

```json
{
  "success": true,
  "message": "Campaign deleted successfully"
}
```

**Error Response (422 Invalid Status):**

```json
{
  "success": false,
  "message": "Can only delete DRAFT campaigns. Set status to ENDED instead."
}
```

---

### 7. Add Products to Campaign

**Endpoint:** `POST /api/ad-campaigns/{id}/products`

**Description:** Add products to campaign with optional effective date. Prevents duplicates.

**Request Body:**

```json
{
  "product_ids": [10, 15, 20],
  "effective_from": "2026-01-25"
}
```

**Validation Rules:**

| Field | Type | Rules |
|-------|------|-------|
| product_ids | array | required, min:1 |
| product_ids.* | integer | required, exists in products table |
| effective_from | date | nullable, cannot be before campaign starts_at |

**Notes:**
- If `effective_from` is omitted, uses current timestamp
- If `effective_from` is before campaign `starts_at`, validation error is returned
- Skips products that are already active in the campaign

**Response:**

```json
{
  "success": true,
  "message": "3 product(s) added, 1 skipped (already exists)",
  "data": {
    "added": [
      {
        "id": 1,
        "campaign_id": 1,
        "product_id": 10,
        "effective_from": "2026-01-25T00:00:00.000000Z",
        "effective_to": null,
        "created_by": 1,
        "created_at": "2026-01-23T15:00:00.000000Z",
        "product": {
          "id": 10,
          "name": "Premium Widget",
          "sku": "WDG-001",
          "price": "99.99"
        }
      },
      {
        "id": 2,
        "campaign_id": 1,
        "product_id": 15,
        "effective_from": "2026-01-25T00:00:00.000000Z",
        "effective_to": null,
        "created_by": 1,
        "created_at": "2026-01-23T15:00:00.000000Z",
        "product": {
          "id": 15,
          "name": "Deluxe Widget",
          "sku": "WDG-002",
          "price": "149.99"
        }
      }
    ],
    "skipped_product_ids": [20]
  }
}
```

**Error Response (422 Validation Error):**

```json
{
  "success": false,
  "message": "effective_from cannot be before campaign starts_at"
}
```

---

### 8. List Campaign Products

**Endpoint:** `GET /api/ad-campaigns/{id}/products`

**Description:** List all products targeted by a campaign, including inactive ones (historical data).

**Response:**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "campaign_id": 1,
      "product_id": 10,
      "effective_from": "2026-01-20T00:00:00.000000Z",
      "effective_to": null,
      "created_by": 1,
      "created_at": "2026-01-23T10:05:00.000000Z",
      "product": {
        "id": 10,
        "name": "Premium Widget",
        "sku": "WDG-001",
        "price": "99.99",
        "cost_price": "50.00"
      },
      "created_by": {
        "id": 1,
        "name": "Admin User",
        "email": "admin@example.com"
      }
    },
    {
      "id": 2,
      "campaign_id": 1,
      "product_id": 15,
      "effective_from": "2026-01-20T00:00:00.000000Z",
      "effective_to": "2026-01-28T00:00:00.000000Z",
      "created_by": 1,
      "created_at": "2026-01-23T10:05:00.000000Z",
      "product": {
        "id": 15,
        "name": "Deluxe Widget (removed)",
        "sku": "WDG-002",
        "price": "149.99"
      },
      "created_by": {
        "id": 1,
        "name": "Admin User"
      }
    }
  ]
}
```

**Note:** Products with `effective_to` set are no longer active but show historical targeting.

---

### 9. Remove Product from Campaign

**Endpoint:** `DELETE /api/ad-campaigns/{id}/products/{mappingId}`

**Description:** Soft remove a product from campaign by setting `effective_to` to current time. Preserves historical attribution data.

**Response:**

```json
{
  "success": true,
  "message": "Product removed from campaign",
  "data": {
    "id": 1,
    "campaign_id": 1,
    "product_id": 10,
    "effective_from": "2026-01-20T00:00:00.000000Z",
    "effective_to": "2026-01-23T16:00:00.000000Z",
    "created_by": 1,
    "created_at": "2026-01-23T10:05:00.000000Z",
    "updated_at": "2026-01-23T16:00:00.000000Z"
  }
}
```

**Error Response (404 Not Found):**

```json
{
  "success": false,
  "message": "Product mapping not found"
}
```

---

## Business Logic Notes

### Effective Dating System

The campaign-product targeting uses an **effective dating system** to preserve historical accuracy:

- **effective_from**: When product targeting starts
- **effective_to**: When product targeting ends (null = still active)

**Why This Matters:**

When you edit campaign products (add/remove), the attribution engine needs to know which products were targeted at the time an order was placed. Setting `effective_to` instead of deleting the record preserves this history.

**Example Timeline:**

```
Campaign: "Spring Sale 2026"
├─ Jan 20-25: Product A, Product B
├─ Jan 26-31: Product A, Product B, Product C (added)
└─ Feb 1-20: Product A, Product C (removed Product B)

Order placed Jan 27 → Matches Products A, B, C
Order placed Feb 10 → Matches Products A, C only
```

### Status Lifecycle

```
┌───────┐
│ DRAFT │ ──────────────┐
└───────┘               │
    │                   ▼
    │              ┌─────────┐
    └─────────────►│ RUNNING │
                   └─────────┘
                        │
                        ├──────┐
                        │      │
                        ▼      ▼
                   ┌────────┐ ┌────────┐
                   │ PAUSED │ │ ENDED  │
                   └────────┘ └────────┘
                        │         │
                        │         └──► (Terminal)
                        ▼
                   ┌─────────┐
                   │ RUNNING │
                   └─────────┘
                        │
                        ▼
                   ┌────────┐
                   │ ENDED  │
                   └────────┘
```

### Campaign Deletion Rules

- **DRAFT status**: Can be hard-deleted (database record removed)
- **Other statuses**: Cannot be deleted, must set status to ENDED instead
- **Reason**: Preserves attribution data for historical orders

---

## Frontend Integration Tips

### Creating a Campaign Workflow

1. Create campaign (status = DRAFT)
2. Add products to campaign
3. Change status to RUNNING when ready to launch
4. Monitor performance (Phase 6 reporting APIs)
5. Pause/resume as needed (RUNNING ↔ PAUSED)
6. End campaign when complete (status = ENDED)

### Filtering Best Practices

Use multiple filters together for better UX:

```javascript
// Example: Active campaigns for Facebook
GET /api/ad-campaigns?status=RUNNING&platform=facebook

// Example: All campaigns from Q1 2026
GET /api/ad-campaigns?from=2026-01-01&to=2026-03-31

// Example: Search with pagination
GET /api/ad-campaigns?search=Spring&per_page=20&page=2
```

### Error Handling

All endpoints return consistent error structure:

```javascript
try {
  const response = await api.post('/ad-campaigns', campaignData);
  if (response.data.success) {
    // Handle success
    console.log('Campaign created:', response.data.data);
  }
} catch (error) {
  if (error.response.status === 422) {
    // Validation errors
    const errors = error.response.data.errors;
    Object.keys(errors).forEach(field => {
      console.error(`${field}: ${errors[field][0]}`);
    });
  } else if (error.response.status === 404) {
    // Not found
    console.error(error.response.data.message);
  } else {
    // Server error
    console.error('Server error:', error.response.data);
  }
}
```

---

## Testing Checklist

- [ ] Create campaign with valid data
- [ ] Create campaign with invalid platform (should fail)
- [ ] Create campaign with ends_at before starts_at (should fail)
- [ ] Update campaign fields
- [ ] Change status DRAFT → RUNNING
- [ ] Attempt invalid status transition ENDED → RUNNING (should fail)
- [ ] Add products to campaign
- [ ] Add duplicate products (should skip)
- [ ] Add products with effective_from before campaign starts (should fail)
- [ ] List campaign products (verify effective dates)
- [ ] Remove product from campaign (verify effective_to set)
- [ ] Delete DRAFT campaign (should succeed)
- [ ] Attempt to delete RUNNING campaign (should fail)
- [ ] List campaigns with various filters
- [ ] Test pagination

---

## Related Documentation

- **Phase 1:** Database Foundation
- **Phase 2:** Models & Relationships
- **Phase 3:** Attribution Engine
- **Phase 4:** Event Automation
- **Phase 6:** Reporting APIs (coming soon)

---

## Contact

For questions about these APIs, contact the backend team or refer to the complete implementation plan.

**Implementation Date:** January 23, 2026  
**Controller:** `app/Http/Controllers/AdCampaignController.php`  
**Routes:** `routes/api.php` (lines 1558-1590)  
**Total Endpoints:** 9
