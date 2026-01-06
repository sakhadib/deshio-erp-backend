# Service Orders API Documentation

**Date**: January 7, 2026  
**Status**: ✅ Complete Implementation  
**Base URL**: `http://localhost:8000/api`

---

## Overview

The Service Orders API allows you to manage customer service bookings for tailoring, alterations, cleaning, repairs, and other custom services. This system is separate from product orders and is designed for service-based businesses.

**Key Features:**
- Create service bookings with multiple service items
- Track order status (pending → confirmed → in progress → completed)
- Manage payments (full/partial payments supported)
- Assign orders to employees
- Schedule service appointments
- Customer service history

---

## Table of Contents

1. [Service Orders CRUD](#service-orders-crud)
2. [Order Status Management](#order-status-management)
3. [Payment Management](#payment-management)
4. [Statistics & Reports](#statistics--reports)
5. [Customer Service History](#customer-service-history)

---

## Authentication

All endpoints require JWT authentication:

```http
Authorization: Bearer {your_jwt_token}
```

---

## Service Orders CRUD

### 1. List All Service Orders

**Endpoint:** `GET /api/service-orders`

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `status` | string | Filter by status: `pending`, `confirmed`, `in_progress`, `completed`, `cancelled` |
| `payment_status` | string | Filter by payment: `unpaid`, `partially_paid`, `paid` |
| `store_id` | integer | Filter by store |
| `customer_id` | integer | Filter by customer |
| `assigned_to` | integer | Filter by assigned employee |
| `search` | string | Search by order number, customer name/phone/email |
| `date_from` | date | Start date filter (YYYY-MM-DD) |
| `date_to` | date | End date filter (YYYY-MM-DD) |
| `scheduled_date` | date | Filter by scheduled date |
| `sort_by` | string | Sort field (default: `created_at`) |
| `sort_order` | string | `asc` or `desc` (default: `desc`) |
| `per_page` | integer | Results per page (default: 20) |

**Example Request:**
```bash
curl -X GET "http://localhost:8000/api/service-orders?status=pending&store_id=1" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "service_order_number": "SVO-2026-001",
        "customer_id": 5,
        "store_id": 1,
        "status": "pending",
        "payment_status": "unpaid",
        "customer_name": "John Doe",
        "customer_phone": "01712345678",
        "customer_email": "john@example.com",
        "subtotal": 1500.00,
        "tax_amount": 0.00,
        "discount_amount": 0.00,
        "total_amount": 1500.00,
        "paid_amount": 0.00,
        "outstanding_amount": 1500.00,
        "scheduled_date": "2026-01-10T00:00:00.000000Z",
        "scheduled_time": "14:00:00",
        "special_instructions": "Urgent - needed by Friday",
        "created_at": "2026-01-07T10:30:00.000000Z",
        "customer": {
          "id": 5,
          "name": "John Doe",
          "phone": "01712345678"
        },
        "store": {
          "id": 1,
          "name": "Main Store",
          "store_code": "MAIN"
        },
        "created_by": {
          "id": 2,
          "name": "Sarah Admin"
        },
        "assigned_to": {
          "id": 3,
          "name": "Tailor Ahmed"
        },
        "items": [
          {
            "id": 1,
            "service_id": 10,
            "service_name": "Shirt Tailoring",
            "quantity": 2,
            "unit_price": 500.00,
            "total_price": 1000.00,
            "selected_options": ["slim_fit", "cotton_fabric"],
            "customizations": {
              "measurements": {
                "chest": "38",
                "shoulder": "16",
                "length": "28"
              }
            },
            "service": {
              "id": 10,
              "name": "Shirt Tailoring",
              "service_code": "SRV-2026-SHIRT",
              "category": "tailoring",
              "base_price": 500.00
            }
          },
          {
            "id": 2,
            "service_id": 12,
            "service_name": "Trouser Alteration",
            "quantity": 1,
            "unit_price": 500.00,
            "total_price": 500.00,
            "special_instructions": "Shorten by 2 inches"
          }
        ],
        "payments": []
      }
    ],
    "total": 45,
    "per_page": 20,
    "current_page": 1,
    "last_page": 3
  }
}
```

---

### 2. Get Single Service Order

**Endpoint:** `GET /api/service-orders/{id}`

**Example Request:**
```bash
curl -X GET "http://localhost:8000/api/service-orders/1" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "service_order_number": "SVO-2026-001",
    "customer_id": 5,
    "store_id": 1,
    "status": "confirmed",
    "payment_status": "partially_paid",
    "customer_name": "John Doe",
    "customer_phone": "01712345678",
    "customer_email": "john@example.com",
    "customer_address": "123 Main St, Dhaka",
    "total_amount": 1500.00,
    "paid_amount": 500.00,
    "outstanding_amount": 1000.00,
    "scheduled_date": "2026-01-10T00:00:00.000000Z",
    "scheduled_time": "14:00:00",
    "estimated_completion": "2026-01-12T18:00:00.000000Z",
    "confirmed_at": "2026-01-07T11:00:00.000000Z",
    "special_instructions": "Urgent - needed by Friday",
    "notes": "Customer prefers SMS updates",
    "customer": { /* ... */ },
    "store": { /* ... */ },
    "created_by": { /* ... */ },
    "assigned_to": { /* ... */ },
    "items": [ /* ... */ ],
    "payments": [
      {
        "id": 1,
        "amount": 500.00,
        "payment_method_id": 1,
        "payment_date": "2026-01-07T11:00:00.000000Z",
        "reference_number": "CASH-001",
        "status": "completed"
      }
    ]
  }
}
```

**Error Response (404):**
```json
{
  "message": "No query results for model [App\\Models\\ServiceOrder] 999"
}
```

---

### 3. Create Service Order

**Endpoint:** `POST /api/service-orders`

**Request Body:**
```json
{
  "customer_id": 5,
  "store_id": 1,
  "customer_name": "John Doe",
  "customer_phone": "01712345678",
  "customer_email": "john@example.com",
  "customer_address": "123 Main St, Dhaka",
  "scheduled_date": "2026-01-10",
  "scheduled_time": "14:00",
  "special_instructions": "Urgent - needed by Friday",
  "items": [
    {
      "service_id": 10,
      "quantity": 2,
      "unit_price": 500.00,
      "selected_options": ["slim_fit", "cotton_fabric"],
      "customizations": {
        "measurements": {
          "chest": "38",
          "shoulder": "16",
          "length": "28"
        }
      },
      "special_instructions": "Slim fit style preferred"
    },
    {
      "service_id": 12,
      "quantity": 1,
      "special_instructions": "Shorten by 2 inches"
    }
  ]
}
```

**Field Descriptions:**

| Field | Required | Type | Description |
|-------|----------|------|-------------|
| `customer_id` | Optional | integer | Existing customer ID (if registered) |
| `store_id` | **Required** | integer | Store where service will be performed |
| `customer_name` | **Required** | string | Customer full name |
| `customer_phone` | **Required** | string | Customer phone number |
| `customer_email` | Optional | string | Customer email |
| `customer_address` | Optional | string | Customer address |
| `scheduled_date` | Optional | date | Appointment date (YYYY-MM-DD) |
| `scheduled_time` | Optional | time | Appointment time (HH:MM) |
| `special_instructions` | Optional | string | General instructions for entire order |
| `items` | **Required** | array | Array of service items (min: 1) |
| `items[].service_id` | **Required** | integer | Service being ordered |
| `items[].quantity` | **Required** | integer | Quantity (min: 1) |
| `items[].unit_price` | Optional | decimal | Override default price |
| `items[].selected_options` | Optional | array | Service options selected |
| `items[].customizations` | Optional | object | Custom data (measurements, etc.) |
| `items[].special_instructions` | Optional | string | Item-specific instructions |

**Success Response (201):**
```json
{
  "success": true,
  "message": "Service order created successfully",
  "data": {
    "id": 15,
    "service_order_number": "SVO-2026-015",
    "status": "pending",
    "payment_status": "unpaid",
    "total_amount": 1500.00,
    "outstanding_amount": 1500.00,
    "items": [ /* ... */ ],
    "customer": { /* ... */ },
    "store": { /* ... */ }
  }
}
```

**Validation Error Response (422):**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "customer_name": ["The customer name field is required."],
    "items": ["The items field is required."],
    "items.0.service_id": ["The items.0.service_id field is required."]
  }
}
```

---

### 4. Update Service Order

**Endpoint:** `PUT /api/service-orders/{id}`

**Note:** Only pending/confirmed orders can be updated. Completed/cancelled orders cannot be modified.

**Request Body:**
```json
{
  "customer_name": "John Smith Doe",
  "customer_phone": "01712345679",
  "customer_email": "john.smith@example.com",
  "customer_address": "456 New Address, Dhaka",
  "scheduled_date": "2026-01-11",
  "scheduled_time": "15:00",
  "assigned_to": 3,
  "special_instructions": "Updated instructions - rush order",
  "notes": "Customer called to reschedule"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Service order updated successfully",
  "data": {
    "id": 1,
    "service_order_number": "SVO-2026-001",
    "customer_name": "John Smith Doe",
    "scheduled_date": "2026-01-11T00:00:00.000000Z",
    "assigned_to": {
      "id": 3,
      "name": "Tailor Ahmed"
    }
    /* ... */
  }
}
```

**Error Response (422):**
```json
{
  "success": false,
  "message": "Cannot update completed or cancelled orders"
}
```

---

## Order Status Management

### 5. Confirm Service Order

**Endpoint:** `PATCH /api/service-orders/{id}/confirm`

Changes status from `pending` to `confirmed`.

**Example Request:**
```bash
curl -X PATCH "http://localhost:8000/api/service-orders/1/confirm" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Service order confirmed successfully",
  "data": {
    "id": 1,
    "status": "confirmed",
    "confirmed_at": "2026-01-07T12:00:00.000000Z"
  }
}
```

**Error Response (422):**
```json
{
  "success": false,
  "message": "Only pending orders can be confirmed"
}
```

---

### 6. Start Service Order

**Endpoint:** `PATCH /api/service-orders/{id}/start`

Changes status from `pending`/`confirmed` to `in_progress`.

**Example Request:**
```bash
curl -X PATCH "http://localhost:8000/api/service-orders/1/start" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Service order started successfully",
  "data": {
    "id": 1,
    "status": "in_progress",
    "started_at": "2026-01-10T14:00:00.000000Z"
  }
}
```

---

### 7. Complete Service Order

**Endpoint:** `PATCH /api/service-orders/{id}/complete`

Changes status from `in_progress` to `completed`.

**Example Request:**
```bash
curl -X PATCH "http://localhost:8000/api/service-orders/1/complete" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Service order completed successfully",
  "data": {
    "id": 1,
    "status": "completed",
    "completed_at": "2026-01-12T16:30:00.000000Z",
    "actual_completion": "2026-01-12T16:30:00.000000Z"
  }
}
```

**Error Response (422):**
```json
{
  "success": false,
  "message": "Only in-progress orders can be completed"
}
```

---

### 8. Cancel Service Order

**Endpoint:** `PATCH /api/service-orders/{id}/cancel`

Cancels a service order. Cannot cancel already completed or cancelled orders.

**Request Body (Optional):**
```json
{
  "cancellation_reason": "Customer requested cancellation"
}
```

**Example Request:**
```bash
curl -X PATCH "http://localhost:8000/api/service-orders/1/cancel" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"cancellation_reason": "Customer no longer needs the service"}'
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Service order cancelled successfully",
  "data": {
    "id": 1,
    "status": "cancelled",
    "cancelled_at": "2026-01-08T10:00:00.000000Z",
    "notes": "Cancellation: Customer no longer needs the service"
  }
}
```

**Error Response (422):**
```json
{
  "success": false,
  "message": "Cannot cancel completed or already cancelled orders"
}
```

---

## Payment Management

### 9. Add Payment to Service Order

**Endpoint:** `POST /api/service-orders/{id}/payments`

Add a payment to a service order. Supports full and partial payments.

**Request Body:**
```json
{
  "amount": 500.00,
  "payment_method_id": 1,
  "payment_date": "2026-01-07",
  "reference_number": "CASH-001234",
  "notes": "Advance payment"
}
```

**Field Descriptions:**

| Field | Required | Type | Description |
|-------|----------|------|-------------|
| `amount` | **Required** | decimal | Payment amount (min: 0.01) |
| `payment_method_id` | **Required** | integer | Payment method used |
| `payment_date` | Optional | date | Date of payment (default: today) |
| `reference_number` | Optional | string | Transaction/receipt number |
| `notes` | Optional | string | Payment notes |

**Example Request:**
```bash
curl -X POST "http://localhost:8000/api/service-orders/1/payments" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 500.00,
    "payment_method_id": 1,
    "reference_number": "CASH-001"
  }'
```

**Success Response (201):**
```json
{
  "success": true,
  "message": "Payment added successfully",
  "data": {
    "payment": {
      "id": 5,
      "service_order_id": 1,
      "amount": 500.00,
      "payment_method_id": 1,
      "payment_date": "2026-01-07T00:00:00.000000Z",
      "reference_number": "CASH-001",
      "status": "completed",
      "received_by": 2,
      "created_at": "2026-01-07T12:00:00.000000Z"
    },
    "order": {
      "id": 1,
      "total_amount": 1500.00,
      "paid_amount": 500.00,
      "outstanding_amount": 1000.00,
      "payment_status": "partially_paid",
      "payments": [
        {
          "id": 5,
          "amount": 500.00,
          "payment_date": "2026-01-07T00:00:00.000000Z"
        }
      ]
    }
  }
}
```

**Validation Error Response (422):**
```json
{
  "success": false,
  "message": "Payment amount exceeds outstanding amount"
}
```

---

## Statistics & Reports

### 10. Get Service Orders Statistics

**Endpoint:** `GET /api/service-orders/statistics`

Get statistical data about service orders.

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `store_id` | integer | Filter by store |
| `date_from` | date | Start date (YYYY-MM-DD) |
| `date_to` | date | End date (YYYY-MM-DD) |

**Example Request:**
```bash
curl -X GET "http://localhost:8000/api/service-orders/statistics?store_id=1&date_from=2026-01-01" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "total_orders": 145,
    "pending_orders": 23,
    "confirmed_orders": 15,
    "in_progress_orders": 8,
    "completed_orders": 92,
    "cancelled_orders": 7,
    
    "total_revenue": 245000.00,
    "total_paid": 230000.00,
    "total_outstanding": 15000.00,
    
    "unpaid_orders": 12,
    "partially_paid_orders": 18,
    "fully_paid_orders": 108,
    
    "scheduled_today": 5
  }
}
```

---

## Customer Service History

### 11. Get Service Orders by Customer

**Endpoint:** `GET /api/customers/{customerId}/service-orders`

Get all service orders for a specific customer.

**Example Request:**
```bash
curl -X GET "http://localhost:8000/api/customers/5/service-orders" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "service_order_number": "SVO-2026-001",
      "status": "completed",
      "payment_status": "paid",
      "total_amount": 1500.00,
      "created_at": "2026-01-05T10:00:00.000000Z",
      "completed_at": "2026-01-07T16:00:00.000000Z",
      "items": [
        {
          "service_name": "Shirt Tailoring",
          "quantity": 2,
          "total_price": 1000.00
        }
      ],
      "payments": [
        {
          "amount": 1500.00,
          "payment_date": "2026-01-07T16:00:00.000000Z"
        }
      ]
    },
    {
      "id": 15,
      "service_order_number": "SVO-2026-015",
      "status": "in_progress",
      "payment_status": "partially_paid",
      "total_amount": 2500.00,
      "paid_amount": 1000.00,
      "outstanding_amount": 1500.00,
      "created_at": "2026-01-07T14:00:00.000000Z"
    }
  ]
}
```

---

## Order Status Workflow

```
┌──────────┐
│ pending  │ ← Order created
└────┬─────┘
     │ confirm()
     ↓
┌───────────┐
│ confirmed │ ← Order approved by staff
└────┬──────┘
     │ start()
     ↓
┌──────────────┐
│ in_progress  │ ← Service work started
└────┬─────────┘
     │ complete()
     ↓
┌───────────┐
│ completed │ ← Service finished
└───────────┘

     ↓ cancel() can be called from any status except completed/cancelled
     
┌───────────┐
│ cancelled │
└───────────┘
```

---

## Payment Status Transitions

```
┌─────────┐
│ unpaid  │ ← No payments received
└────┬────┘
     │ addPayment() with partial amount
     ↓
┌──────────────────┐
│ partially_paid   │ ← Some payment received (0 < paid < total)
└────┬─────────────┘
     │ addPayment() to complete
     ↓
┌───────┐
│ paid  │ ← Fully paid (paid_amount >= total_amount)
└───────┘
```

---

## Common Use Cases

### Use Case 1: Walk-in Customer Service Booking

```javascript
// Step 1: Create order
const order = await createServiceOrder({
  customer_name: "Ahmed Hassan",
  customer_phone: "01712345678",
  store_id: 1,
  scheduled_date: "2026-01-10",
  items: [
    {
      service_id: 10,  // Shirt Tailoring
      quantity: 3,
      customizations: {
        measurements: { chest: "40", shoulder: "17", length: "29" }
      }
    }
  ]
});

// Step 2: Collect advance payment
await addPayment(order.id, {
  amount: 500,
  payment_method_id: 1  // Cash
});

// Step 3: Confirm order
await confirmOrder(order.id);
```

### Use Case 2: Service Completion & Final Payment

```javascript
// Step 1: Start work
await startOrder(orderId);

// ... work in progress ...

// Step 2: Complete service
await completeOrder(orderId);

// Step 3: Collect final payment
await addPayment(orderId, {
  amount: 1000,  // Remaining amount
  payment_method_id: 2  // Card
});
```

### Use Case 3: Check Today's Scheduled Services

```javascript
const today = new Date().toISOString().split('T')[0];

const orders = await fetch(`/api/service-orders?scheduled_date=${today}&status=confirmed`, {
  headers: { 'Authorization': `Bearer ${token}` }
});

// Display orders scheduled for today
orders.data.data.forEach(order => {
  console.log(`${order.scheduled_time} - ${order.customer_name} - ${order.items.length} services`);
});
```

---

## Error Handling

### Common HTTP Status Codes

| Code | Meaning | Description |
|------|---------|-------------|
| 200 | OK | Request successful |
| 201 | Created | Resource created successfully |
| 400 | Bad Request | Invalid request data |
| 401 | Unauthorized | Missing or invalid auth token |
| 404 | Not Found | Resource doesn't exist |
| 422 | Unprocessable Entity | Validation failed |
| 500 | Server Error | Internal server error |

### Standard Error Response Format

```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field_name": ["Validation error message"]
  }
}
```

---

## Testing with cURL

### Complete Test Workflow

```bash
# 1. Create service order
ORDER_RESPONSE=$(curl -X POST "http://localhost:8000/api/service-orders" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "customer_name": "Test Customer",
    "customer_phone": "01712345678",
    "store_id": 1,
    "items": [{
      "service_id": 1,
      "quantity": 1
    }]
  }')

ORDER_ID=$(echo $ORDER_RESPONSE | jq -r '.data.id')

# 2. Confirm order
curl -X PATCH "http://localhost:8000/api/service-orders/$ORDER_ID/confirm" \
  -H "Authorization: Bearer $TOKEN"

# 3. Add payment
curl -X POST "http://localhost:8000/api/service-orders/$ORDER_ID/payments" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 500,
    "payment_method_id": 1
  }'

# 4. Start order
curl -X PATCH "http://localhost:8000/api/service-orders/$ORDER_ID/start" \
  -H "Authorization: Bearer $TOKEN"

# 5. Complete order
curl -X PATCH "http://localhost:8000/api/service-orders/$ORDER_ID/complete" \
  -H "Authorization: Bearer $TOKEN"

# 6. View order
curl -X GET "http://localhost:8000/api/service-orders/$ORDER_ID" \
  -H "Authorization: Bearer $TOKEN"
```

---

## Related APIs

- **Services Management**: `/api/services` - Manage available services
- **Customers**: `/api/customers` - Customer management
- **Payment Methods**: `/api/payment-methods` - Available payment options
- **Employees**: `/api/employees` - Staff management for assignments

---

## Changelog

**January 7, 2026:**
- Initial release of Service Orders API
- Complete CRUD operations
- Status management workflow
- Payment integration
- Statistics and reporting

---

## Support

For issues or questions about the Service Orders API:
- Backend Team: Contact backend development team
- Documentation: Check related API docs in `/docs` folder

---

**Document Version**: 1.0  
**Last Updated**: January 7, 2026
