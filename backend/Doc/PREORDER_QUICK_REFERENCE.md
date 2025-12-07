# 🚀 Pre-Order Quick Reference Card

---

## 📱 FRONTEND TEAM

### Catalog API Changes

```javascript
// Show ALL products (including out-of-stock)
GET /api/catalog/products?in_stock=

// Show ONLY out-of-stock products
GET /api/catalog/products?in_stock=false

// Show ONLY in-stock products
GET /api/catalog/products?in_stock=true
```

### Product Display

```json
{
  "in_stock": false,
  "price_display": "TBA",           // Show this instead of price
  "available_for_preorder": true    // Show "Pre-Order" button
}
```

### UI Example

```jsx
{product.available_for_preorder && (
  <span className="badge preorder">Pre-Order Available</span>
)}
<button>{product.in_stock ? 'Add to Cart' : 'Pre-Order Now'}</button>
```

### ⚠️ No Checkout Changes Needed
Just call `/api/guest-checkout` or `/api/customer/orders/create-from-cart` normally!

---

## 🏢 ERP TEAM

### Dashboard Routes

```bash
# View all pre-orders
GET /api/pre-orders

# Pre-orders ready to fulfill (stock available)
GET /api/pre-orders/ready-to-fulfill

# Dashboard statistics
GET /api/pre-orders/statistics

# Mark order ready for assignment
POST /api/pre-orders/{id}/mark-stock-available
```

### Statistics Response

```json
{
  "total_preorders": 45,
  "awaiting_stock": 30,
  "ready_to_fulfill": 15,
  "pending_assignment": 12,
  "total_value": 156780.00
}
```

### Daily Workflow

1. Check `/api/pre-orders/ready-to-fulfill` daily
2. Click "Mark Stock Available" for each order
3. Order moves to "Pending Assignment"
4. Assign to store as normal
5. Store fulfills order

---

## 🔧 BACKEND TEAM

### Order Model

```php
// New fields
'is_preorder' => boolean
'stock_available_at' => datetime
'preorder_notes' => text
```

### Auto-Detection Logic

```php
// System automatically checks:
$hasOutOfStockItems = ProductBatch::where('product_id', $item['product_id'])
    ->where('quantity', '>', 0)
    ->sum('quantity') < $item['quantity'];

// Sets flag:
Order::create(['is_preorder' => $hasOutOfStockItems]);
```

### Migration Command

```bash
php artisan migrate
```

---

## 📊 KEY METRICS

- **Total Pre-Orders**: Count of orders waiting for stock
- **Awaiting Stock**: Pre-orders with insufficient inventory
- **Ready to Fulfill**: Pre-orders with full stock available
- **Pending Assignment**: Orders waiting for store assignment

---

## ⚡ QUICK ANSWERS

**Q: Do we need to manually mark orders as pre-order?**  
A: No! System auto-detects based on stock availability.

**Q: Can customers place pre-orders?**  
A: Yes! Both guest and registered customers can pre-order automatically.

**Q: How do we know when to fulfill pre-orders?**  
A: Check "Ready to Fulfill" endpoint - shows orders with available stock.

**Q: What if price is unknown?**  
A: System shows "TBA" and accepts orders with zero price.

**Q: Does this break existing checkout?**  
A: No! Fully backward compatible. Existing flows work normally.

---

## 📞 SUPPORT

- **API Docs**: `Doc/PREORDER_SYSTEM.md`
- **Implementation Summary**: `Doc/PREORDER_IMPLEMENTATION_SUMMARY.md`
- **Questions**: Contact backend team

---

✅ **PRODUCTION READY** - Deploy anytime!
