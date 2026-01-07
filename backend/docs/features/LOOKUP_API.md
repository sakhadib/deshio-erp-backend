# Lookup APIs Documentation

## Overview

The Lookup APIs provide comprehensive lifecycle tracking and history for products, orders, and batches. These endpoints enable complete traceability from purchase order receipt through sales, returns, and re-sales with full audit trails.

**Base URL:** `/api/lookup`

**Authentication:** All endpoints require `auth:api` middleware (Bearer token)

---

## Table of Contents

1. [Product Lookup (by Barcode)](#1-product-lookup-by-barcode)
2. [Order Lookup](#2-order-lookup)
3. [Batch Lookup](#3-batch-lookup)
4. [Common Use Cases](#common-use-cases)
5. [Frontend Integration Examples](#frontend-integration-examples)

---

## 1. Product Lookup (by Barcode)

**Endpoint:** `GET /api/lookup/product?barcode={barcode}`

**Description:** Track the complete lifecycle of a specific physical product unit from purchase order receipt through all sales, returns, transfers, and defect marking.

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `barcode` | string | Yes | The barcode number to look up |

### What This Returns

- **Product Information**: SKU, name, brand, category, vendor
- **Barcode Details**: Type, status, generation date, location metadata
- **Current Location**: Which store currently holds this unit
- **Batch Information**: Original batch details, prices, dates
- **Purchase Order Origin**: Which PO this came from, when received
- **Complete Lifecycle**:
  - Initial receipt at warehouse/store
  - All dispatches & store-to-store transfers
  - Sale records (which customer bought it, when, which store)
  - Return records (if returned, when, where, why)
  - Re-sale records (if sold again after return)
  - Defective marking (if marked as defective)
- **Activity History**: Every change with timestamps and who made it
- **Summary Statistics**: Total dispatches, sales, returns, current status

### Example Request

```bash
GET /api/lookup/product?barcode=BC12345678901
Authorization: Bearer {token}
```

### Example Response

```json
{
  "success": true,
  "data": {
    "product": {
      "id": 101,
      "sku": "PROD-001",
      "name": "Premium Blue Shirt",
      "description": "High quality cotton shirt",
      "brand": "FashionCo",
      "category": {
        "id": 5,
        "name": "Clothing"
      },
      "vendor": {
        "id": 12,
        "name": "ABC Suppliers",
        "company_name": "ABC Trading Ltd"
      }
    },
    "barcode": {
      "barcode": "BC12345678901",
      "type": "CODE128",
      "is_primary": true,
      "is_active": false,
      "is_defective": false,
      "generated_at": "2025-01-15 10:30:00",
      "current_status": "with_customer",
      "location_updated_at": "2025-12-20 14:00:00",
      "location_metadata": {
        "source": "purchase_order",
        "po_number": "PO-2025-00123",
        "received_date": "2025-01-15 10:30:00"
      }
    },
    "current_location": {
      "store_id": 3,
      "store_name": "Main Store - Dhaka",
      "store_code": "DHK-001",
      "store_type": "retail",
      "address": "123 Main Street, Dhaka",
      "phone": "+880123456789"
    },
    "batch": {
      "id": 456,
      "batch_number": "PO-2025-00123-1",
      "cost_price": "1200.00",
      "sell_price": "1800.00",
      "manufactured_date": "2024-12-01",
      "expiry_date": null,
      "original_store": {
        "id": 1,
        "name": "Central Warehouse",
        "store_code": "WH-001"
      }
    },
    "purchase_order_origin": {
      "po_number": "PO-2025-00123",
      "received_date": "2025-01-15 10:30:00",
      "source": "purchase_order"
    },
    "lifecycle": [
      {
        "stage": "origin",
        "title": "Purchase Order Receipt",
        "timestamp": "2025-01-15 10:30:00",
        "data": {
          "po_number": "PO-2025-00123",
          "received_date": "2025-01-15 10:30:00"
        }
      },
      {
        "stage": "dispatches",
        "title": "Store Transfers",
        "count": 2,
        "data": [
          {
            "dispatch_id": 789,
            "dispatch_number": "DISP-2025-00045",
            "dispatch_date": "2025-02-01 09:00:00",
            "status": "received",
            "from_store": {
              "id": 1,
              "name": "Central Warehouse",
              "store_code": "WH-001"
            },
            "to_store": {
              "id": 3,
              "name": "Main Store - Dhaka",
              "store_code": "DHK-001"
            },
            "dispatched_by": {
              "id": 5,
              "name": "John Warehouse Manager"
            }
          }
        ]
      },
      {
        "stage": "sales",
        "title": "Sales History",
        "count": 1,
        "data": [
          {
            "order_id": 2345,
            "order_number": "ORD-2025-05678",
            "order_date": "2025-12-20 14:00:00",
            "order_status": "completed",
            "sale_price": "1800.00",
            "store": {
              "id": 3,
              "name": "Main Store - Dhaka",
              "store_code": "DHK-001"
            },
            "customer": {
              "id": 567,
              "name": "Ahmed Khan",
              "phone": "01712345678",
              "customer_code": "CUST-2025-ABC123",
              "customer_type": "ecommerce"
            }
          }
        ]
      },
      {
        "stage": "returns",
        "title": "Return History",
        "count": 0,
        "data": []
      },
      {
        "stage": "defective",
        "title": "Defective Status",
        "is_defective": false,
        "data": null
      }
    ],
    "activity_history": [
      {
        "id": 1001,
        "event": "created",
        "description": "ProductBarcode created",
        "timestamp": "2025-01-15 10:30:00",
        "human_time": "11 months ago",
        "performed_by": {
          "id": 10,
          "type": "Employee",
          "name": "System Auto-Generate"
        },
        "changes": {}
      },
      {
        "id": 1002,
        "event": "updated",
        "description": "ProductBarcode updated",
        "timestamp": "2025-02-01 09:00:00",
        "human_time": "10 months ago",
        "performed_by": {
          "id": 5,
          "type": "Employee",
          "name": "John Warehouse Manager"
        },
        "changes": {
          "current_store_id": {
            "from": 1,
            "to": 3
          },
          "current_status": {
            "from": "in_warehouse",
            "to": "in_shop"
          }
        }
      },
      {
        "id": 1003,
        "event": "updated",
        "description": "Sold to customer",
        "timestamp": "2025-12-20 14:00:00",
        "human_time": "3 days ago",
        "performed_by": {
          "id": 8,
          "type": "Employee",
          "name": "Sarah Sales"
        },
        "changes": {
          "is_active": {
            "from": true,
            "to": false
          },
          "current_status": {
            "from": "in_shop",
            "to": "with_customer"
          }
        }
      }
    ],
    "summary": {
      "total_dispatches": 2,
      "total_sales": 1,
      "total_returns": 0,
      "is_currently_defective": false,
      "is_active": false,
      "current_status": "with_customer"
    }
  }
}
```

---

## 2. Order Lookup

**Endpoint:** `GET /api/lookup/order/{orderId}`

**Description:** Get complete order details including all products sold with their specific barcode numbers (if fulfilled), customer information, payment records, shipment tracking, and full timestamped edit history.

### URL Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `orderId` | integer | Yes | The order ID to look up |

### What This Returns

- **Order Information**: Order number, type, status, dates, amounts
- **Customer Information**: Full customer details, purchase history
- **Store Information**: Which store processed this order
- **Order Items with Barcodes**: 
  - Product details
  - Batch information
  - **Specific barcode numbers sold** (if order is fulfilled)
  - Prices, quantities, totals
- **Payment Records**: All payments with methods, dates, transaction IDs
- **Shipment Records**: Tracking numbers, carrier, delivery status
- **Created By & Fulfilled By**: Employee information
- **Activity History**: Complete audit trail of all changes
- **Summary Statistics**: Item counts, fulfillment status

### Example Request

```bash
GET /api/lookup/order/2345
Authorization: Bearer {token}
```

### Example Response

```json
{
  "success": true,
  "data": {
    "order": {
      "id": 2345,
      "order_number": "ORD-2025-05678",
      "order_type": "ecommerce",
      "status": "completed",
      "fulfillment_status": "fulfilled",
      "payment_status": "paid",
      "order_date": "2025-12-20 13:00:00",
      "confirmed_at": "2025-12-20 13:05:00",
      "fulfilled_at": "2025-12-20 14:00:00",
      "shipped_at": "2025-12-20 15:00:00",
      "delivered_at": "2025-12-21 10:00:00",
      "cancelled_at": null,
      "subtotal": "3500.00",
      "tax_amount": "525.00",
      "discount_amount": "200.00",
      "shipping_amount": "100.00",
      "total_amount": "3925.00",
      "paid_amount": "3925.00",
      "outstanding_amount": "0.00"
    },
    "customer": {
      "id": 567,
      "customer_code": "CUST-2025-ABC123",
      "customer_type": "ecommerce",
      "name": "Ahmed Khan",
      "phone": "01712345678",
      "email": "ahmed@example.com",
      "address": "House 45, Road 12, Dhanmondi",
      "city": "Dhaka",
      "total_orders": 15,
      "total_purchases": "45000.00"
    },
    "store": {
      "id": 3,
      "name": "Main Store - Dhaka",
      "store_code": "DHK-001",
      "store_type": "retail",
      "address": "123 Main Street, Dhaka",
      "phone": "+880123456789"
    },
    "items": [
      {
        "item_id": 4567,
        "product": {
          "id": 101,
          "sku": "PROD-001",
          "name": "Premium Blue Shirt",
          "brand": "FashionCo"
        },
        "batch": {
          "id": 456,
          "batch_number": "PO-2025-00123-1",
          "cost_price": "1200.00",
          "sell_price": "1800.00"
        },
        "barcode": {
          "barcode": "BC12345678901",
          "type": "CODE128",
          "is_active": false,
          "current_status": "with_customer"
        },
        "quantity": 1,
        "unit_price": "1800.00",
        "discount_amount": "100.00",
        "tax_amount": "255.00",
        "total_amount": "1955.00",
        "notes": null
      },
      {
        "item_id": 4568,
        "product": {
          "id": 102,
          "sku": "PROD-002",
          "name": "Black Jeans",
          "brand": "DenimCo"
        },
        "batch": {
          "id": 457,
          "batch_number": "PO-2025-00124-1",
          "cost_price": "1400.00",
          "sell_price": "2000.00"
        },
        "barcode": {
          "barcode": "BC12345678902",
          "type": "CODE128",
          "is_active": false,
          "current_status": "with_customer"
        },
        "quantity": 1,
        "unit_price": "2000.00",
        "discount_amount": "100.00",
        "tax_amount": "270.00",
        "total_amount": "2170.00",
        "notes": null
      }
    ],
    "payments": [
      {
        "payment_id": 890,
        "payment_date": "2025-12-20 13:10:00",
        "amount": "3925.00",
        "payment_method": {
          "id": 1,
          "name": "bKash",
          "type": "mobile_banking"
        },
        "transaction_id": "BKH2025122012345",
        "status": "completed",
        "notes": null
      }
    ],
    "shipments": [
      {
        "shipment_id": 567,
        "tracking_number": "SHIP-2025-00789",
        "carrier_name": "Pathao",
        "status": "delivered",
        "shipped_at": "2025-12-20 15:00:00",
        "delivered_at": "2025-12-21 10:00:00",
        "shipping_address": "House 45, Road 12, Dhanmondi, Dhaka"
      }
    ],
    "created_by": {
      "id": 8,
      "name": "Sarah Sales",
      "email": "sarah@company.com"
    },
    "fulfilled_by": {
      "id": 8,
      "name": "Sarah Sales",
      "email": "sarah@company.com"
    },
    "activity_history": [
      {
        "id": 5001,
        "event": "created",
        "subject_type": "Order",
        "description": "Order created",
        "timestamp": "2025-12-20 13:00:00",
        "human_time": "3 days ago",
        "performed_by": {
          "id": 8,
          "type": "Employee",
          "name": "Sarah Sales"
        },
        "changes": {}
      },
      {
        "id": 5002,
        "event": "updated",
        "subject_type": "Order",
        "description": "Order status changed",
        "timestamp": "2025-12-20 13:05:00",
        "human_time": "3 days ago",
        "performed_by": {
          "id": 8,
          "type": "Employee",
          "name": "Sarah Sales"
        },
        "changes": {
          "status": {
            "from": "pending",
            "to": "confirmed"
          }
        }
      },
      {
        "id": 5003,
        "event": "created",
        "subject_type": "OrderPayment",
        "description": "Payment recorded",
        "timestamp": "2025-12-20 13:10:00",
        "human_time": "3 days ago",
        "performed_by": {
          "id": 8,
          "type": "Employee",
          "name": "Sarah Sales"
        },
        "changes": {}
      },
      {
        "id": 5004,
        "event": "updated",
        "subject_type": "Order",
        "description": "Order fulfilled",
        "timestamp": "2025-12-20 14:00:00",
        "human_time": "3 days ago",
        "performed_by": {
          "id": 8,
          "type": "Employee",
          "name": "Sarah Sales"
        },
        "changes": {
          "fulfillment_status": {
            "from": "pending",
            "to": "fulfilled"
          }
        }
      }
    ],
    "summary": {
      "total_items": 2,
      "items_with_barcodes": 2,
      "total_payments": 1,
      "total_shipments": 1,
      "is_fulfilled": true,
      "is_paid": true
    }
  }
}
```

---

## 3. Batch Lookup

**Endpoint:** `GET /api/lookup/batch/{batchId}`

**Description:** Get complete batch information including all product barcodes in the batch, their current locations, all sales, dispatches, movements, and full timestamped edit history.

### URL Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `batchId` | integer | Yes | The batch ID to look up |

### What This Returns

- **Batch Information**: Batch number, quantities, prices, dates
- **Product Information**: Associated product details
- **Store Information**: Original receiving store
- **All Barcodes in Batch**: 
  - Every barcode number
  - Current location of each
  - Active/sold/defective status
  - Defective records if any
- **Sales Records**: All sales from this batch with customers
- **Dispatch Records**: All store transfers involving this batch
- **Movement Records**: All inventory movements
- **Activity History**: Complete audit trail with timestamps
- **Stock Summary**: Current stock, sales count, dispatch count

### Example Request

```bash
GET /api/lookup/batch/456
Authorization: Bearer {token}
```

### Example Response

```json
{
  "success": true,
  "data": {
    "batch": {
      "id": 456,
      "batch_number": "PO-2025-00123-1",
      "quantity": 47,
      "cost_price": "1200.00",
      "sell_price": "1800.00",
      "tax_percentage": "15.00",
      "base_price": "1565.22",
      "tax_amount": "234.78",
      "manufactured_date": "2024-12-01",
      "expiry_date": null,
      "is_active": true,
      "availability": true,
      "notes": "High quality stock",
      "created_at": "2025-01-15 10:30:00"
    },
    "product": {
      "id": 101,
      "sku": "PROD-001",
      "name": "Premium Blue Shirt",
      "description": "High quality cotton shirt",
      "brand": "FashionCo",
      "category": {
        "id": 5,
        "name": "Clothing"
      },
      "vendor": {
        "id": 12,
        "name": "ABC Suppliers",
        "company_name": "ABC Trading Ltd"
      }
    },
    "store": {
      "id": 1,
      "name": "Central Warehouse",
      "store_code": "WH-001",
      "store_type": "warehouse",
      "address": "Warehouse Complex, Industrial Area"
    },
    "barcodes": [
      {
        "id": 1001,
        "barcode": "BC12345678901",
        "type": "CODE128",
        "is_primary": true,
        "is_active": false,
        "is_defective": false,
        "current_status": "with_customer",
        "generated_at": "2025-01-15 10:30:00",
        "current_location": {
          "id": 3,
          "name": "Main Store - Dhaka",
          "store_code": "DHK-001"
        },
        "defective_record": null
      },
      {
        "id": 1002,
        "barcode": "BC12345678902",
        "type": "CODE128",
        "is_primary": false,
        "is_active": true,
        "is_defective": false,
        "current_status": "in_shop",
        "generated_at": "2025-01-15 10:30:00",
        "current_location": {
          "id": 3,
          "name": "Main Store - Dhaka",
          "store_code": "DHK-001"
        },
        "defective_record": null
      },
      {
        "id": 1003,
        "barcode": "BC12345678903",
        "type": "CODE128",
        "is_primary": false,
        "is_active": true,
        "is_defective": true,
        "current_status": "defective",
        "generated_at": "2025-01-15 10:30:00",
        "current_location": {
          "id": 1,
          "name": "Central Warehouse",
          "store_code": "WH-001"
        },
        "defective_record": {
          "defect_reason": "Minor scratch on fabric",
          "condition": "minor_damage",
          "discount_percentage": "20.00"
        }
      }
    ],
    "sales_records": [
      {
        "order_number": "ORD-2025-05678",
        "order_date": "2025-12-20 14:00:00",
        "quantity": 1,
        "unit_price": "1800.00",
        "total_amount": "1955.00",
        "barcode": "BC12345678901",
        "customer": {
          "name": "Ahmed Khan",
          "customer_code": "CUST-2025-ABC123"
        }
      },
      {
        "order_number": "ORD-2025-05679",
        "order_date": "2025-12-21 10:00:00",
        "quantity": 1,
        "unit_price": "1800.00",
        "total_amount": "1955.00",
        "barcode": "BC12345678904",
        "customer": {
          "name": "Fatima Rahman",
          "customer_code": "CUST-2025-DEF456"
        }
      }
    ],
    "dispatch_records": [
      {
        "dispatch_number": "DISP-2025-00045",
        "dispatch_date": "2025-02-01 09:00:00",
        "status": "received",
        "quantity": 30,
        "from_store": {
          "name": "Central Warehouse",
          "store_code": "WH-001"
        },
        "to_store": {
          "name": "Main Store - Dhaka",
          "store_code": "DHK-001"
        }
      },
      {
        "dispatch_number": "DISP-2025-00046",
        "dispatch_date": "2025-02-15 10:00:00",
        "status": "received",
        "quantity": 20,
        "from_store": {
          "name": "Central Warehouse",
          "store_code": "WH-001"
        },
        "to_store": {
          "name": "Branch Store - Chittagong",
          "store_code": "CTG-001"
        }
      }
    ],
    "movement_records": [
      {
        "movement_type": "dispatch",
        "quantity": 30,
        "timestamp": "2025-02-01 09:00:00",
        "from_store": {
          "name": "Central Warehouse",
          "store_code": "WH-001"
        },
        "to_store": {
          "name": "Main Store - Dhaka",
          "store_code": "DHK-001"
        },
        "performed_by": {
          "name": "John Warehouse Manager"
        },
        "notes": "Initial stock distribution"
      }
    ],
    "activity_history": [
      {
        "id": 2001,
        "event": "created",
        "subject_type": "ProductBatch",
        "description": "Batch created",
        "timestamp": "2025-01-15 10:30:00",
        "human_time": "11 months ago",
        "performed_by": {
          "id": 10,
          "type": "Employee",
          "name": "System"
        },
        "changes": {}
      },
      {
        "id": 2002,
        "event": "updated",
        "subject_type": "ProductBatch",
        "description": "Stock reduced (dispatch)",
        "timestamp": "2025-02-01 09:00:00",
        "human_time": "10 months ago",
        "performed_by": {
          "id": 5,
          "type": "Employee",
          "name": "John Warehouse Manager"
        },
        "changes": {
          "quantity": {
            "from": 50,
            "to": 20
          }
        }
      },
      {
        "id": 2003,
        "event": "updated",
        "subject_type": "ProductBatch",
        "description": "Stock reduced (sale)",
        "timestamp": "2025-12-20 14:00:00",
        "human_time": "3 days ago",
        "performed_by": {
          "id": 8,
          "type": "Employee",
          "name": "Sarah Sales"
        },
        "changes": {
          "quantity": {
            "from": 48,
            "to": 47
          }
        }
      }
    ],
    "stock_summary": {
      "total_barcodes_generated": 50,
      "active_barcodes": 47,
      "sold_barcodes": 2,
      "defective_barcodes": 1,
      "current_stock_quantity": 47,
      "total_sales": 2,
      "total_dispatches": 2,
      "total_movements": 1
    }
  }
}
```

---

## Common Use Cases

### 1. Trace Product Origin & Journey

```javascript
// Find where a specific product came from and everywhere it's been
const response = await fetch('/api/lookup/product?barcode=BC12345678901', {
  headers: { 'Authorization': `Bearer ${token}` }
});

const { data } = await response.json();

console.log('Origin:', data.purchase_order_origin);
console.log('Current Location:', data.current_location);
console.log('Transfer History:', data.lifecycle.find(l => l.stage === 'dispatches').data);
console.log('Sale History:', data.lifecycle.find(l => l.stage === 'sales').data);
```

### 2. Verify Order Fulfillment with Specific Barcodes

```javascript
// Check which exact product units were sold in an order
const response = await fetch('/api/lookup/order/2345', {
  headers: { 'Authorization': `Bearer ${token}` }
});

const { data } = await response.json();

data.items.forEach(item => {
  console.log(`Product: ${item.product.name}`);
  console.log(`Barcode: ${item.barcode.barcode}`);
  console.log(`Status: ${item.barcode.current_status}`);
});
```

### 3. Audit Batch Distribution

```javascript
// See where all units from a batch ended up
const response = await fetch('/api/lookup/batch/456', {
  headers: { 'Authorization': `Bearer ${token}` }
});

const { data } = await response.json();

// Group barcodes by current location
const byLocation = {};
data.barcodes.forEach(barcode => {
  const location = barcode.current_location?.name || 'Unknown';
  byLocation[location] = (byLocation[location] || 0) + 1;
});

console.log('Distribution:', byLocation);
// Output: { "Main Store - Dhaka": 30, "Branch Store": 15, "Central Warehouse": 5 }
```

### 4. Track Customer Returns

```javascript
// Check if a product was returned
const response = await fetch('/api/lookup/product?barcode=BC12345678901', {
  headers: { 'Authorization': `Bearer ${token}` }
});

const { data } = await response.json();

const returns = data.lifecycle.find(l => l.stage === 'returns');
if (returns.count > 0) {
  console.log('Product was returned:');
  returns.data.forEach(ret => {
    console.log(`- Return #${ret.return_number}`);
    console.log(`  Reason: ${ret.return_reason}`);
    console.log(`  Date: ${ret.return_date}`);
  });
}
```

### 5. Identify Defective Products in a Batch

```javascript
// Find all defective units in a batch
const response = await fetch('/api/lookup/batch/456', {
  headers: { 'Authorization': `Bearer ${token}` }
});

const { data } = await response.json();

const defectiveBarcodes = data.barcodes.filter(b => b.is_defective);
console.log(`Found ${defectiveBarcodes.length} defective units`);

defectiveBarcodes.forEach(barcode => {
  console.log(`Barcode: ${barcode.barcode}`);
  console.log(`Reason: ${barcode.defective_record.defect_reason}`);
  console.log(`Discount: ${barcode.defective_record.discount_percentage}%`);
});
```

---

## Frontend Integration Examples

### Product Lifecycle Timeline Component

```jsx
function ProductLifecycleTimeline({ barcode }) {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetch(`/api/lookup/product?barcode=${barcode}`, {
      headers: { 'Authorization': `Bearer ${token}` }
    })
      .then(res => res.json())
      .then(result => {
        setData(result.data);
        setLoading(false);
      });
  }, [barcode]);

  if (loading) return <Spinner />;

  return (
    <div className="product-lifecycle">
      <h2>Product Journey: {data.product.name}</h2>
      
      {/* Current Status */}
      <div className="current-status">
        <Badge color={data.barcode.is_active ? 'green' : 'gray'}>
          {data.barcode.current_status}
        </Badge>
        <p>Currently at: {data.current_location?.store_name}</p>
      </div>

      {/* Timeline */}
      <Timeline>
        {/* Origin */}
        <TimelineItem>
          <TimelineIcon icon={<BoxIcon />} />
          <TimelineContent>
            <h4>Received from Purchase Order</h4>
            <p>PO: {data.purchase_order_origin?.po_number}</p>
            <p>{data.barcode.generated_at}</p>
          </TimelineContent>
        </TimelineItem>

        {/* Dispatches */}
        {data.lifecycle.find(l => l.stage === 'dispatches').data.map(dispatch => (
          <TimelineItem key={dispatch.dispatch_id}>
            <TimelineIcon icon={<TruckIcon />} />
            <TimelineContent>
              <h4>Transferred</h4>
              <p>From: {dispatch.from_store.name}</p>
              <p>To: {dispatch.to_store.name}</p>
              <p>{dispatch.dispatch_date}</p>
            </TimelineContent>
          </TimelineItem>
        ))}

        {/* Sales */}
        {data.lifecycle.find(l => l.stage === 'sales').data.map(sale => (
          <TimelineItem key={sale.order_id}>
            <TimelineIcon icon={<ShoppingCartIcon />} />
            <TimelineContent>
              <h4>Sold</h4>
              <p>Customer: {sale.customer.name}</p>
              <p>Order: {sale.order_number}</p>
              <p>Price: ৳{sale.sale_price}</p>
              <p>{sale.order_date}</p>
            </TimelineContent>
          </TimelineItem>
        ))}

        {/* Returns */}
        {data.lifecycle.find(l => l.stage === 'returns').data.map(ret => (
          <TimelineItem key={ret.return_id}>
            <TimelineIcon icon={<ReturnIcon />} />
            <TimelineContent>
              <h4>Returned</h4>
              <p>Reason: {ret.return_reason}</p>
              <p>Type: {ret.return_type}</p>
              <p>{ret.return_date}</p>
            </TimelineContent>
          </TimelineItem>
        ))}
      </Timeline>

      {/* Summary Stats */}
      <div className="summary-stats">
        <Stat label="Total Transfers" value={data.summary.total_dispatches} />
        <Stat label="Total Sales" value={data.summary.total_sales} />
        <Stat label="Total Returns" value={data.summary.total_returns} />
      </div>
    </div>
  );
}
```

### Order Details with Barcode Scanner Integration

```jsx
function OrderDetailsPage({ orderId }) {
  const [order, setOrder] = useState(null);

  useEffect(() => {
    fetch(`/api/lookup/order/${orderId}`, {
      headers: { 'Authorization': `Bearer ${token}` }
    })
      .then(res => res.json())
      .then(result => setOrder(result.data));
  }, [orderId]);

  if (!order) return <Spinner />;

  return (
    <div className="order-details">
      <h1>Order {order.order.order_number}</h1>
      
      {/* Order Status */}
      <div className="order-status">
        <Badge>{order.order.status}</Badge>
        <Badge>{order.order.fulfillment_status}</Badge>
        <Badge>{order.order.payment_status}</Badge>
      </div>

      {/* Customer Info */}
      <Card title="Customer">
        <p>{order.customer.name}</p>
        <p>{order.customer.phone}</p>
        <p>Type: {order.customer.customer_type}</p>
      </Card>

      {/* Items with Barcodes */}
      <Card title="Items Sold">
        <Table>
          <thead>
            <tr>
              <th>Product</th>
              <th>Barcode</th>
              <th>Batch</th>
              <th>Price</th>
              <th>Total</th>
            </tr>
          </thead>
          <tbody>
            {order.items.map(item => (
              <tr key={item.item_id}>
                <td>{item.product.name}</td>
                <td>
                  <code>{item.barcode?.barcode || 'N/A'}</code>
                  {item.barcode && (
                    <Badge color={item.barcode.is_active ? 'green' : 'gray'}>
                      {item.barcode.current_status}
                    </Badge>
                  )}
                </td>
                <td>{item.batch?.batch_number}</td>
                <td>৳{item.unit_price}</td>
                <td>৳{item.total_amount}</td>
              </tr>
            ))}
          </tbody>
        </Table>
      </Card>

      {/* Payments */}
      <Card title="Payments">
        {order.payments.map(payment => (
          <div key={payment.payment_id}>
            <p>Method: {payment.payment_method?.name}</p>
            <p>Amount: ৳{payment.amount}</p>
            <p>Transaction ID: {payment.transaction_id}</p>
            <p>Date: {payment.payment_date}</p>
          </div>
        ))}
      </Card>

      {/* Activity Log */}
      <Card title="Activity History">
        <Timeline>
          {order.activity_history.map(activity => (
            <TimelineItem key={activity.id}>
              <TimelineContent>
                <h4>{activity.description}</h4>
                <p>By: {activity.performed_by?.name}</p>
                <p>{activity.timestamp}</p>
                {Object.keys(activity.changes).length > 0 && (
                  <pre>{JSON.stringify(activity.changes, null, 2)}</pre>
                )}
              </TimelineContent>
            </TimelineItem>
          ))}
        </Timeline>
      </Card>
    </div>
  );
}
```

### Batch Barcode List Export

```javascript
async function exportBatchBarcodes(batchId) {
  const response = await fetch(`/api/lookup/batch/${batchId}`, {
    headers: { 'Authorization': `Bearer ${token}` }
  });
  
  const { data } = await response.json();
  
  // Generate CSV
  const csvHeader = 'Barcode,Status,Location,Defective,Generated Date\n';
  const csvRows = data.barcodes.map(barcode => {
    return [
      barcode.barcode,
      barcode.current_status,
      barcode.current_location?.name || 'N/A',
      barcode.is_defective ? 'Yes' : 'No',
      barcode.generated_at
    ].join(',');
  }).join('\n');
  
  const csv = csvHeader + csvRows;
  
  // Download file
  const blob = new Blob([csv], { type: 'text/csv' });
  const url = window.URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = `batch-${data.batch.batch_number}-barcodes.csv`;
  link.click();
}
```

---

## Error Responses

### 404 - Not Found

```json
{
  "success": false,
  "message": "Barcode not found"
}
```

```json
{
  "success": false,
  "message": "Order not found"
}
```

```json
{
  "success": false,
  "message": "Batch not found"
}
```

### 422 - Validation Error

```json
{
  "success": false,
  "errors": {
    "barcode": [
      "The barcode field is required."
    ]
  }
}
```

---

## Performance Tips

1. **Pagination for Large Histories**: Activity histories can be large. Consider adding pagination parameters in future versions.

2. **Caching**: Cache frequently accessed lookup data on the frontend for better performance.

3. **Lazy Loading**: Load lifecycle stages (dispatches, sales, returns) on demand rather than all at once.

4. **Barcode Scanning**: Use dedicated barcode scanner libraries for mobile/POS interfaces.

5. **Export Large Batches**: For batches with hundreds of barcodes, use background jobs for CSV/Excel generation.

---

## Security Notes

- All endpoints require authentication (`auth:api` middleware)
- Only authorized employees can access lookup APIs
- Sensitive customer information (phone, address) should be masked based on user permissions
- Activity logs show which employee performed each action for accountability

---

**Last Updated:** December 23, 2025  
**API Version:** 1.0
