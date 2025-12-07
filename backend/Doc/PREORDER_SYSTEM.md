# Pre-Order System - API Documentation

**Last Updated:** December 7, 2025  
**Feature:** Pre-Order Support for Out-of-Stock Products

---

## 🎯 Overview

The pre-order system allows customers to place orders for products that are currently out of stock. The system automatically detects when ordered items have no available inventory and marks them as pre-orders.

### Key Features

- ✅ **Automatic Detection**: Orders with out-of-stock items are automatically flagged as pre-orders
- ✅ **Price Display**: Out-of-stock products show "TBA" instead of price
- ✅ **Stock Monitoring**: ERP tracks when pre-ordered items become available
- ✅ **Fulfillment Ready**: Dashboard shows pre-orders ready to process
- ✅ **Guest Support**: Both guest and registered customers can place pre-orders

---

## 📱 Frontend Changes

### Catalog API Changes

**Endpoint:** `GET /api/catalog/products`

**New Query Parameters:**

```javascript
{
  in_stock: null,        // null = all products, true = in stock only, false = out of stock only
  preorder_only: false   // true = only pre-order items
}
```

**Updated Response:**

```json
{
  "id": 123,
  "name": "Premium T-Shirt",
  "selling_price": null,              // null if no stock
  "price_display": "TBA",             // "TBA" or "1500.00 BDT"
  "stock_quantity": 0,
  "in_stock": false,
  "available_for_preorder": true,     // NEW: Indicates item can be pre-ordered
  "images": [...]
}
```

### UI Examples

#### Product Card (Out of Stock)

```jsx
function ProductCard({ product }) {
  return (
    <div className="product-card">
      <img src={product.images[0].url} alt={product.name} />
      <h3>{product.name}</h3>
      
      {product.in_stock ? (
        <p className="price">{product.price_display}</p>
      ) : (
        <div>
          <p className="price tba">Price: TBA</p>
          <span className="badge preorder">Pre-Order Available</span>
        </div>
      )}
      
      <button disabled={!product.in_stock && !product.available_for_preorder}>
        {product.in_stock ? 'Add to Cart' : 'Pre-Order Now'}
      </button>
    </div>
  );
}
```

#### Filter Options

```jsx
<select onChange={(e) => setInStock(e.target.value)}>
  <option value="">All Products</option>
  <option value="true">In Stock Only</option>
  <option value="false">Out of Stock / Pre-Order</option>
</select>
```

---

## 🛒 Guest Checkout (Supports Pre-Orders)

**Endpoint:** `POST /api/guest-checkout`

**No Changes Required** - The existing guest checkout API automatically handles pre-orders!

### How It Works

1. Customer adds out-of-stock items to cart
2. Submits order via `/api/guest-checkout`
3. System automatically:
   - Detects out-of-stock items
   - Marks order as `is_preorder: true`
   - Adds note: "This order contains out-of-stock items..."
   - Sets status to `pending_assignment`

### Response Example

```json
{
  "success": true,
  "data": {
    "order": {
      "order_number": "ORD-2025-001234",
      "is_preorder": true,
      "preorder_notes": "This order contains out-of-stock items and will be fulfilled when stock becomes available.",
      "status": "pending_assignment",
      "total_amount": 3210.00
    },
    "message_to_customer": "Thank you for your pre-order! We'll contact you when stock arrives."
  }
}
```

---

## 🏢 ERP Pre-Order Management

### 1. List All Pre-Orders

**Endpoint:** `GET /api/pre-orders`

**Query Parameters:**

```javascript
{
  has_stock: false,     // Filter by stock availability
  status: 'pending_assignment',
  date_from: '2025-01-01',
  date_to: '2025-12-31',
  search: 'ORD-2025',   // Search order number or customer
  per_page: 20
}
```

**Response:**

```json
{
  "success": true,
  "data": {
    "orders": [
      {
        "order_id": 789,
        "order_number": "ORD-2025-001234",
        "customer": {
          "id": 456,
          "name": "John Doe",
          "phone": "01712345678"
        },
        "is_preorder": true,
        "stock_available_at": null,
        "all_items_in_stock": false,
        "items": [
          {
            "product_name": "Premium T-Shirt",
            "quantity_ordered": 2,
            "available_stock": 0,
            "has_sufficient_stock": false,
            "stock_shortage": 2
          }
        ],
        "total_amount": 3210.00,
        "created_at": "2025-12-06T10:30:00Z"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "total": 15,
      "last_page": 1
    }
  }
}
```

---

### 2. Get Pre-Orders Ready to Fulfill

**Endpoint:** `GET /api/pre-orders/ready-to-fulfill`

Returns pre-orders where **ALL items now have sufficient stock**.

**Response:**

```json
{
  "success": true,
  "data": {
    "total_ready": 5,
    "orders": [
      {
        "order_id": 789,
        "order_number": "ORD-2025-001234",
        "all_items_in_stock": true,
        "items": [
          {
            "product_name": "Premium T-Shirt",
            "quantity_ordered": 2,
            "available_stock": 10,
            "has_sufficient_stock": true,
            "stock_shortage": 0
          }
        ]
      }
    ]
  },
  "message": "Found 5 pre-orders ready to fulfill"
}
```

---

### 3. Mark Pre-Order as Stock Available

**Endpoint:** `POST /api/pre-orders/{id}/mark-stock-available`

Verifies stock and marks order ready for store assignment.

**Success Response:**

```json
{
  "success": true,
  "message": "Pre-order marked as stock available. Ready for store assignment.",
  "data": {
    "order_id": 789,
    "stock_available_at": "2025-12-07T14:30:00Z",
    "all_items_in_stock": true
  }
}
```

**Error Response (Insufficient Stock):**

```json
{
  "success": false,
  "message": "Cannot mark as stock available. Some items are still out of stock.",
  "missing_stock": [
    {
      "product": "Premium T-Shirt",
      "required": 2,
      "available": 1,
      "shortage": 1
    }
  ]
}
```

---

### 4. Pre-Order Statistics

**Endpoint:** `GET /api/pre-orders/statistics`

**Response:**

```json
{
  "success": true,
  "data": {
    "total_preorders": 45,
    "awaiting_stock": 30,
    "ready_to_fulfill": 15,
    "pending_assignment": 12,
    "total_value": 156780.00
  }
}
```

---

### 5. Trending Pre-Order Products

**Endpoint:** `GET /api/pre-orders/trending-products`

Shows which products are most frequently pre-ordered (helps with inventory planning).

**Response:**

```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "name": "Premium T-Shirt",
      "sku": "TS-001",
      "total_preordered": 45,
      "order_count": 15
    }
  ],
  "message": "Top 10 products by pre-order volume"
}
```

---

## 🎨 ERP Dashboard UI

### Pre-Order Widget

```jsx
function PreOrderDashboard() {
  const [stats, setStats] = useState(null);
  const [readyOrders, setReadyOrders] = useState([]);

  useEffect(() => {
    // Fetch statistics
    fetch('/api/pre-orders/statistics')
      .then(res => res.json())
      .then(data => setStats(data.data));

    // Fetch ready-to-fulfill orders
    fetch('/api/pre-orders/ready-to-fulfill')
      .then(res => res.json())
      .then(data => setReadyOrders(data.data.orders));
  }, []);

  return (
    <div className="preorder-dashboard">
      <h2>Pre-Order Management</h2>
      
      <div className="stats-grid">
        <div className="stat-card">
          <h3>{stats?.total_preorders}</h3>
          <p>Total Pre-Orders</p>
        </div>
        <div className="stat-card warning">
          <h3>{stats?.awaiting_stock}</h3>
          <p>Awaiting Stock</p>
        </div>
        <div className="stat-card success">
          <h3>{stats?.ready_to_fulfill}</h3>
          <p>Ready to Fulfill</p>
        </div>
        <div className="stat-card">
          <h3>{stats?.total_value?.toLocaleString()} BDT</h3>
          <p>Total Value</p>
        </div>
      </div>

      <div className="ready-orders">
        <h3>Ready to Fulfill ({readyOrders.length})</h3>
        {readyOrders.map(order => (
          <div key={order.order_id} className="order-card">
            <div>
              <strong>{order.order_number}</strong>
              <p>{order.customer.name} - {order.customer.phone}</p>
            </div>
            <button onClick={() => markStockAvailable(order.order_id)}>
              Mark Stock Available
            </button>
          </div>
        ))}
      </div>
    </div>
  );
}

function markStockAvailable(orderId) {
  fetch(`/api/pre-orders/${orderId}/mark-stock-available`, {
    method: 'POST',
    headers: { 'Authorization': `Bearer ${token}` }
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      alert('Pre-order ready for store assignment!');
      // Redirect to order management
      window.location.href = '/order-management/pending-assignment';
    } else {
      alert(data.message);
    }
  });
}
```

---

## 🔄 Workflow

### For Customers (Frontend)

1. Browse products
2. See "TBA" price for out-of-stock items
3. Add to cart/pre-order
4. Complete checkout (COD or online payment)
5. Receive confirmation: "We'll contact you when stock arrives"

### For ERP (Backend Team)

1. View pre-orders dashboard
2. Filter: "Awaiting Stock" vs "Ready to Fulfill"
3. When stock arrives, system auto-detects
4. Click "Mark Stock Available" to verify
5. Order moves to "Pending Assignment"
6. Assign to store for fulfillment
7. Store fulfills order normally

---

## 📊 Database Changes

### `orders` Table (Updated)

```sql
ALTER TABLE orders
ADD COLUMN is_preorder BOOLEAN DEFAULT FALSE,
ADD COLUMN stock_available_at TIMESTAMP NULL,
ADD COLUMN preorder_notes TEXT NULL,
ADD INDEX idx_preorder_status (is_preorder, status);
```

### Order Model Changes

```php
// Added to fillable
'is_preorder',
'stock_available_at',
'preorder_notes',

// Added to casts
'is_preorder' => 'boolean',
'stock_available_at' => 'datetime',
```

---

## ✅ Testing Checklist

### Frontend

- [ ] Products with no stock show "TBA" price
- [ ] Products with no stock show "Pre-Order Available" badge
- [ ] Filter: "Out of Stock / Pre-Order" works
- [ ] Guest checkout accepts out-of-stock items
- [ ] Checkout shows "Pre-order" confirmation message

### ERP

- [ ] Pre-orders list loads correctly
- [ ] Filter by "has_stock" works
- [ ] Statistics dashboard shows accurate counts
- [ ] "Ready to Fulfill" shows only orders with stock
- [ ] "Mark Stock Available" verifies stock correctly
- [ ] Trending products shows pre-order demand

---

## 🚀 Example API Calls

### Get All Products (Including Out of Stock)

```bash
curl -X GET "https://api.yoursite.com/api/catalog/products?in_stock="
```

### Get Only Out-of-Stock Products

```bash
curl -X GET "https://api.yoursite.com/api/catalog/products?in_stock=false"
```

### Place Pre-Order (Guest Checkout)

```bash
curl -X POST "https://api.yoursite.com/api/guest-checkout" \
  -H "Content-Type: application/json" \
  -d '{
    "phone": "01712345678",
    "items": [
      {"product_id": 123, "quantity": 2}
    ],
    "payment_method": "cod",
    "delivery_address": {
      "full_name": "John Doe",
      "address_line_1": "123 Main St",
      "city": "Dhaka",
      "postal_code": "1212"
    }
  }'
```

### Get Pre-Orders Ready to Fulfill (ERP)

```bash
curl -X GET "https://api.yoursite.com/api/pre-orders/ready-to-fulfill" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Mark Pre-Order as Stock Available (ERP)

```bash
curl -X POST "https://api.yoursite.com/api/pre-orders/789/mark-stock-available" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## 💡 Pro Tips

### For Frontend Team

1. **Cache catalog data** but refresh every 5 minutes to catch stock updates
2. **Show pre-order badge** prominently - it drives sales!
3. **Notify customers** when pre-ordered items are back in stock (future feature)
4. **Allow wishlist** for out-of-stock items

### For ERP Team

1. **Check "Ready to Fulfill" daily** to process pre-orders quickly
2. **Use "Trending Products"** to prioritize restocking
3. **Filter by date** to find old pre-orders
4. **Contact customers** when marking stock available

---

## 📞 Support

**Questions?** Contact backend team or check the main API documentation.

**Frontend Team:** You can now show ALL products (in-stock + out-of-stock) and let customers pre-order! 🎉

**ERP Team:** Pre-orders are automatically tracked. Just check the dashboard daily! 📊

---

**Happy Coding!** 🚀
