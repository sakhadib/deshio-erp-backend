# Product Lookup API Enhancement - PO & Vendor Information

## Overview

The **Product Lookup by Barcode API** has been enhanced to include detailed **Purchase Order (PO)** and **Vendor** information. This helps admin users trace the complete procurement history of any product.

---

## Endpoint

**Endpoint:** `GET /api/lookup/product?barcode={barcode}`

**Authentication:** Required (Bearer Token)

---

## New Response Fields

### Added Fields

| Field | Type | Description |
|-------|------|-------------|
| `purchase_order` | object\|null | Full purchase order details |
| `vendor` | object\|null | Complete vendor information |
| `summary.has_purchase_order` | boolean | Quick check if PO data exists |
| `summary.has_vendor_info` | boolean | Quick check if vendor data exists |

---

## Response Structure

```json
{
  "success": true,
  "data": {
    "product": {
      "id": 123,
      "sku": "PRD-001",
      "name": "Jamdani Saree",
      "description": "...",
      "brand": "Deshio",
      "category": { "id": 5, "name": "Saree" },
      "vendor": { "id": 10, "name": "ABC Textiles", "company_name": "ABC Ltd" }
    },
    "barcode": {
      "barcode": "BC12345678901",
      "type": "CODE128",
      "is_primary": true,
      "is_active": true,
      "is_defective": false,
      "generated_at": "2026-01-15 10:30:00",
      "current_status": "in_shop",
      "location_updated_at": "2026-01-20 14:00:00",
      "location_metadata": {}
    },
    "current_location": {
      "store_id": 5,
      "store_name": "Dhanmondi Outlet",
      "store_code": "DM-001",
      "store_type": "shop",
      "address": "...",
      "phone": "..."
    },
    "batch": {
      "id": 45,
      "batch_number": "BT-2026-001234",
      "cost_price": "2500.00",
      "sell_price": "4500.00",
      "manufactured_date": "2026-01-10",
      "expiry_date": null,
      "original_store": { "id": 1, "name": "Main Warehouse", "store_code": "WH-001" }
    },
    
    "purchase_order_origin": {
      "po_number": "PO-2026-000123",
      "received_date": "2026-01-15 10:30:00",
      "source": "purchase_order"
    },
    
    "purchase_order": {
      "id": 789,
      "po_number": "PO-2026-000123",
      "order_date": "2026-01-10",
      "expected_delivery_date": "2026-01-15",
      "status": "received",
      "payment_status": "paid",
      "total_amount": "125000.00",
      "paid_amount": "125000.00",
      "outstanding_amount": "0.00",
      "store": {
        "id": 1,
        "name": "Main Warehouse",
        "store_code": "WH-001"
      },
      "created_by": {
        "id": 5,
        "name": "Admin User"
      },
      "item_details": {
        "quantity_ordered": 50,
        "quantity_received": 50,
        "unit_cost": "2500.00",
        "unit_sell_price": "4500.00",
        "total_cost": "125000.00",
        "receive_status": "fully_received"
      }
    },
    
    "vendor": {
      "id": 10,
      "name": "ABC Textiles",
      "company_name": "ABC Textiles Limited",
      "email": "contact@abctextiles.com",
      "phone": "+8801712345678",
      "address": "123 Textile Road",
      "city": "Dhaka",
      "state": "Dhaka",
      "postal_code": "1205",
      "country": "Bangladesh",
      "tax_id": "TIN123456789",
      "payment_terms": "Net 30",
      "status": "active",
      "notes": "Premium textile supplier",
      "total_purchase_orders": 25,
      "total_purchase_amount": "5000000.00"
    },
    
    "lifecycle": [...],
    "activity_history": [...],
    
    "summary": {
      "total_dispatches": 2,
      "total_sales": 1,
      "total_returns": 0,
      "is_currently_defective": false,
      "is_active": true,
      "current_status": "sold",
      "has_purchase_order": true,
      "has_vendor_info": true
    }
  }
}
```

---

## Field Descriptions

### `purchase_order` Object

| Field | Type | Description |
|-------|------|-------------|
| `id` | int | Purchase order ID |
| `po_number` | string | PO reference number (e.g., "PO-2026-000123") |
| `order_date` | date | Date the PO was created |
| `expected_delivery_date` | date\|null | Expected delivery date |
| `status` | string | PO status: `draft`, `approved`, `partially_received`, `received`, `cancelled` |
| `payment_status` | string | Payment status: `unpaid`, `partial`, `paid` |
| `total_amount` | decimal | Total PO amount |
| `paid_amount` | decimal | Amount paid so far |
| `outstanding_amount` | decimal | Remaining balance |
| `store` | object | Receiving warehouse/store |
| `created_by` | object | Employee who created the PO |
| `item_details` | object | Specific line item details for this product |

### `purchase_order.item_details` Object

| Field | Type | Description |
|-------|------|-------------|
| `quantity_ordered` | int | How many were ordered in this PO |
| `quantity_received` | int | How many were received |
| `unit_cost` | decimal | Cost per unit from vendor |
| `unit_sell_price` | decimal | Intended sell price |
| `total_cost` | decimal | Total line item cost |
| `receive_status` | string | `pending`, `partially_received`, `fully_received`, `cancelled` |

### `vendor` Object

| Field | Type | Description |
|-------|------|-------------|
| `id` | int | Vendor ID |
| `name` | string | Vendor contact name |
| `company_name` | string | Company/business name |
| `email` | string\|null | Email address |
| `phone` | string\|null | Phone number |
| `address` | string\|null | Street address |
| `city` | string\|null | City |
| `state` | string\|null | State/Province |
| `postal_code` | string\|null | ZIP/Postal code |
| `country` | string\|null | Country |
| `tax_id` | string\|null | Tax identification number |
| `payment_terms` | string\|null | Payment terms (e.g., "Net 30") |
| `status` | string | `active`, `inactive` |
| `notes` | string\|null | Additional notes |
| `total_purchase_orders` | int | Total POs from this vendor |
| `total_purchase_amount` | decimal | Total purchase value from this vendor |

---

## Frontend Usage Examples

### React Component

```jsx
function ProductLookupDetails({ barcode }) {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchProductLookup(barcode);
  }, [barcode]);

  const fetchProductLookup = async (barcode) => {
    try {
      const response = await fetch(`/api/lookup/product?barcode=${barcode}`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json'
        }
      });
      const result = await response.json();
      setData(result.data);
    } catch (error) {
      console.error('Lookup failed:', error);
    } finally {
      setLoading(false);
    }
  };

  if (loading) return <Spinner />;
  if (!data) return <NotFound />;

  return (
    <div className="product-lookup">
      {/* Product Info */}
      <ProductCard product={data.product} />
      
      {/* Barcode & Location */}
      <BarcodeInfo barcode={data.barcode} location={data.current_location} />
      
      {/* PO Information - NEW */}
      {data.purchase_order && (
        <POSection po={data.purchase_order} />
      )}
      
      {/* Vendor Information - NEW */}
      {data.vendor && (
        <VendorSection vendor={data.vendor} />
      )}
      
      {/* Lifecycle */}
      <LifecycleTimeline lifecycle={data.lifecycle} />
    </div>
  );
}

// PO Section Component
function POSection({ po }) {
  return (
    <div className="po-section card">
      <h3>Purchase Order Information</h3>
      
      <div className="po-header">
        <span className="po-number">{po.po_number}</span>
        <StatusBadge status={po.status} />
        <PaymentBadge status={po.payment_status} />
      </div>
      
      <div className="po-details">
        <DetailRow label="Order Date" value={po.order_date} />
        <DetailRow label="Received At" value={po.store?.name} />
        <DetailRow label="Created By" value={po.created_by?.name} />
      </div>
      
      <div className="po-financials">
        <div className="financial-item">
          <span>Total Amount</span>
          <span className="amount">৳{formatNumber(po.total_amount)}</span>
        </div>
        <div className="financial-item">
          <span>Paid</span>
          <span className="amount paid">৳{formatNumber(po.paid_amount)}</span>
        </div>
        <div className="financial-item">
          <span>Outstanding</span>
          <span className="amount due">৳{formatNumber(po.outstanding_amount)}</span>
        </div>
      </div>
      
      <div className="item-details">
        <h4>This Product in PO</h4>
        <table>
          <tr>
            <td>Ordered</td>
            <td>{po.item_details.quantity_ordered} pcs</td>
          </tr>
          <tr>
            <td>Received</td>
            <td>{po.item_details.quantity_received} pcs</td>
          </tr>
          <tr>
            <td>Unit Cost</td>
            <td>৳{po.item_details.unit_cost}</td>
          </tr>
          <tr>
            <td>Total Cost</td>
            <td>৳{po.item_details.total_cost}</td>
          </tr>
        </table>
      </div>
    </div>
  );
}

// Vendor Section Component
function VendorSection({ vendor }) {
  return (
    <div className="vendor-section card">
      <h3>Vendor Information</h3>
      
      <div className="vendor-header">
        <h4>{vendor.company_name || vendor.name}</h4>
        <StatusBadge status={vendor.status} />
      </div>
      
      <div className="vendor-contact">
        {vendor.phone && <ContactItem icon="phone" value={vendor.phone} />}
        {vendor.email && <ContactItem icon="email" value={vendor.email} />}
        {vendor.address && (
          <ContactItem 
            icon="location" 
            value={`${vendor.address}, ${vendor.city}, ${vendor.country}`} 
          />
        )}
      </div>
      
      <div className="vendor-stats">
        <StatCard label="Total POs" value={vendor.total_purchase_orders} />
        <StatCard label="Total Value" value={`৳${formatNumber(vendor.total_purchase_amount)}`} />
        <StatCard label="Payment Terms" value={vendor.payment_terms || 'N/A'} />
      </div>
    </div>
  );
}
```

---

## Handling Missing Data

PO and Vendor data may be `null` in these cases:
- Product was added manually (not through PO)
- Old product before PO system was implemented
- Batch not linked to PO item

**Always check before rendering:**

```jsx
// Quick check using summary flags
if (data.summary.has_purchase_order) {
  // Show PO section
}

if (data.summary.has_vendor_info) {
  // Show vendor section
}

// Or check objects directly
{data.purchase_order && <POSection po={data.purchase_order} />}
{data.vendor && <VendorSection vendor={data.vendor} />}
```

---

## Use Cases

### 1. Admin Product Detail Page
Show complete procurement history including PO and vendor details.

### 2. Barcode Scanner App
When scanning a barcode, display where it came from (vendor) and procurement details (PO).

### 3. Inventory Audit
Verify product origin and cost information during stock audits.

### 4. Customer Service
Quickly look up product origin when handling customer queries about product authenticity.

---

## Related APIs

| API | Description |
|-----|-------------|
| `GET /api/purchase-orders/{id}` | Get full PO details |
| `GET /api/vendors/{id}` | Get full vendor details |
| `GET /api/purchase-orders/{id}/pdf` | Download PO as PDF |

---

## Notes

1. PO data is looked up through batch connection first, then falls back to product_id match
2. If multiple POs exist for the same product, the most recent received PO is shown
3. Vendor statistics (`total_purchase_orders`, `total_purchase_amount`) are calculated in real-time
