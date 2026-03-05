# Order Customer Information Update API

**Date:** March 5, 2026  
**Feature:** Edit customer name, phone, and address on existing orders  
**Status:** ✅ IMPLEMENTED

---

## Overview

This API allows updating customer information on existing orders. Since **phone number is the prime identifier** for customers, the system handles phone number changes specially:

- **Phone Changed:** Creates or finds a new customer with the new phone, then reassigns the order
- **Phone Same:** Updates the existing customer's name and/or address
- **Full Audit Trail:** All changes are logged in the order's metadata and activity logs

---

## API Endpoint

```
PATCH /api/admin/orders/{id}/customer-info
```

**Authentication:** Required (Employee Token)

**Route Location:** `routes/api.php` - Line 1021

**Controller Method:** `OrderController::updateCustomerInfo()` - Line 771

---

## Request

### Headers
```http
Authorization: Bearer {employee_access_token}
Content-Type: application/json
```

### Path Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | Order ID |

### Request Body
```json
{
  "customer_name": "John Smith",
  "customer_phone": "+8801722222222",
  "customer_address": "123 New Street, Dhaka 1212"
}
```

### Body Parameters
| Parameter | Type | Required | Max Length | Description |
|-----------|------|----------|------------|-------------|
| `customer_name` | string | Yes | 255 | Customer's full name |
| `customer_phone` | string | Yes | 20 | Customer's phone number (prime identifier) |
| `customer_address` | string | No | 500 | Customer's address |

---

## Response

### Success Response (200 OK)

**Scenario 1: Phone Changed (New Customer Created)**
```json
{
  "success": true,
  "message": "Customer information updated successfully",
  "data": {
    "order": {
      "id": 123,
      "order_number": "ORD-2026-001234",
      "customer_id": 78,
      "status": "confirmed",
      "total_amount": 15000.00,
      "customer": {
        "id": 78,
        "name": "John Smith",
        "phone": "+8801722222222",
        "address": "123 New Street, Dhaka 1212",
        "customer_code": "CUST-000078"
      },
      "items": [...],
      "payments": [...]
    },
    "action_taken": "customer_created_and_order_reassigned",
    "old_customer": {
      "id": 45,
      "name": "John Doe",
      "phone": "+8801711111111",
      "address": "Old Address"
    },
    "new_customer": {
      "id": 78,
      "name": "John Smith",
      "phone": "+8801722222222",
      "address": "123 New Street, Dhaka 1212"
    }
  }
}
```

**Scenario 2: Phone Changed (Existing Customer Found)**
```json
{
  "success": true,
  "message": "Customer information updated successfully",
  "data": {
    "order": {...},
    "action_taken": "order_reassigned_to_existing_customer",
    "old_customer": {
      "id": 45,
      "name": "John Doe",
      "phone": "+8801711111111",
      "address": "Old Address"
    },
    "new_customer": {
      "id": 92,
      "name": "John Smith",
      "phone": "+8801722222222",
      "address": "123 New Street, Dhaka 1212"
    }
  }
}
```

**Scenario 3: Phone Same, Name/Address Updated**
```json
{
  "success": true,
  "message": "Customer information updated successfully",
  "data": {
    "order": {...},
    "action_taken": "customer_info_updated",
    "old_customer": {
      "id": 45,
      "name": "John Doe",
      "phone": "+8801711111111",
      "address": "Old Address"
    },
    "new_customer": {
      "id": 45,
      "name": "John Smith",
      "phone": "+8801711111111",
      "address": "123 New Street, Dhaka 1212"
    }
  }
}
```

**Scenario 4: No Changes Needed**
```json
{
  "success": true,
  "message": "Customer information updated successfully",
  "data": {
    "order": {...},
    "action_taken": "no_changes_needed",
    "old_customer": {...},
    "new_customer": {...}
  }
}
```

### Error Responses

**404 Not Found - Order Not Found**
```json
{
  "success": false,
  "message": "Order not found"
}
```

**422 Unprocessable Entity - Validation Error**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "customer_name": ["The customer name field is required."],
    "customer_phone": ["The customer phone field is required."]
  }
}
```

**500 Internal Server Error**
```json
{
  "success": false,
  "message": "Failed to update customer information",
  "error": "Database error message"
}
```

---

## Action Types

The `action_taken` field in the response indicates what action was performed:

| Action | Description |
|--------|-------------|
| `customer_created_and_order_reassigned` | New customer created with new phone number, order reassigned to new customer |
| `order_reassigned_to_existing_customer` | Existing customer with new phone found, order reassigned to that customer |
| `customer_info_updated` | Phone stayed same, customer's name/address updated |
| `customer_assigned_to_order` | Customer with new phone found and assigned to previously orphaned order |
| `customer_created_for_order` | New customer created for previously orphaned order |
| `no_changes_needed` | No changes detected (all fields same as before) |

---

## Business Logic

### Phone Number Change (Prime Identifier Logic)

**Step 1: Check if phone changed**
```
Old Phone: +8801711111111
New Phone: +8801722222222
Result: Phone changed → Trigger reassignment flow
```

**Step 2: Find or create customer with new phone**
```sql
SELECT * FROM customers WHERE phone = '+8801722222222';
```

**If Found:**
- Update customer's name and address to match request
- Reassign order to this existing customer
- Log: "order_reassigned_to_existing_customer"

**If Not Found:**
- Create new customer with new phone, name, address
- Auto-generate customer_code (e.g., CUST-000078)
- Reassign order to new customer
- Log: "customer_created_and_order_reassigned"

**Step 3: Update order metadata**
```json
{
  "customer_changes": [
    {
      "changed_at": "2026-03-05T14:30:00.000000Z",
      "changed_by": 12,
      "old_customer": {
        "id": 45,
        "name": "John Doe",
        "phone": "+8801711111111",
        "address": "Old Address"
      },
      "new_customer": {
        "id": 78,
        "name": "John Smith",
        "phone": "+8801722222222",
        "address": "123 New Street, Dhaka 1212"
      },
      "reason": "phone_number_changed"
    }
  ]
}
```

### Phone Same (Information Update)

**Step 1: Check if phone changed**
```
Old Phone: +8801711111111
New Phone: +8801711111111
Result: Same phone → Update existing customer
```

**Step 2: Update customer fields**
```php
if ($name changed) → Update name
if ($address changed) → Update address
```

**Step 3: Log changes in metadata**
```json
{
  "customer_changes": [
    {
      "changed_at": "2026-03-05T14:30:00.000000Z",
      "changed_by": 12,
      "old_info": {
        "id": 45,
        "name": "John Doe",
        "phone": "+8801711111111",
        "address": "Old Address"
      },
      "new_info": {
        "id": 45,
        "name": "John Smith",
        "phone": "+8801711111111",
        "address": "123 New Street, Dhaka 1212"
      },
      "reason": "customer_info_correction",
      "fields_changed": ["name", "address"]
    }
  ]
}
```

---

## Use Cases

### Use Case 1: Correct Wrong Phone Number
**Problem:** Employee entered wrong phone number when creating order  
**Solution:** Update phone to correct number  
**Result:** Order reassigned to correct customer (creates if doesn't exist)

**Example:**
```bash
# Wrong phone entered: +8801711111111
# Correct phone: +8801799999999

curl -X PATCH https://api.deshio.com/api/admin/orders/123/customer-info \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "customer_name": "Ahmed Rahman",
    "customer_phone": "+8801799999999",
    "customer_address": "456 Correct Street, Dhaka"
  }'
```

### Use Case 2: Customer Changed Phone Number
**Problem:** Customer got a new phone number after order was placed  
**Solution:** Update to new phone, reassign order to new customer record  
**Result:** Order tracked under new phone, old customer history preserved

### Use Case 3: Update Name/Address Only
**Problem:** Customer's name spelling was wrong or address incomplete  
**Solution:** Update name/address with same phone  
**Result:** Existing customer info corrected, no new customer created

**Example:**
```bash
# Fix spelling: "Jon Doe" → "John Doe"
# Add apartment number to address

curl -X PATCH https://api.deshio.com/api/admin/orders/123/customer-info \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "customer_name": "John Doe",
    "customer_phone": "+8801711111111",
    "customer_address": "House 42, Road 10, Dhanmondi, Dhaka 1209"
  }'
```

### Use Case 4: Transfer Order to Different Customer
**Problem:** Order was placed by mistake under wrong customer  
**Solution:** Change phone to correct customer's phone  
**Result:** Order transferred, both customers' history maintained

---

## Activity Logging

All changes are automatically logged via the `AutoLogsActivity` trait on both Customer and Order models.

### What Gets Logged

**Order Model Activity Log:**
- `customer_id` changed (if phone changed)
- `metadata` updated (customer_changes array)
- Employee who made the change
- Timestamp of change

**Customer Model Activity Log:**
- `name` changed
- `address` changed
- Employee who made the change
- Timestamp of change

### Viewing Activity Logs
```sql
-- View order activity logs
SELECT * FROM activity_log 
WHERE subject_type = 'App\\Models\\Order' 
AND subject_id = 123 
ORDER BY created_at DESC;

-- View customer activity logs
SELECT * FROM activity_log 
WHERE subject_type = 'App\\Models\\Customer' 
AND subject_id IN (45, 78) 
ORDER BY created_at DESC;
```

---

## Order Metadata Structure

The order's `metadata` JSON field stores the complete change history:

```json
{
  "customer_changes": [
    {
      "changed_at": "2026-03-05T14:30:00.000000Z",
      "changed_by": 12,
      "old_customer": {
        "id": 45,
        "name": "John Doe",
        "phone": "+8801711111111",
        "address": "House 10, Road 5, Gulshan, Dhaka"
      },
      "new_customer": {
        "id": 78,
        "name": "John Smith",
        "phone": "+8801722222222",
        "address": "123 New Street, Dhaka 1212"
      },
      "reason": "phone_number_changed"
    },
    {
      "changed_at": "2026-03-05T16:45:00.000000Z",
      "changed_by": 15,
      "old_info": {
        "id": 78,
        "name": "John Smith",
        "phone": "+8801722222222",
        "address": "123 New Street, Dhaka 1212"
      },
      "new_info": {
        "id": 78,
        "name": "John Smith",
        "phone": "+8801722222222",
        "address": "456 Updated Address, Dhaka 1215"
      },
      "reason": "customer_info_correction",
      "fields_changed": ["address"]
    }
  ]
}
```

---

## Edge Cases & Handling

### Edge Case 1: Customer Has Multiple Orders
**Scenario:** Customer 45 has 10 orders. Phone changed on Order 123.  
**Behavior:**  
- Only Order 123 is reassigned to new customer
- Customer 45's other 9 orders remain unchanged
- Both customers' order counts are NOT automatically updated (for data integrity)

### Edge Case 2: New Phone Belongs to Existing VIP Customer
**Scenario:** New phone +8801799999999 belongs to Customer 92 (VIP, 50 orders, 500k spent)  
**Behavior:**  
- Order reassigned to Customer 92
- Customer 92's name/address updated to match request
- Customer 92's order history grows by 1
- Original customer's history preserved

### Edge Case 3: Same Phone, No Actual Changes
**Scenario:** Request has identical name, phone, address  
**Behavior:**  
- No database updates
- `action_taken`: "no_changes_needed"
- Still returns success (idempotent)

### Edge Case 4: Phone Format Validation
**Scenario:** Phone format inconsistent (spaces, dashes, country code)  
**Recommendation:** Implement phone normalization:
```php
// Normalize phone: remove spaces, dashes, ensure +88 prefix
$normalizedPhone = '+88' . preg_replace('/[^0-9]/', '', $phone);
```

### Edge Case 5: Order Status Check
**Current:** No order status restrictions  
**Reason:** Allow corrections even after fulfillment/delivery  
**Note:** If needed, add status check in future:
```php
if (in_array($order->status, ['cancelled', 'refunded'])) {
    return response()->json([
        'success' => false,
        'message' => 'Cannot update customer info for cancelled/refunded orders'
    ], 422);
}
```

---

## Transaction Safety

All operations wrapped in database transaction:

```php
DB::beginTransaction();
try {
    // 1. Find or create customer
    // 2. Update customer info
    // 3. Reassign order if needed
    // 4. Update order metadata
    // 5. Save all changes
    
    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
    // Return error
}
```

**Guarantees:**
- Either all changes succeed, or none do
- No partial updates
- Data consistency maintained

---

## Testing Scenarios

### Test 1: Phone Changed, New Customer Created
```bash
# Pre-condition: Customer with +8801722222222 does NOT exist

curl -X PATCH http://localhost:8000/api/admin/orders/123/customer-info \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "customer_name": "New Customer",
    "customer_phone": "+8801722222222",
    "customer_address": "New Address"
  }'

# Expected: 
# - New customer created
# - Order reassigned
# - action_taken: "customer_created_and_order_reassigned"
```

### Test 2: Phone Changed, Existing Customer Found
```bash
# Pre-condition: Customer with +8801799999999 already exists (ID: 92)

curl -X PATCH http://localhost:8000/api/admin/orders/123/customer-info \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "customer_name": "Existing Customer",
    "customer_phone": "+8801799999999",
    "customer_address": "Some Address"
  }'

# Expected:
# - Order reassigned to customer 92
# - Customer 92's name/address updated
# - action_taken: "order_reassigned_to_existing_customer"
```

### Test 3: Name Updated, Phone Same
```bash
curl -X PATCH http://localhost:8000/api/admin/orders/123/customer-info \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "customer_name": "Updated Name",
    "customer_phone": "+8801711111111",
    "customer_address": "Old Address"
  }'

# Expected:
# - Customer name updated
# - Order stays with same customer
# - action_taken: "customer_info_updated"
```

### Test 4: Address Updated, Name/Phone Same
```bash
curl -X PATCH http://localhost:8000/api/admin/orders/123/customer-info \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "customer_name": "John Doe",
    "customer_phone": "+8801711111111",
    "customer_address": "New Complete Address Line 1, Line 2, Dhaka 1212"
  }'

# Expected:
# - Customer address updated
# - fields_changed: ["address"]
# - action_taken: "customer_info_updated"
```

### Test 5: Validation Error
```bash
curl -X PATCH http://localhost:8000/api/admin/orders/123/customer-info \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "customer_name": "",
    "customer_phone": ""
  }'

# Expected:
# - 422 Validation Error
# - Errors for customer_name and customer_phone
```

### Test 6: Order Not Found
```bash
curl -X PATCH http://localhost:8000/api/admin/orders/999999/customer-info \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "customer_name": "Test",
    "customer_phone": "+8801711111111",
    "customer_address": "Test"
  }'

# Expected:
# - 404 Not Found
# - Message: "Order not found"
```

---

## Frontend Integration

### React/Next.js Example

```typescript
interface UpdateCustomerInfoRequest {
  customer_name: string;
  customer_phone: string;
  customer_address?: string;
}

interface UpdateCustomerInfoResponse {
  success: boolean;
  message: string;
  data: {
    order: Order;
    action_taken: string;
    old_customer: CustomerInfo;
    new_customer: CustomerInfo;
  };
}

async function updateOrderCustomerInfo(
  orderId: number,
  data: UpdateCustomerInfoRequest,
  token: string
): Promise<UpdateCustomerInfoResponse> {
  const response = await fetch(
    `${API_BASE_URL}/api/admin/orders/${orderId}/customer-info`,
    {
      method: 'PATCH',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(data),
    }
  );

  if (!response.ok) {
    throw new Error('Failed to update customer info');
  }

  return response.json();
}

// Usage in component
const handleUpdateCustomerInfo = async () => {
  try {
    const result = await updateOrderCustomerInfo(
      123,
      {
        customer_name: 'John Smith',
        customer_phone: '+8801722222222',
        customer_address: '123 New Street, Dhaka',
      },
      authToken
    );

    if (result.success) {
      // Show success message
      if (result.data.action_taken === 'customer_created_and_order_reassigned') {
        toast.success('Order reassigned to new customer');
      } else if (result.data.action_taken === 'customer_info_updated') {
        toast.success('Customer information updated');
      }

      // Refresh order data
      refreshOrderDetails();
    }
  } catch (error) {
    toast.error('Failed to update customer info');
  }
};
```

### Form Component Example

```tsx
import { useState } from 'react';
import { Order } from '@/types';

interface Props {
  order: Order;
  onSuccess: () => void;
}

export function UpdateCustomerInfoForm({ order, onSuccess }: Props) {
  const [name, setName] = useState(order.customer.name);
  const [phone, setPhone] = useState(order.customer.phone);
  const [address, setAddress] = useState(order.customer.address || '');
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);

    try {
      const response = await updateOrderCustomerInfo(
        order.id,
        {
          customer_name: name,
          customer_phone: phone,
          customer_address: address,
        },
        authToken
      );

      if (response.success) {
        toast.success(response.message);
        
        // Show additional context
        if (response.data.action_taken === 'customer_created_and_order_reassigned') {
          toast.info('New customer created and order reassigned');
        }
        
        onSuccess();
      }
    } catch (error) {
      toast.error('Failed to update customer info');
    } finally {
      setLoading(false);
    }
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      <div>
        <label htmlFor="name">Customer Name</label>
        <input
          id="name"
          type="text"
          value={name}
          onChange={(e) => setName(e.target.value)}
          required
          maxLength={255}
          className="w-full border rounded px-3 py-2"
        />
      </div>

      <div>
        <label htmlFor="phone">Phone Number</label>
        <input
          id="phone"
          type="tel"
          value={phone}
          onChange={(e) => setPhone(e.target.value)}
          required
          maxLength={20}
          className="w-full border rounded px-3 py-2"
        />
        <p className="text-sm text-amber-600 mt-1">
          ⚠️ Changing phone will reassign order to different customer
        </p>
      </div>

      <div>
        <label htmlFor="address">Address</label>
        <textarea
          id="address"
          value={address}
          onChange={(e) => setAddress(e.target.value)}
          maxLength={500}
          rows={3}
          className="w-full border rounded px-3 py-2"
        />
      </div>

      <button
        type="submit"
        disabled={loading}
        className="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
      >
        {loading ? 'Updating...' : 'Update Customer Info'}
      </button>
    </form>
  );
}
```

---

## Security Considerations

### Authorization
- ✅ Employee authentication required
- ✅ Currently no role-based restrictions
- 💡 Future: Consider restricting to managers/admins for phone changes

### Input Validation
- ✅ All fields validated (max lengths, required fields)
- 💡 Future: Add phone format validation (regex for Bangladesh numbers)
- 💡 Future: Add name validation (no special characters)

### Audit Trail
- ✅ All changes logged in activity_log table
- ✅ Changes stored in order metadata JSON
- ✅ Employee who made change tracked
- ✅ Timestamps on all changes

### Data Integrity
- ✅ Database transactions ensure atomicity
- ✅ Customer history preserved (no deletion)
- ✅ Order history maintained

---

## Future Enhancements

### Enhancement 1: Phone Number Normalization
```php
// Normalize phone format before searching/creating
private function normalizePhone(string $phone): string {
    // Remove all non-numeric characters
    $cleaned = preg_replace('/[^0-9]/', '', $phone);
    
    // Ensure +88 prefix for Bangladesh
    if (!str_starts_with($cleaned, '88')) {
        $cleaned = '88' . $cleaned;
    }
    
    return '+' . $cleaned;
}
```

### Enhancement 2: Bulk Customer Update
```
POST /api/admin/orders/bulk-update-customer-info

Body:
{
  "order_ids": [123, 124, 125],
  "customer_name": "Bulk Customer",
  "customer_phone": "+8801799999999"
}
```

### Enhancement 3: Customer Merge
```
POST /api/admin/customers/merge

Body:
{
  "source_customer_id": 45,
  "target_customer_id": 78,
  "keep_target_info": true
}

Result:
- All orders from customer 45 moved to customer 78
- Customer 45 soft-deleted or marked as merged
```

### Enhancement 4: Change History API
```
GET /api/admin/orders/{id}/customer-change-history

Response:
{
  "success": true,
  "data": {
    "order_id": 123,
    "changes": [
      {
        "changed_at": "2026-03-05T14:30:00Z",
        "changed_by": "Ahmed Khan",
        "old_customer": {...},
        "new_customer": {...},
        "reason": "phone_number_changed"
      }
    ]
  }
}
```

---

## Summary

✅ **Implemented:** Customer info update API with phone as prime identifier  
✅ **Smart Logic:** Automatically handles phone changes vs info updates  
✅ **Audit Trail:** Complete change history in metadata and activity logs  
✅ **Transaction Safe:** All operations atomic (all or nothing)  
✅ **Well Documented:** Comprehensive API docs with examples  

**Endpoint:** `PATCH /api/admin/orders/{id}/customer-info`  
**Controller:** [OrderController.php](d:\Intern\deshio-erp-backend\backend\app\Http\Controllers\OrderController.php#L771)  
**Route:** [api.php](d:\Intern\deshio-erp-backend\backend\routes\api.php#L1021)

Ready for frontend integration! 🚀
