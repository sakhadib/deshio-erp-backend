# POS Manual Salesman Entry Guide

## Overview

The POS system supports **manual salesman assignment** for counter sales. This allows managers to create orders and assign them to specific salesmen for commission tracking.

---

## When to Use Manual Salesman Entry

✅ **Shared POS Terminals** - Multiple salesmen use same computer  
✅ **Manager Creates Orders** - Supervisor places order on behalf of salesman  
✅ **Commission Tracking** - Ensure correct salesman gets credit  
✅ **Order Reassignment** - Transfer order to different salesman  

---

## How It Works

### Default Behavior (Auto-Assignment)

When `salesman_id` is **NOT** provided, the system automatically uses the authenticated employee:

```http
POST /api/orders
{
  "order_type": "counter",
  "store_id": 1,
  // salesman_id omitted
  "customer": {...},
  "items": [...]
}

Result:
✅ created_by = Auth::id() (logged in employee)
```

### Manual Assignment

When `salesman_id` **IS** provided, the system uses the specified employee:

```http
POST /api/orders
{
  "order_type": "counter",
  "store_id": 1,
  "salesman_id": 7,  // Manual assignment
  "customer": {...},
  "items": [...]
}

Result:
✅ created_by = 7 (specified employee)
```

---

## Real-World Scenarios

### Scenario 1: Salesman Uses Own Login

**Context**: Ahmed logs into POS and makes a sale

```http
POST /api/orders
{
  "order_type": "counter",
  "store_id": 1,
  // No salesman_id needed
  "customer": {
    "name": "John Doe",
    "phone": "01712345678"
  },
  "items": [
    {
      "product_id": 1,
      "batch_id": 5,
      "quantity": 2,
      "unit_price": 75000.00
    }
  ],
  "payment": {
    "payment_method_id": 1,
    "amount": 150000.00,
    "payment_type": "full"
  }
}

Response:
{
  "salesman": {
    "id": 5,  // Ahmed's ID (from Auth)
    "name": "Ahmed Rahman"
  }
}
```

✅ **Ahmed gets commission**  
✅ **Order appears in Ahmed's statistics**

---

### Scenario 2: Manager Creates Order for Salesman

**Context**: Manager logged in at shared POS, customer asks for specific salesman

```http
POST /api/orders
{
  "order_type": "counter",
  "store_id": 1,
  "salesman_id": 12,  // Fatima's employee ID
  "customer": {
    "name": "Sarah Ahmed",
    "phone": "01987654321"
  },
  "items": [
    {
      "product_id": 3,
      "batch_id": 10,
      "quantity": 1,
      "unit_price": 45000.00
    }
  ],
  "payment": {
    "payment_method_id": 2,
    "amount": 45000.00,
    "payment_type": "full"
  }
}

Response:
{
  "salesman": {
    "id": 12,  // Fatima (specified)
    "name": "Fatima Khan"
  }
}
```

✅ **Fatima gets commission (not manager)**  
✅ **Order credited to Fatima's statistics**  
✅ **Manager is transaction processor, not salesman**

---

### Scenario 3: Shared Terminal - Multiple Salesmen

**Context**: 3 salesmen share one POS terminal at electronics counter

**Setup**: Manager stays logged in, salesmen select their ID from dropdown

```bash
# Salesman 1 makes sale at 10:00 AM
POST /api/orders
{
  "salesman_id": 5,  // Ahmed
  "order_type": "counter",
  ...
}

# Salesman 2 makes sale at 10:30 AM
POST /api/orders
{
  "salesman_id": 8,  // Karim
  "order_type": "counter",
  ...
}

# Salesman 3 makes sale at 11:00 AM
POST /api/orders
{
  "salesman_id": 12,  // Fatima
  "order_type": "counter",
  ...
}
```

✅ **Each salesman gets correct commission**  
✅ **End-of-day report shows individual performance**  
✅ **No need for logout/login between sales**

---

### Scenario 4: Phone Order Taken by Floor Manager

**Context**: Customer calls shop, manager takes order but assigns to salesman who helped customer before

```http
POST /api/orders
{
  "order_type": "counter",  // In-person sale
  "store_id": 1,
  "salesman_id": 7,  // Original salesman who helped customer
  "customer": {
    "name": "Ali Hassan",
    "phone": "01812345678"
  },
  "items": [...],
  "notes": "Customer called to complete purchase. Helped by Ahmed last week.",
  "payment": {
    "payment_method_id": 2,  // bKash
    "amount": 25000.00,
    "payment_type": "partial"
  }
}
```

✅ **Original salesman gets credit**  
✅ **Fair commission distribution**  
✅ **Notes track context**

---

## POS UI Implementation Suggestions

### Login Screen
```
┌─────────────────────────────┐
│   Employee Login            │
│                             │
│   Employee Code: ________   │
│   PIN: ________             │
│                             │
│   [ Login ]                 │
└─────────────────────────────┘
```

### Sale Screen (Auto Mode)
```
┌─────────────────────────────────────┐
│  Logged in as: Ahmed Rahman         │
│  Sales will be credited to you      │
│                                     │
│  [ Switch to Manual Entry ]         │
└─────────────────────────────────────┘
```

### Sale Screen (Manual Mode)
```
┌─────────────────────────────────────┐
│  Logged in as: Manager              │
│                                     │
│  Assign sale to:                    │
│  ┌──────────────────────┐           │
│  │ Select Salesman ▼    │           │
│  └──────────────────────┘           │
│    - Ahmed Rahman (#5)              │
│    - Karim Sheikh (#8)              │
│    - Fatima Khan (#12)              │
│                                     │
│  [ Back to Auto Mode ]              │
└─────────────────────────────────────┘
```

---

## API Field Reference

```javascript
{
  // Optional field for manual salesman assignment
  "salesman_id": 7,  // integer|nullable|exists:employees,id
  
  // Other order fields...
  "order_type": "counter",
  "store_id": 1,
  "customer": {...},
  "items": [...]
}
```

**Validation Rules:**
- `salesman_id`: Optional
- Must be valid employee ID if provided
- If omitted, uses `Auth::id()`
- Available for all order types (counter, social_commerce, ecommerce)

---

## Commission Tracking

### View Orders by Salesman

```http
# Get all orders for specific salesman
GET /api/orders?created_by=7

# Get salesman statistics
GET /api/orders/statistics

Response:
{
  "top_salesmen": [
    {
      "employee_id": 7,
      "employee_name": "Ahmed Rahman",
      "order_count": 45,
      "total_sales": "1500000.00"  // Commission base
    }
  ]
}
```

### Calculate Commission

```javascript
// Example: 2% commission on total sales
const salesmanStats = {
  employee_id: 7,
  employee_name: "Ahmed Rahman",
  total_sales: 1500000.00
};

const commissionRate = 0.02;  // 2%
const commission = salesmanStats.total_sales * commissionRate;

console.log(`Commission for ${salesmanStats.employee_name}: ${commission}`);
// Output: Commission for Ahmed Rahman: 30000
```

---

## Security Considerations

### Permission Control

```php
// In your middleware/policy
if ($request->filled('salesman_id')) {
    // Only managers can assign orders to other salesmen
    if (!Auth::user()->hasRole('manager')) {
        return response()->json([
            'success' => false,
            'message' => 'Only managers can assign orders to other salesmen'
        ], 403);
    }
    
    // Verify salesman is in same store
    $salesman = Employee::findOrFail($request->salesman_id);
    if ($salesman->store_id != $request->store_id) {
        return response()->json([
            'success' => false,
            'message' => 'Cannot assign order to salesman from different store'
        ], 403);
    }
}
```

### Audit Trail

Every order tracks:
- `created_by` - Salesman who gets credit
- `created_at` - When order created
- `updated_by` - Who modified order
- `updated_at` - When modified

```sql
-- Audit query: Find orders where creator != salesman
SELECT 
    o.order_number,
    creator.name as creator_name,
    salesman.name as salesman_name,
    o.total_amount
FROM orders o
JOIN employees creator ON o.updated_by = creator.id
JOIN employees salesman ON o.created_by = salesman.id
WHERE o.updated_by != o.created_by
  AND o.order_type = 'counter';
```

---

## Best Practices

### ✅ DO:
- Use auto-assignment when salesman is logged in
- Use manual assignment for shared terminals
- Document assignment reason in notes
- Verify salesman is from correct store
- Check manager permission for manual assignment

### ❌ DON'T:
- Let salesmen assign to each other (permission required)
- Assign to inactive employees
- Assign cross-store (different locations)
- Forget to track in audit logs

---

## Testing Checklist

- [ ] Create order without salesman_id (auto-assignment)
- [ ] Create order with salesman_id (manual assignment)
- [ ] Verify salesman statistics show correct totals
- [ ] Test manager permission for manual assignment
- [ ] Verify salesman from same store only
- [ ] Check commission calculations
- [ ] Test shared terminal scenario
- [ ] Validate audit trail

---

## Quick Reference

```bash
# Auto-assignment (default)
POST /api/orders
{
  "order_type": "counter",
  # salesman_id omitted → uses Auth::id()
}

# Manual assignment
POST /api/orders
{
  "order_type": "counter",
  "salesman_id": 7  # specified salesman gets credit
}

# Get salesman's orders
GET /api/orders?created_by=7

# Get salesman stats
GET /api/orders/statistics
```

---

**Perfect for**: Retail stores, electronics shops, multi-salesman counters, commission-based sales teams! 🎯
