# Product Journey: From Vendor to Sold

**Date:** January 29, 2026  
**Purpose:** Complete documentation of a product's lifecycle in the Deshio ERP system  
**Audience:** Frontend Developers, PM, New Team Members

---

## 📊 Overview Diagram

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                           PRODUCT LIFECYCLE JOURNEY                              │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌──────────┐    ┌──────────────┐    ┌────────────┐    ┌─────────────────────┐ │
│  │  VENDOR  │───▶│ PURCHASE     │───▶│ WAREHOUSE  │───▶│ PRODUCT BATCH       │ │
│  │          │    │ ORDER (PO)   │    │ (Store)    │    │ (Stock + Pricing)   │ │
│  └──────────┘    └──────────────┘    └────────────┘    └─────────────────────┘ │
│                                                               │                 │
│                                                               ▼                 │
│                                                        ┌─────────────────────┐  │
│                                                        │ PRODUCT BARCODE     │  │
│                                                        │ (Individual Unit)   │  │
│                                                        └─────────────────────┘  │
│                                                               │                 │
│  ┌──────────────────────────────────────────────────────────┘                  │
│  │                                                                              │
│  │    ┌────────────────┐         ┌────────────────┐         ┌──────────────┐  │
│  └───▶│ PRODUCT        │────────▶│ SHOP/STORE     │────────▶│ ORDER        │  │
│       │ DISPATCH       │         │ (Destination)  │         │ (Sale)       │  │
│       │ (Transfer)     │         │                │         │              │  │
│       └────────────────┘         └────────────────┘         └──────────────┘  │
│                                                                    │           │
│                                         ┌──────────────────────────┘           │
│                                         │                                      │
│                                         ▼                                      │
│  ┌──────────────┐    ┌──────────────────────────┐    ┌────────────────────┐   │
│  │ PRODUCT      │◀───│ ORDER ITEM               │───▶│ CUSTOMER           │   │
│  │ MOVEMENT     │    │ (What was sold)          │    │ (Who bought)       │   │
│  │ (Audit Log)  │    └──────────────────────────┘    └────────────────────┘   │
│  └──────────────┘                   │                                          │
│                                     │                                          │
│                                     ▼                                          │
│                          ┌──────────────────────┐                              │
│                          │ PRODUCT RETURN       │                              │
│                          │ (If returned)        │                              │
│                          └──────────────────────┘                              │
│                                                                                 │
└─────────────────────────────────────────────────────────────────────────────────┘
```

---

## 🏭 Stage 1: Product Creation

### What Happens
A new product is defined in the system with basic information.

### Database Table: `products`
| Column | Description | Example |
|--------|-------------|---------|
| `id` | Unique identifier | 123 |
| `name` | Display name | "saree-red-30" |
| `base_name` | Core name (for common edit) | "saree" |
| `variation_suffix` | Variation identifier | "-red-30" |
| `sku` | Stock Keeping Unit | "SAREE-001" |
| `category_id` | Product category | 5 (Clothing) |
| `vendor_id` | Default vendor | 2 (Textile Co) |
| `brand` | Brand name | "Deshi Fashion" |
| `description` | Product details | "Premium cotton saree" |

### API
```
POST /api/employee/products
```

### Model: `Product.php`
- Has many `ProductBatch` (stock entries)
- Has many `ProductBarcode` (individual units)
- Has many `ProductField` (custom attributes)
- Belongs to `Category`, `Vendor`

---

## 📦 Stage 2: Purchase from Vendor (Purchase Order)

### What Happens
Products are ordered from vendors. Only **warehouses** can receive products.

### Database Table: `purchase_orders`
| Column | Description | Example |
|--------|-------------|---------|
| `po_number` | Unique PO number | "PO-20260129-000001" |
| `vendor_id` | Supplier | 2 |
| `store_id` | Receiving warehouse | 1 (Main Warehouse) |
| `status` | Order status | "draft" → "approved" → "received" |
| `total_amount` | Total value | 50,000.00 |
| `payment_status` | Payment state | "unpaid" / "paid" |

### Status Flow
```
draft → pending_approval → approved → sent_to_vendor → partially_received → received
                                                     ↘ cancelled / returned
```

### Database Table: `purchase_order_items`
| Column | Description |
|--------|-------------|
| `purchase_order_id` | Parent PO |
| `product_id` | Product ordered |
| `quantity` | Quantity ordered |
| `unit_cost` | Cost per unit |
| `received_quantity` | How many received |

---

## 🏪 Stage 3: Warehouse Receipt (Product Batch)

### What Happens
When products arrive, they're recorded as **batches** with cost/sell prices.

### Database Table: `product_batches`
| Column | Description | Example |
|--------|-------------|---------|
| `batch_number` | Unique batch ID | "BTH-20260129-000001" |
| `product_id` | Which product | 123 |
| `store_id` | Which warehouse | 1 |
| `quantity` | How many units | 100 |
| `cost_price` | What we paid | 200.00 |
| `sell_price` | What we sell for (tax inclusive) | 350.00 |
| `base_price` | Price before tax | 318.18 |
| `tax_percentage` | Tax rate | 10% |
| `tax_amount` | Tax per unit | 31.82 |
| `manufactured_date` | When made | 2026-01-15 |
| `expiry_date` | When expires | 2027-01-15 |

### Tax Calculation
System supports **TAX_MODE**:
- **Inclusive**: `sell_price` includes tax → `base_price = sell_price / (1 + tax%)`
- **Exclusive**: `sell_price` is before tax → `base_price = sell_price`

### Model: `ProductBatch.php`
- Belongs to `Product`, `Store`
- Has many `ProductBarcode`

---

## 🏷️ Stage 4: Barcode Generation (Individual Units)

### What Happens
Each physical unit gets a unique barcode for tracking.

### Database Table: `product_barcodes`
| Column | Description | Example |
|--------|-------------|---------|
| `barcode` | Unique barcode | "2026012900000001" |
| `product_id` | Which product | 123 |
| `batch_id` | Which batch | 45 |
| `type` | Barcode type | "internal" / "ean13" |
| `is_primary` | Main barcode? | true |
| `is_active` | Can be sold? | true |
| `is_defective` | Damaged? | false |
| `current_store_id` | Current location | 1 |
| `current_status` | Current state | "in_warehouse" |

### Status Values
```
in_warehouse → in_transit → in_shop → sold → returned
                                    ↘ defective
```

### Model: `ProductBarcode.php`
- Belongs to `Product`, `ProductBatch`, `Store`
- Has location tracking methods
- Has movement history

---

## 🚚 Stage 5: Product Dispatch (Transfer Between Stores)

### What Happens
Products move from warehouse to shops (or between stores).

### Database Table: `product_dispatches`
| Column | Description | Example |
|--------|-------------|---------|
| `dispatch_number` | Unique ID | "DSP-20260129-000001" |
| `source_store_id` | From where | 1 (Warehouse) |
| `destination_store_id` | To where | 2 (Shop A) |
| `status` | Dispatch state | "pending" → "in_transit" → "received" |
| `total_items` | Total units | 50 |
| `total_value` | Total value | 17,500.00 |

### Status Flow
```
pending → approved → in_transit → partially_received → received
                              ↘ rejected / cancelled
```

### Database Table: `product_dispatch_items`
| Column | Description |
|--------|-------------|
| `product_dispatch_id` | Parent dispatch |
| `product_batch_id` | Which batch |
| `quantity` | How many |
| `status` | Item status |

### Special: Pathao Delivery
Dispatches can be marked `for_pathao_delivery = true` for customer deliveries.

---

## 📍 Stage 6: Product Movement (Audit Trail)

### What Happens
Every movement is logged for tracking and auditing.

### Database Table: `product_movements`
| Column | Description | Example |
|--------|-------------|---------|
| `product_batch_id` | Which batch moved | 45 |
| `product_barcode_id` | Which unit (if single) | 789 |
| `from_store_id` | Source | 1 |
| `to_store_id` | Destination | 2 |
| `movement_type` | Type of movement | see below |
| `quantity` | How many | 10 |
| `reference_type` | Related record type | "dispatch" |
| `reference_id` | Related record ID | 15 |

### Movement Types
| Type | Description |
|------|-------------|
| `purchase_receive` | Received from vendor |
| `transfer_out` | Sent to another store |
| `transfer_in` | Received from another store |
| `sale` | Sold to customer |
| `return` | Returned by customer |
| `adjustment` | Manual stock adjustment |
| `defective` | Marked as defective |

---

## 💰 Stage 7: Sale (Order Creation)

### What Happens
Customer buys products. Order created with items.

### Database Table: `orders`
| Column | Description | Example |
|--------|-------------|---------|
| `order_number` | Unique order ID | "ORD-20260129-000001" |
| `customer_id` | Who bought | 55 |
| `store_id` | Which store | 2 (Shop A) |
| `order_type` | Sale channel | "walk_in" / "social_commerce" / "ecommerce" |
| `status` | Order state | "pending" → "confirmed" → "fulfilled" |
| `total_amount` | Total sale value | 1,050.00 |
| `paid_amount` | Amount paid | 1,050.00 |
| `payment_status` | Payment state | "paid" |

### Order Status Flow
```
pending → confirmed → processing → fulfilled → shipped → delivered
                                            ↘ cancelled
```

### Database Table: `order_items`
| Column | Description | Example |
|--------|-------------|---------|
| `order_id` | Parent order | 100 |
| `product_id` | Which product | 123 |
| `product_batch_id` | From which batch | 45 |
| `product_barcode_id` | Specific unit (if scanned) | 789 |
| `store_id` | Fulfilling store | 2 |
| `quantity` | How many | 3 |
| `unit_price` | Price per unit | 350.00 |
| `cogs` | Cost of goods sold | 200.00 |
| `total_amount` | Line total | 1,050.00 |
| `product_options` | Size/color JSON | {"color": "red", "size": "30"} |

### Sale Methods
1. **Barcode Scan** (Walk-in POS): Scan individual barcode → auto-deduct from batch
2. **Product Select** (E-commerce): Select product + quantity → system picks batch (FIFO)

---

## 🔄 Stage 8: Product Return

### What Happens
Customer returns product. Stock can be restored or marked defective.

### Database Table: `product_returns`
| Column | Description | Example |
|--------|-------------|---------|
| `return_number` | Unique ID | "RTN-20260129-000001" |
| `order_id` | Original order | 100 |
| `customer_id` | Who returned | 55 |
| `return_reason` | Why returned | "wrong_size" |
| `return_type` | Full/partial | "partial" |
| `status` | Return state | "pending" → "approved" → "refunded" |
| `total_refund_amount` | Refund given | 350.00 |

### Return Status Flow
```
pending → received → inspected → approved → refunded
                              ↘ rejected
```

### What Happens to Returned Products
1. **Good condition**: Back to inventory (new batch or existing)
2. **Defective**: Marked as defective, separate tracking
3. **Vendor return**: Send back to vendor for credit

---

## 🗃️ Summary: Key Models & Relationships

```
Vendor
  └── Product (vendor_id)
        ├── ProductBatch (product_id) - Stock with pricing
        │     └── ProductBarcode (batch_id) - Individual units
        │
        ├── ProductField (product_id) - Custom attributes
        ├── ProductImage (product_id) - Photos
        └── ProductVariant (product_id) - [EXISTS but NOT used in main flow]

Store
  ├── ProductBatch (store_id) - Inventory location
  ├── ProductBarcode (current_store_id) - Unit location
  ├── ProductDispatch (source/destination_store_id) - Transfers
  └── Order (store_id) - Sales location

Customer
  ├── Order (customer_id) - Purchases
  └── ProductReturn (customer_id) - Returns

Order
  └── OrderItem (order_id)
        ├── Product (product_id)
        ├── ProductBatch (product_batch_id)
        └── ProductBarcode (product_barcode_id)
```

---

## 📡 Key APIs by Stage

| Stage | Endpoint | Method | Description |
|-------|----------|--------|-------------|
| 1 | `/products` | POST | Create product |
| 1 | `/products/{id}` | GET | View product |
| 2 | `/purchase-orders` | POST | Create PO |
| 2 | `/purchase-orders/{id}/receive` | POST | Receive goods |
| 3 | `/product-batches` | POST | Create batch |
| 3 | `/product-batches/{id}/adjust-stock` | POST | Adjust stock |
| 4 | `/product-barcodes/generate` | POST | Generate barcodes |
| 4 | `/product-barcodes/scan` | POST | Scan barcode |
| 5 | `/product-dispatches` | POST | Create dispatch |
| 5 | `/product-dispatches/{id}/receive` | POST | Receive dispatch |
| 7 | `/orders` | POST | Create order |
| 7 | `/orders/{id}/add-item` | POST | Add item to order |
| 8 | `/product-returns` | POST | Create return |
| 8 | `/product-returns/{id}/approve` | POST | Approve return |

---

## 💡 Business Rules

### Stock Deduction
- **Walk-in (POS)**: Scan barcode → specific unit sold
- **E-commerce**: System auto-selects batch (FIFO - First In First Out)

### Pricing
- **Batch-level pricing**: Each batch can have different sell price
- **Price overrides**: Customer-specific or store-specific prices possible

### Inventory Tracking
- **Batch-level**: Quantity tracked per batch
- **Barcode-level**: Individual unit status tracked
- **Store-level**: Each store has own inventory

### Tax Handling
- **Inclusive mode**: Customer sees final price, tax calculated backward
- **Exclusive mode**: Base price shown, tax added at checkout

---

## 🎯 Common Scenarios

### Scenario 1: New Product Arrival
```
1. Create Product (if new)
2. Create Purchase Order to Vendor
3. Receive PO → Creates ProductBatch at Warehouse
4. Generate Barcodes for units
5. Dispatch to Shops
6. Receive at Shop → Ready for sale
```

### Scenario 2: Walk-in Sale
```
1. Scan ProductBarcode
2. System finds Product, Batch, Store
3. Create Order + OrderItem
4. Process Payment
5. Barcode status → "sold"
6. ProductMovement logged
```

### Scenario 3: Online Sale
```
1. Customer selects Product + Quantity
2. Create Order (status: pending)
3. Confirm Order → Reserve stock
4. Fulfill Order → Deduct from batch
5. Ship Order
6. Deliver to Customer
```

### Scenario 4: Return
```
1. Create ProductReturn
2. Receive returned item
3. Quality check
4. If good → Return to inventory
5. If defective → Mark as defective
6. Process refund
```

---

**Author:** Backend Team  
**Last Updated:** January 29, 2026
