# Frontend Integration Guide - API Updates & Fixes

## Date: December 8, 2025

This document covers critical fixes and adoption guidelines for the frontend team regarding order management, store assignment, and the new inclusive tax system.

---

## 🔧 Critical Fixes Implemented

### 1. **Store Assignment - Null Store Handling**

**Issue Fixed:** Orders without store assignment (e-commerce orders) were causing frontend crashes when trying to access `store.id` or `store.name`.

**Solution:** The `store` field in order responses is now properly nullable.

#### Response Structure Change

**Before (would crash if store was null):**
```json
{
  "order": {
    "id": 1,
    "store": {
      "id": 1,
      "name": "Main Store"
    }
  }
}
```

**After (null-safe):**
```json
{
  "order": {
    "id": 1,
    "store": null  // ← Can be null for unassigned e-commerce orders
  }
}
```

Or when assigned:
```json
{
  "order": {
    "id": 1,
    "store": {
      "id": 1,
      "name": "Main Store"
    }
  }
}
```

#### Frontend Code Update Required

**❌ Old Code (will crash):**
```javascript
// Don't do this
const storeName = order.store.name;
const storeId = order.store.id;
```

**✅ New Code (safe):**
```javascript
// Do this instead
const storeName = order.store?.name || 'Not Assigned';
const storeId = order.store?.id || null;

// Or with conditional rendering
{order.store ? (
  <div>Store: {order.store.name}</div>
) : (
  <div className="text-warning">Awaiting Store Assignment</div>
)}
```

---

### 2. **Product Creation - Brand Field Made Optional**

**Issue Fixed:** Products couldn't be created without a brand, even though the database allowed null values.

**Solution:** The `brand` field validation is now nullable in both create and update operations.

#### API Changes

**Endpoint:** `POST /api/products`

**Before:**
```json
{
  "name": "Product Name",
  "sku": "SKU-001",
  "category_id": 1,
  "vendor_id": 1,
  "brand": "Required Field"  // ← Was required
}
```

**After:**
```json
{
  "name": "Product Name",
  "sku": "SKU-001",
  "category_id": 1,
  "vendor_id": 1,
  "brand": "Optional Brand"  // ← Now optional
}
```

Or simply omit it:
```json
{
  "name": "Product Name",
  "sku": "SKU-001",
  "category_id": 1,
  "vendor_id": 1
  // brand field can be omitted
}
```

#### Frontend Form Update

```javascript
// Make brand field optional in your form validation
const productSchema = {
  name: { required: true },
  sku: { required: true },
  category_id: { required: true },
  vendor_id: { required: true },
  brand: { required: false } // ← Update this
};
```

---

### 3. **Order Filtering - New Query Parameters**

New filtering options for order listing to help with store assignment workflow.

#### Endpoint: `GET /api/orders`

**New Query Parameters:**

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `store_id` | string/number | Filter by store | `?store_id=1` |
| `store_id=unassigned` | string | Get only unassigned orders | `?store_id=unassigned` |
| `store_id=null` | string | Same as unassigned | `?store_id=null` |
| `pending_assignment` | boolean | E-commerce orders awaiting store | `?pending_assignment=true` |

**Examples:**

```bash
# Get all orders for store 1
GET /api/orders?store_id=1

# Get all unassigned orders
GET /api/orders?store_id=unassigned

# Get e-commerce orders pending store assignment
GET /api/orders?pending_assignment=true

# Combine filters
GET /api/orders?order_type=ecommerce&pending_assignment=true
```

**Response Example:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 123,
        "order_number": "ORD-2025-001",
        "order_type": "ecommerce",
        "status": "pending_assignment",
        "store": null,  // ← Not assigned yet
        "customer": {
          "id": 45,
          "name": "John Doe"
        }
      }
    ]
  }
}
```

---

## 💰 Inclusive Tax System - Major Update

The ERP now uses **inclusive tax pricing** where tax is embedded in the selling price.

### What Changed

**Old System (EXCLUSIVE):**
- Sell Price: 1000 BDT
- Tax: 2% → 20 BDT added
- Customer Pays: **1020 BDT**

**New System (INCLUSIVE):**
- Sell Price: 1000 BDT (includes 2% tax)
- Tax Extracted: 19.61 BDT
- Customer Pays: **1000 BDT**

### API Response Changes

#### Product Batch Response

**New Fields Added:**

```json
{
  "batch": {
    "id": 1,
    "product_id": 1,
    "cost_price": "800.00",
    "sell_price": "1000.00",
    "tax_percentage": "2.00",        // ← NEW: Tax rate
    "base_price": "980.39",          // ← NEW: Price excluding tax
    "tax_amount": "19.61",           // ← NEW: Tax per unit
    "quantity": 100
  }
}
```

**Calculation:**
- `base_price = sell_price / (1 + tax_percentage/100)`
- `tax_amount = sell_price - base_price`

#### Order Response

```json
{
  "order": {
    "id": 1,
    "order_number": "ORD-001",
    "subtotal": "5000.00",      // ← Includes tax
    "tax_amount": "98.05",      // ← Tax extracted from items
    "discount_amount": "0.00",
    "shipping_amount": "0.00",
    "total_amount": "5000.00",  // ← subtotal - discount + shipping (NO TAX ADDED)
    "items": [
      {
        "quantity": 5,
        "unit_price": "1000.00",    // ← Inclusive price
        "tax_amount": "98.05",      // ← Tax for this item
        "total_amount": "5000.00"
      }
    ]
  }
}
```

**Key Points:**
- `subtotal` now includes tax (it's in the item prices)
- `tax_amount` shows the tax extracted from items
- `total_amount` = subtotal - discount + shipping (tax is NOT added separately)

### Frontend Display Guidelines

#### Price Display

```javascript
// Display inclusive price to customer
<div className="price">
  <span className="amount">{item.unit_price} BDT</span>
  <span className="tax-info">(incl. {item.tax_percentage}% tax)</span>
</div>
```

#### Invoice/Receipt Format

```
Product A x 5                    5000.00 BDT
  (Price includes 2% tax)

Subtotal:                        5000.00 BDT
Discount:                           0.00 BDT
Shipping:                           0.00 BDT
                                ---------------
Total:                           5000.00 BDT

Tax Summary:
  Tax collected (2%):               98.05 BDT
  Net Revenue:                    4901.95 BDT
```

#### Creating Batches with Tax

```javascript
// Frontend form for creating batch
const createBatch = async (data) => {
  await api.post('/api/batches', {
    product_id: 1,
    store_id: 1,
    quantity: 100,
    cost_price: 800,
    sell_price: 1000,      // This is what customer pays
    tax_percentage: 2.0    // System calculates base_price automatically
  });
};

// System automatically calculates:
// base_price = 980.39 BDT
// tax_amount = 19.61 BDT
```

---

## 📊 Order Statuses & Workflow

### E-commerce Order Lifecycle

```
1. pending_assignment (store = null)
   ↓ Employee assigns store
2. confirmed (store assigned)
   ↓ Items picked and packed
3. ready_for_shipment
   ↓ Shipped via Pathao
4. shipped (has tracking_number)
   ↓ Customer receives
5. delivered
```

### Status Meanings

| Status | Store Required? | Description |
|--------|----------------|-------------|
| `pending_assignment` | ❌ No | E-commerce order awaiting store assignment |
| `pending` | ✅ Yes | Counter/social commerce - awaiting confirmation |
| `confirmed` | ✅ Yes | Order confirmed, ready for fulfillment |
| `ready_for_shipment` | ✅ Yes | Packed and ready to ship |
| `shipped` | ✅ Yes | In transit (has tracking number) |
| `delivered` | ✅ Yes | Customer received |
| `cancelled` | ❌ Optional | Order cancelled |

---

## 🔄 Store Assignment Workflow

### Step 1: List Unassigned Orders

```javascript
// Get orders pending store assignment
const response = await fetch('/api/orders?pending_assignment=true');

// Response
{
  "data": {
    "data": [
      {
        "id": 123,
        "order_number": "ORD-001",
        "order_type": "ecommerce",
        "status": "pending_assignment",
        "store": null,  // ← No store yet
        "customer": { ... }
      }
    ]
  }
}
```

### Step 2: Assign Store

```javascript
// Endpoint: POST /api/orders/{id}/assign-store
const assignStore = async (orderId, storeId) => {
  const response = await fetch(`/api/orders/${orderId}/assign-store`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({
      store_id: storeId
    })
  });
  
  return response.json();
};

// Response
{
  "success": true,
  "message": "Store assigned successfully",
  "order": {
    "id": 123,
    "store": {
      "id": 1,
      "name": "Main Store"
    },
    "status": "confirmed"  // ← Status updated
  }
}
```

### Step 3: Fetch Order After Assignment

```javascript
// Order now has store assigned
const order = await fetch('/api/orders/123');

// Response - store is now populated
{
  "order": {
    "id": 123,
    "store": {
      "id": 1,
      "name": "Main Store"
    },
    "status": "confirmed"
  }
}
```

---

## 🛠️ Frontend Component Updates Checklist

### Order List Component

```javascript
// ✅ Update to handle null stores
const OrderCard = ({ order }) => {
  return (
    <div className="order-card">
      <h3>{order.order_number}</h3>
      
      {/* Handle null store */}
      <div className="store-info">
        {order.store ? (
          <span>Store: {order.store.name}</span>
        ) : (
          <span className="badge badge-warning">
            Awaiting Store Assignment
          </span>
        )}
      </div>
      
      {/* Show assign button for unassigned orders */}
      {!order.store && order.status === 'pending_assignment' && (
        <button onClick={() => showAssignStoreModal(order.id)}>
          Assign Store
        </button>
      )}
    </div>
  );
};
```

### Product Form Component

```javascript
// ✅ Make brand optional
const ProductForm = () => {
  const [formData, setFormData] = useState({
    name: '',
    sku: '',
    category_id: '',
    vendor_id: '',
    brand: ''  // ← Can be empty now
  });
  
  return (
    <form>
      {/* Other fields */}
      
      <input
        name="brand"
        value={formData.brand}
        placeholder="Brand (Optional)"  // ← Update label
        onChange={handleChange}
      />
    </form>
  );
};
```

### Batch Creation Form

```javascript
// ✅ Add tax percentage field
const BatchForm = () => {
  const [formData, setFormData] = useState({
    product_id: '',
    store_id: '',
    quantity: '',
    cost_price: '',
    sell_price: '',
    tax_percentage: 0  // ← NEW FIELD
  });
  
  return (
    <form>
      {/* Other fields */}
      
      <div className="form-group">
        <label>Sell Price (Inclusive)</label>
        <input
          type="number"
          name="sell_price"
          value={formData.sell_price}
          placeholder="What customer pays (includes tax)"
        />
      </div>
      
      <div className="form-group">
        <label>Tax Percentage</label>
        <input
          type="number"
          name="tax_percentage"
          value={formData.tax_percentage}
          placeholder="e.g., 2 for 2%"
          step="0.01"
          min="0"
          max="100"
        />
        <small>System will calculate base price automatically</small>
      </div>
    </form>
  );
};
```

### Invoice/Receipt Component

```javascript
// ✅ Show inclusive tax properly
const Invoice = ({ order }) => {
  return (
    <div className="invoice">
      <h2>Invoice #{order.order_number}</h2>
      
      {/* Items */}
      {order.items.map(item => (
        <div key={item.id} className="item-row">
          <span>{item.product_name} x {item.quantity}</span>
          <span>{item.total_amount} BDT</span>
        </div>
      ))}
      
      {/* Totals */}
      <div className="totals">
        <div>Subtotal: {order.subtotal} BDT</div>
        <div>Discount: {order.discount_amount} BDT</div>
        <div>Shipping: {order.shipping_amount} BDT</div>
        <div className="total">Total: {order.total_amount} BDT</div>
        
        {/* Tax breakdown - for information only */}
        <div className="tax-info">
          <small>
            (Includes {order.tax_amount} BDT in taxes)
          </small>
        </div>
      </div>
    </div>
  );
};
```

---

## 🔍 Testing Checklist

### Store Assignment Testing

- [ ] List orders with `?pending_assignment=true`
- [ ] Verify orders without stores show `store: null`
- [ ] Assign store to order
- [ ] Verify order now has `store: { id, name }`
- [ ] Verify status changed from `pending_assignment` to `confirmed`
- [ ] Verify no crashes when displaying unassigned orders

### Product Creation Testing

- [ ] Create product without brand field
- [ ] Create product with brand field
- [ ] Update product and remove brand
- [ ] Verify no validation errors for missing brand

### Tax System Testing

- [ ] Create batch with tax_percentage = 2.0 and sell_price = 1000
- [ ] Verify response has `base_price: "980.39"` and `tax_amount: "19.61"`
- [ ] Create order with this batch
- [ ] Verify order item has `tax_amount` calculated
- [ ] Verify order `total_amount` equals `subtotal - discount + shipping` (no tax added)
- [ ] Display receipt showing inclusive prices correctly

---

## 📝 API Endpoints Summary

### Orders

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/orders` | List orders (with new filters) |
| GET | `/api/orders?pending_assignment=true` | Unassigned e-commerce orders |
| GET | `/api/orders?store_id=unassigned` | All unassigned orders |
| POST | `/api/orders/{id}/assign-store` | Assign store to order |
| GET | `/api/orders/{id}` | Get order details |

### Products

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/products` | Create product (brand optional) |
| PUT | `/api/products/{id}` | Update product (brand optional) |

### Batches

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/batches` | Create batch (tax_percentage new) |
| GET | `/api/batches/{id}` | Get batch (includes tax fields) |

---

## ⚠️ Breaking Changes

### 1. Order Store Field

**Before:** Always an object
```json
{ "store": { "id": 1, "name": "Store" } }
```

**After:** Can be null
```json
{ "store": null }
```

**Action Required:** Update all frontend code accessing `order.store` to handle null.

### 2. Product Batch Response

**Before:**
```json
{
  "cost_price": "800.00",
  "sell_price": "1000.00"
}
```

**After:** Added fields
```json
{
  "cost_price": "800.00",
  "sell_price": "1000.00",
  "tax_percentage": "2.00",
  "base_price": "980.39",
  "tax_amount": "19.61"
}
```

**Action Required:** Update displays to show tax information.

### 3. Order Totals Calculation

**Before:**
```
total = subtotal + tax + shipping - discount
```

**After:**
```
total = subtotal + shipping - discount
(tax already in subtotal)
```

**Action Required:** Don't manually add tax to totals.

---

## 🎯 Migration Strategy

### Phase 1: Immediate (Critical Fixes)
1. ✅ Update all `order.store` access to handle null
2. ✅ Remove `required` validation for product brand field
3. ✅ Test unassigned orders don't crash the app

### Phase 2: Store Assignment (High Priority)
1. ✅ Implement "Pending Assignment" orders list
2. ✅ Add "Assign Store" functionality
3. ✅ Update order status badges

### Phase 3: Tax System (Medium Priority)
1. ✅ Add tax_percentage field to batch creation forms
2. ✅ Update invoice displays to show inclusive prices
3. ✅ Add tax breakdown section to receipts

### Phase 4: Testing
1. ✅ Test all order workflows
2. ✅ Test product creation without brand
3. ✅ Test batch creation with different tax rates
4. ✅ Verify accounting reports

---

## 📞 Support

If you encounter any issues during integration:

1. Check this document first
2. Verify API responses match examples
3. Test with Postman/Thunder Client
4. Contact backend team with:
   - Endpoint URL
   - Request payload
   - Response received
   - Expected behavior

---

## 📚 Additional Resources

- **Tax System Documentation:** `INCLUSIVE_TAX_SYSTEM.md`
- **Test Guide:** `TEST_GUIDE.md`
- **HTTP Test File:** `test_inclusive_tax.http`
- **Pre-order System:** `PREORDER_SYSTEM.md`

---

**Last Updated:** December 8, 2025  
**Version:** 2.0  
**Backend Version:** Laravel 10.x
