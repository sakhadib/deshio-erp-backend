# Pre-Order System Documentation

## Overview
The pre-order system allows customers to place orders for products that are currently out of stock or don't have batches assigned yet. This is essential for managing customer demand before inventory arrives.

---

## Key Features

### 1. Optional Batch Assignment
- Orders can be created **without** specifying `batch_id` for items
- When `batch_id` is null, the item is automatically marked as a pre-order
- Stock validation is **skipped** for pre-order items
- Batches can be assigned later when inventory arrives

### 2. Automatic Pre-Order Detection
- **Order-level**: An order is marked `is_preorder: true` if ANY item lacks a batch
- **Item-level**: Each item has `is_preorder: true/false` based on batch presence
- No need to manually specify pre-order status

### 3. Financial Tracking
- **COGS**: Set to `0` for pre-order items (updated when batch assigned)
- **Tax**: Calculated from `unit_price` if no batch provided
- **Revenue**: Tracked immediately upon payment
- **Tax Liability**: Recorded at time of sale

---

## API Changes

### Creating Orders with Pre-Orders

#### Endpoint
```
POST /api/orders
```

#### Request Body
```json
{
  "order_type": "counter|social_commerce|ecommerce",
  "customer_id": 1,
  "store_id": 1,
  "items": [
    {
      "product_id": 10,
      "batch_id": null,           // ← NULL for pre-order items
      "quantity": 5,
      "unit_price": 1000,         // Required when batch_id is null
      "discount_amount": 0
    },
    {
      "product_id": 15,
      "batch_id": 25,              // ← Has batch, regular order
      "quantity": 2,
      "unit_price": 500,
      "discount_amount": 0
    }
  ],
  "discount_amount": 0,
  "shipping_amount": 0,
  "notes": "Mixed order with pre-order items"
}
```

#### Response
```json
{
  "success": true,
  "message": "Order created successfully",
  "data": {
    "order": {
      "id": 100,
      "order_number": "ORD-20251208-0100",
      "customer_id": 1,
      "store_id": 1,
      "order_type": "counter",
      "status": "pending",
      "payment_status": "pending",
      "is_preorder": true,        // ← TRUE because item 1 has no batch
      "subtotal": 5500,
      "tax_amount": 0,
      "total_amount": 5500,
      "items": [
        {
          "id": 200,
          "product_id": 10,
          "product_batch_id": null,  // ← No batch
          "is_preorder": true,       // ← Pre-order item
          "quantity": 5,
          "unit_price": 1000,
          "total_amount": 5000,
          "cogs": 0                  // ← COGS is 0 until batch assigned
        },
        {
          "id": 201,
          "product_id": 15,
          "product_batch_id": 25,    // ← Has batch
          "is_preorder": false,      // ← Regular item
          "quantity": 2,
          "unit_price": 500,
          "total_amount": 1000,
          "cogs": 600                // ← COGS calculated from batch
        }
      ]
    }
  }
}
```

---

## Validation Rules

### Order Items

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `product_id` | integer | ✅ Yes | Must exist in products table |
| `batch_id` | integer | ❌ No | Nullable for pre-orders |
| `quantity` | integer | ✅ Yes | Must be > 0 |
| `unit_price` | decimal | ✅ Yes | Required when batch_id is null |
| `discount_amount` | decimal | ❌ No | Default: 0 |
| `barcode` | string | ❌ No | Only validated if batch exists |

### Important Notes
- **`batch_id`**: Can be `null` or omitted for pre-orders
- **`unit_price`**: Must be provided when creating pre-orders (since no batch price available)
- **Stock validation**: Only performed when `batch_id` is provided
- **Barcode validation**: Only performed when both `batch_id` and `barcode` are provided

---

## Frontend Implementation Guide

### 1. Product Selection UI

#### Show Batch Availability
```javascript
// Check if product has available batches
if (product.batches && product.batches.length > 0) {
  // Show batch selection dropdown
  showBatchSelector(product.batches);
} else {
  // Show "Pre-order" badge
  showPreOrderBadge();
  // Allow order without batch selection
}
```

#### Batch Selector Component
```jsx
function BatchSelector({ product, selectedBatch, onChange }) {
  const hasBatches = product.batches?.length > 0;
  
  if (!hasBatches) {
    return (
      <div className="pre-order-badge">
        <Icon name="clock" />
        Pre-order (No stock available)
      </div>
    );
  }
  
  return (
    <select onChange={(e) => onChange(e.target.value)}>
      <option value="">Select batch (or pre-order)</option>
      {product.batches.map(batch => (
        <option key={batch.id} value={batch.id}>
          Batch #{batch.id} - {batch.quantity} available - ৳{batch.selling_price}
        </option>
      ))}
    </select>
  );
}
```

### 2. Creating Pre-Orders

#### Example: Add to Cart
```javascript
function addToCart(product, quantity, selectedBatchId = null) {
  const cartItem = {
    product_id: product.id,
    batch_id: selectedBatchId, // Can be null for pre-orders
    quantity: quantity,
    unit_price: selectedBatchId 
      ? product.batches.find(b => b.id === selectedBatchId).selling_price
      : product.base_price || 0, // Use product base price for pre-orders
    discount_amount: 0
  };
  
  // If no batch, show pre-order indicator
  if (!selectedBatchId) {
    cartItem.is_preorder = true;
  }
  
  dispatch(addItemToCart(cartItem));
}
```

#### Example: Submit Order
```javascript
async function submitOrder(cart, customer, storeId) {
  const orderData = {
    order_type: 'counter', // or 'social_commerce', 'ecommerce'
    customer_id: customer.id,
    store_id: storeId,
    items: cart.items.map(item => ({
      product_id: item.product_id,
      batch_id: item.batch_id, // null for pre-orders
      quantity: item.quantity,
      unit_price: item.unit_price,
      discount_amount: item.discount_amount || 0
    })),
    discount_amount: cart.discount || 0,
    shipping_amount: cart.shipping || 0,
    notes: cart.notes || ''
  };
  
  try {
    const response = await api.post('/api/orders', orderData);
    
    // Check if order contains pre-orders
    if (response.data.order.is_preorder) {
      showNotification('Order created with pre-order items. Stock will be allocated when available.');
    }
    
    return response.data;
  } catch (error) {
    handleError(error);
  }
}
```

### 3. Displaying Orders

#### Order List Item
```jsx
function OrderListItem({ order }) {
  return (
    <div className="order-item">
      <div className="order-header">
        <span className="order-number">{order.order_number}</span>
        {order.is_preorder && (
          <span className="badge badge-warning">
            Pre-order
          </span>
        )}
        <span className={`badge badge-${order.status}`}>
          {order.status}
        </span>
      </div>
      
      <div className="order-items">
        {order.items.map(item => (
          <div key={item.id} className="order-item-row">
            <span>{item.product_name}</span>
            <span>x{item.quantity}</span>
            {item.is_preorder && (
              <span className="badge badge-sm badge-warning">
                Pre-order
              </span>
            )}
            {!item.product_batch_id && (
              <span className="text-muted">
                Batch pending
              </span>
            )}
          </div>
        ))}
      </div>
    </div>
  );
}
```

### 4. Order Details View

```jsx
function OrderDetails({ order }) {
  const hasPreOrderItems = order.items.some(item => item.is_preorder);
  
  return (
    <div className="order-details">
      <div className="order-header">
        <h2>Order #{order.order_number}</h2>
        {order.is_preorder && (
          <div className="alert alert-warning">
            <Icon name="info" />
            This order contains pre-order items. Stock will be allocated when inventory arrives.
          </div>
        )}
      </div>
      
      <table className="items-table">
        <thead>
          <tr>
            <th>Product</th>
            <th>Batch</th>
            <th>Quantity</th>
            <th>Unit Price</th>
            <th>Total</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          {order.items.map(item => (
            <tr key={item.id}>
              <td>{item.product_name}</td>
              <td>
                {item.product_batch_id 
                  ? `Batch #${item.product_batch_id}` 
                  : <span className="text-warning">Not assigned</span>
                }
              </td>
              <td>{item.quantity}</td>
              <td>৳{item.unit_price}</td>
              <td>৳{item.total_amount}</td>
              <td>
                {item.is_preorder && (
                  <span className="badge badge-warning">Pre-order</span>
                )}
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
```

---

## Business Logic

### When to Use Pre-Orders

1. **Out of Stock**: Product exists but no inventory available
2. **Future Stock**: Product will arrive soon, accept orders now
3. **Custom Orders**: Made-to-order or custom products
4. **Seasonal Items**: Pre-season orders before stock arrives

### Pre-Order Workflow

```
1. Customer orders product without batch
   ↓
2. Order created with is_preorder = true
   ↓
3. Payment processed (if paid)
   ↓
4. Finance records revenue immediately
   ↓
5. Inventory team notified of pre-order demand
   ↓
6. When stock arrives:
   - Create batch
   - Assign batch to pre-order items
   - Update COGS
   - Process fulfillment
   ↓
7. Ship to customer
```

### Stock Allocation Priority

When new inventory arrives, prioritize:
1. **Pre-orders** (oldest first)
2. **Regular pending orders**
3. **Available for new orders**

---

## Edge Cases & Handling

### Mixed Orders (Pre-order + Regular)
- **Supported**: ✅ Yes
- **Behavior**: Order marked as `is_preorder: true`
- **Fulfillment**: Regular items can ship immediately, pre-order items wait for stock
- **Frontend**: Show mixed status clearly

### Pricing
- **With Batch**: Use `batch.selling_price` (includes tax)
- **Without Batch**: Use provided `unit_price` (should include tax)
- **Tax Calculation**: Automatic based on price (inclusive tax system)

### Payment
- **Allowed**: ✅ Yes, can pay for pre-orders
- **Recommendation**: Accept partial payment or full payment upfront
- **Refunds**: Standard refund process applies if pre-order cancelled

### Cancellation
- **Before Batch Assigned**: Easy cancellation, no stock impact
- **After Batch Assigned**: Standard cancellation process

---

## API Response Fields Reference

### Order Object
```typescript
interface Order {
  id: number;
  order_number: string;
  customer_id: number;
  store_id: number;
  order_type: 'counter' | 'social_commerce' | 'ecommerce';
  status: 'pending' | 'confirmed' | 'processing' | 'completed' | 'cancelled';
  payment_status: 'pending' | 'partial' | 'paid' | 'refunded';
  fulfillment_status: 'pending_fulfillment' | 'fulfilled' | null;
  is_preorder: boolean;          // NEW: True if any item has no batch
  subtotal: number;
  tax_amount: number;
  discount_amount: number;
  shipping_amount: number;
  total_amount: number;
  outstanding_amount: number;
  items: OrderItem[];
  created_at: string;
  updated_at: string;
}
```

### OrderItem Object
```typescript
interface OrderItem {
  id: number;
  order_id: number;
  product_id: number;
  product_batch_id: number | null;  // NULL for pre-orders
  product_name: string;
  product_sku: string;
  quantity: number;
  unit_price: number;
  discount_amount: number;
  tax_amount: number;
  total_amount: number;
  cogs: number;                     // 0 for pre-orders until batch assigned
  is_preorder: boolean;             // NEW: True if no batch
  created_at: string;
  updated_at: string;
}
```

---

## Testing Checklist

### Frontend Testing

- [ ] Can add product without selecting batch
- [ ] Pre-order badge displays correctly
- [ ] Can submit order with null batch_id
- [ ] Order list shows pre-order indicator
- [ ] Order details shows batch status
- [ ] Mixed orders display correctly
- [ ] Payment works for pre-orders
- [ ] Error handling for invalid requests

### Backend Testing

```bash
# Test 1: Create pre-order
POST /api/orders
{
  "order_type": "counter",
  "customer_id": 1,
  "store_id": 1,
  "items": [{
    "product_id": 1,
    "batch_id": null,
    "quantity": 5,
    "unit_price": 1000
  }]
}
# Expected: Order created with is_preorder=true, item.is_preorder=true

# Test 2: Create mixed order
POST /api/orders
{
  "order_type": "counter",
  "customer_id": 1,
  "store_id": 1,
  "items": [
    {
      "product_id": 1,
      "batch_id": null,
      "quantity": 5,
      "unit_price": 1000
    },
    {
      "product_id": 2,
      "batch_id": 10,
      "quantity": 2,
      "unit_price": 500
    }
  ]
}
# Expected: Order with is_preorder=true, first item is_preorder=true, second item is_preorder=false

# Test 3: Verify stock not checked for pre-orders
POST /api/orders
{
  "items": [{
    "product_id": 99,
    "batch_id": null,
    "quantity": 1000000
  }]
}
# Expected: Success (no stock validation)

# Test 4: Verify barcode ignored for pre-orders
POST /api/orders
{
  "items": [{
    "product_id": 1,
    "batch_id": null,
    "barcode": "INVALID_BARCODE",
    "quantity": 5,
    "unit_price": 1000
  }]
}
# Expected: Success (barcode not validated without batch)
```

---

## Migration Notes

### Database Changes
- ✅ `order_items.product_batch_id` already nullable
- ✅ Foreign key set to `ON DELETE SET NULL`
- ✅ No migration needed

### Backward Compatibility
- ✅ Existing orders unaffected
- ✅ All existing orders have batches
- ✅ Frontend can be updated gradually

---

## Common Issues & Solutions

### Issue 1: "batch_id is required"
**Cause**: Old frontend sending requests to updated backend  
**Solution**: Update frontend to allow null batch_id

### Issue 2: Unit price missing for pre-orders
**Cause**: Frontend not sending unit_price when batch_id is null  
**Solution**: Always send unit_price, use product base price or manual entry

### Issue 3: COGS showing as 0
**Expected**: COGS is 0 for pre-orders until batch assigned  
**Solution**: Display "Pending" instead of 0 in reports

### Issue 4: Can't fulfill pre-order
**Cause**: No batch assigned yet  
**Solution**: Assign batch to order items when stock arrives, then fulfill

---

## Contact & Support

For questions or issues:
- **Backend Team**: [Add contact]
- **API Documentation**: `/api/documentation`
- **Issue Tracker**: [Add link]

Last Updated: December 8, 2025
