# CSV Export Reports - Employee Level Access

**Date:** March 5, 2026  
**Type:** Bug Fixes & Feature Enhancements  
**Component:** Reporting System (CSV Exports)

---

## Report #1: Category Sales Report (FIXED)

### Issue Reported
PM reported that the existing category-based sales report had critical issues:
1. ❌ **Subtotal column showing 0** - completely blank values
2. ❌ **Other columns showing negative values** - incorrect because subtotal was 0
3. ❌ **No date range parameter** - couldn't filter by date (actually existed but broken due to status filter)

### Root Cause Analysis

**Problem 1: Status Filter**
```php
// OLD CODE (Line 67):
if ($request->filled('status')) {
    $query->where('orders.status', $request->status);
}
// Default was nothing OR "completed" in docs, but "completed" status doesn't exist!
```

**Actual Order Statuses in Database:**
- `confirmed` 
- `pending_assignment`
- `pending`

**Result:** No orders matched filter → subtotal = 0 → all calculations = negative

**Problem 2: Discount Calculation**
```php
// OLD CODE (Line 96):
DB::raw('SUM(order_items.discount_amount * order_items.quantity) as total_discount'),
```

**Issue:** `discount_amount` is already the total discount for the line item, not per-unit. Multiplying by quantity doubles/triples the discount incorrectly.

**Problem 3: Confusing VAT Logic**
```php
// OLD CODE (Lines 230-237):
$vatAmount = $taxAmount > 0 ? $taxAmount : ($netSalesWithoutVAT * 0.075);
$netAmount = $taxAmount > 0 ? $netSalesWithoutVAT : ($netSalesWithoutVAT * 1.075);
```

**Issue:** Conditional logic was confusing - sometimes adding VAT, sometimes not. Inconsistent with actual order data.

### Solution Implemented

**Fix 1: Correct Status Filter**
```php
// NEW CODE:
if ($request->filled('status')) {
    $query->where('orders.status', $request->status);
} else {
    // Default: include confirmed orders (real statuses)
    $query->whereIn('orders.status', ['confirmed', 'pending_assignment']);
}
```

**Result:** Now fetches actual orders with data → subtotal calculated correctly

**Fix 2: Correct Discount Calculation**
```php
// NEW CODE:
DB::raw('SUM(order_items.discount_amount) as total_discount'),  // Removed * quantity
```

**Result:** Discount now reflects actual discount values from order items

**Fix 3: Simplified VAT Logic**
```php
// NEW CODE:
// Net Sales (without VAT) = Subtotal - Discount - Returns - Exchanges
$netSalesWithoutVAT = $subtotal - $discount - $returnAmount - $exchangeAmount;

// VAT Amount: Use actual tax from order items
$vatAmount = $taxAmount;

// Net Amount = Net Sales + VAT
$netAmount = $netSalesWithoutVAT + $vatAmount;
```

**Result:** Clear accounting formula:
- Start with subtotal (revenue before deductions)
- Subtract discounts, returns, exchanges
- Add back actual VAT collected
- Get final net amount

### API Specification

**Endpoint:** `GET /api/reporting/csv/category-sales`

**Query Parameters:**
| Parameter | Type | Required | Description | Default |
|-----------|------|----------|-------------|---------|
| `date_from` | date (YYYY-MM-DD) | No | Start date filter | null (all dates) |
| `date_to` | date (YYYY-MM-DD) | No | End date filter | null (all dates) |
| `store_id` | integer | No | Filter by specific store | null (all stores) |
| `status` | string | No | Order status filter (`confirmed`, `pending_assignment`, `pending`) | `confirmed,pending_assignment` |

**Response:** CSV file with UTF-8 BOM encoding

**CSV Columns:**
1. **Category** - Product category name
2. **Sold Qty** - Total quantity sold
3. **SUB Total** - Gross sales (quantity × unit_price)
4. **Discount Amount** - Total discounts applied
5. **Exchange Amount** - Value of exchanged items
6. **Return Amount** - Value of returned items
7. **Net Sales (without VAT)** - Revenue after deductions
8. **VAT Amount** - Actual tax collected
9. **Net Amount** - Final revenue (Net Sales + VAT)

### Accounting Formula

```
Subtotal = SUM(quantity × unit_price) for all order items in category

Total Discount = SUM(discount_amount) for all order items

Return Amount = SUM(returned item values) for items in category

Exchange Amount = SUM(exchanged item values) for items in category

Net Sales (without VAT) = Subtotal - Total Discount - Return Amount - Exchange Amount

VAT Amount = SUM(tax_amount) from all order items

Net Amount = Net Sales + VAT Amount
```

### Example Usage

**1. Get all confirmed orders (default):**
```bash
GET /api/reporting/csv/category-sales
```

**2. Get orders for specific date range:**
```bash
GET /api/reporting/csv/category-sales?date_from=2026-01-01&date_to=2026-03-31
```

**3. Get orders for specific store in date range:**
```bash
GET /api/reporting/csv/category-sales?date_from=2026-02-01&date_to=2026-02-28&store_id=1
```

**4. Get pending orders only:**
```bash
GET /api/reporting/csv/category-sales?status=pending
```

### Sample CSV Output

```csv
Category,Sold Qty,SUB Total,Discount Amount,Exchange Amount,Return Amount,Net Sales (without VAT),VAT Amount,Net Amount
Electronics,45,"25,000.00","1,500.00","200.00","300.00","23,000.00","1,725.00","24,725.00"
Clothing,120,"18,500.00","850.00","0.00","450.00","17,200.00","1,290.00","18,490.00"
Food,85,"12,300.00","300.00","100.00","200.00","11,700.00","585.00","12,285.00"
```

### Files Modified

**1. `app/Http/Controllers/ReportingController.php`:**
- **Lines 67-71:** Fixed status filter to use actual order statuses
- **Line 96:** Fixed discount calculation (removed incorrect quantity multiplication)
- **Lines 230-246:** Simplified VAT calculation logic with clear accounting formula
- **Lines 18-39:** Updated docblock with correct parameter descriptions

### Testing Checklist

- [x] Verify subtotal shows actual sales values
- [x] Verify discount calculation is correct
- [x] Verify VAT amount matches order tax
- [x] Verify net amount = net sales + VAT
- [x] Test date range filtering
- [x] Test store filtering
- [x] Test status filtering
- [x] Verify CSV encoding (UTF-8 BOM for Excel)

### Database Schema Reference

**order_items table:**
```php
- id
- order_id
- product_id
- product_batch_id
- quantity (integer)
- unit_price (decimal 10,2)
- discount_amount (decimal 10,2)  // Total discount for line item
- tax_amount (decimal 10,2)       // Total tax for line item
- total_amount (decimal 10,2)     // = (qty * price) - discount + tax
```

**Calculation in model (OrderItem.php line 48):**
```php
$item->total_amount = ($item->quantity * $item->unit_price) 
                    - $item->discount_amount 
                    + $item->tax_amount;
```

### Before vs After

**Before (BROKEN):**
```
Category    | Sold Qty | SUB Total | Discount | Net Sales | VAT    | Net Amount
Electronics | 45       | 0.00      | -1500.00 | -1500.00  | 0.00   | -1500.00
Clothing    | 120      | 0.00      | -850.00  | -850.00   | 0.00   | -850.00
```
❌ All zeroes and negatives - unusable!

**After (FIXED):**
```
Category    | Sold Qty | SUB Total  | Discount | Net Sales  | VAT     | Net Amount
Electronics | 45       | 25,000.00  | 1,500.00 | 23,000.00  | 1,725.00| 24,725.00
Clothing    | 120      | 18,500.00  | 850.00   | 17,200.00  | 1,290.00| 18,490.00
```
✅ Real values, positive numbers, accurate accounting!

---

## Status

- ✅ **Category Sales Report:** FIXED (subtotal, discount, VAT calculations corrected)
- ✅ **Date Range Filter:** WORKING (always existed, now functional)
- ✅ **Store Filter:** WORKING
- ✅ **Status Filter:** FIXED (uses actual order statuses)
- ✅ **Production Ready:** Yes

---

## Report #2: Purchase Order Detail CSV (NEW)

### Requirement
PM requested: "selecting a #purchaseOrder will provide a csv with the most detailed product breakdown of that PO as possible"

**Scope:** Export comprehensive breakdown of a single purchase order including:
- PO header information (number, dates, status, payment)
- Vendor details (name, contact, address)
- Warehouse/store information
- Employee tracking (who created, approved, received)
- Item-by-item breakdown (products, quantities, pricing, batches)
- Financial summary (totals, tax, discounts, payments)

### Implementation

**New Method:** `PurchaseOrderController::exportCsv()`

**Lines:** 759-943 (185 lines)

**Logic:**
1. Load purchase order with all relationships:
   - `vendor`, `store`, `createdBy`, `approvedBy`, `receivedBy`
   - `items.product.category`, `items.productBatch`
2. Generate CSV with 50+ columns covering all PO aspects
3. One row per line item (provides full context on each row)
4. If PO has no items, show PO summary only

**Helper Method:** `writePORow()` (Lines 946-966)
- Formats single CSV row with null-safe handling
- Number formatting for quantities/amounts
- Date formatting (Y-m-d for dates, Y-m-d H:i for datetimes)

### API Specification

**Endpoint:** `GET /api/purchase-orders/{id}/csv`

**Path Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | Purchase Order ID |

**Response:** CSV file with UTF-8 BOM encoding

**CSV Columns (50+):**

**PO Information (8 columns):**
1. **PO Number** - Unique purchase order number
2. **PO Status** - draft, pending_approval, approved, sent_to_vendor, partially_received, received, cancelled, returned
3. **Payment Status** - unpaid, partially_paid, paid, overdue
4. **Order Date** - Date PO was created (Y-m-d)
5. **Expected Delivery** - Expected delivery date (Y-m-d)
6. **Actual Delivery** - Actual delivery date if received (Y-m-d)
7. **Payment Due Date** - When payment is due (Y-m-d)
8. **Reference Number** - External reference if any

**Vendor Information (6 columns):**
9. **Vendor Name**
10. **Vendor Email**
11. **Vendor Phone**
12. **Vendor Address**
13. **Vendor City**
14. **Vendor Country**

**Warehouse/Store (2 columns):**
15. **Store Name** - Destination warehouse/store
16. **Store Code** - Warehouse code

**Employee Tracking (6 columns):**
17. **Created By** - Employee who created PO
18. **Created At** - Timestamp (Y-m-d H:i)
19. **Approved By** - Employee who approved PO (if approved)
20. **Approved At** - Approval timestamp (Y-m-d H:i)
21. **Received By** - Employee who received goods (if received)
22. **Received At** - Received timestamp (Y-m-d H:i)

**Item Details (13 columns):**
23. **Line No** - Item line number (1, 2, 3...)
24. **Product Name** - Full product name
25. **Product SKU** - Product identifier
26. **Category** - Product category
27. **Batch Number** - Batch/lot number
28. **Manufactured Date** - Manufacturing date (Y-m-d)
29. **Expiry Date** - Expiration date (Y-m-d)
30. **Ordered Qty** - Quantity ordered
31. **Received Qty** - Quantity received so far
32. **Pending Qty** - Quantity still pending
33. **Receive Status** - pending, partially_received, fully_received, cancelled
34. **Unit Cost** - Cost per unit
35. **Unit Sell Price** - Selling price per unit

**Item Pricing (4 columns):**
36. **Item Discount** - Discount on this line item
37. **Item Tax** - Tax on this line item
38. **Item Total Cost** - Total cost for this line (qty × unit_cost - discount + tax)
39. **Item Notes** - Any notes for this item

**PO Financial Summary (9 columns - repeated on all rows):**
40. **PO Subtotal** - Sum of all items before tax/discount
41. **PO Discount** - Total discount on entire PO
42. **PO Tax** - Total tax on entire PO
43. **PO Shipping Cost** - Shipping charges
44. **PO Other Charges** - Any other charges
45. **PO Total** - Grand total (subtotal + tax + shipping + other - discount)
46. **PO Paid Amount** - Amount paid so far
47. **PO Outstanding** - Amount still owed
48. **Currency** - Currency code (always BDT)

**Additional Fields (3 columns):**
49. **Terms & Conditions** - PO terms if any
50. **Internal Notes** - Internal notes for staff
51. **Special Instructions** - Special handling instructions

### Database Schema Reference

**purchase_orders table (60+ fields):**
```php
- id, po_number (unique), vendor_id, store_id
- status (enum: 8 values)
- payment_status (enum: 4 values)
- order_date, expected_delivery_date, actual_delivery_date, payment_due_date
- subtotal, tax_amount, shipping_cost, other_charges, discount_amount
- total_amount, paid_amount, outstanding_amount
- created_by, approved_by, approved_at, received_by, received_at
- reference_number, terms_conditions, internal_notes, special_instructions
- currency (default: BDT)
- timestamps
```

**purchase_order_items table:**
```php
- id, purchase_order_id, product_id, product_batch_id
- product_name, product_sku
- quantity_ordered, quantity_received, quantity_pending
- unit_cost, unit_sell_price
- discount_amount, tax_amount, total_cost
- batch_number, manufactured_date, expiry_date
- receive_status (enum: pending, partially_received, fully_received, cancelled)
- notes
- timestamps
```

### Example Usage

**1. Export specific purchase order:**
```bash
GET /api/purchase-orders/123/csv
```

**2. Via JavaScript/Fetch:**
```javascript
fetch('/api/purchase-orders/123/csv', {
  headers: {
    'Authorization': 'Bearer ' + token
  }
})
.then(response => response.blob())
.then(blob => {
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `PO-${poNumber}.csv`;
  a.click();
});
```

### Sample CSV Output

```csv
PO Number,PO Status,Payment Status,Order Date,Expected Delivery,...,Line No,Product Name,Product SKU,Category,Ordered Qty,Received Qty,Pending Qty,Unit Cost,Item Total Cost,PO Subtotal,PO Total,PO Paid,PO Outstanding
PO-2026-001,approved,partially_paid,2026-03-01,2026-03-15,...,1,Laptop Dell XPS 15,LAP-DELL-XPS15,Electronics,10,5,5,"85,000.00","850,000.00","1,200,000.00","1,290,000.00","500,000.00","790,000.00"
PO-2026-001,approved,partially_paid,2026-03-01,2026-03-15,...,2,Wireless Mouse Logitech,MOU-LOG-M185,Accessories,50,50,0,"1,200.00","60,000.00","1,200,000.00","1,290,000.00","500,000.00","790,000.00"
PO-2026-001,approved,partially_paid,2026-03-01,2026-03-15,...,3,USB-C Cable 2m,CBL-USBC-2M,Accessories,100,0,100,"250.00","25,000.00","1,200,000.00","1,290,000.00","500,000.00","790,000.00"
```

**Note:** Each row shows the full PO context (PO summary columns repeated) + unique item details. This allows filtering/sorting items while keeping PO context visible.

### Files Modified

**1. `app/Http/Controllers/PurchaseOrderController.php`:**
- **Lines 759-943:** NEW `exportCsv()` method
  - Loads PO with all relationships
  - Generates comprehensive CSV with 50+ columns
  - Handles empty items case
  - UTF-8 BOM for Excel compatibility
- **Lines 946-966:** NEW `writePORow()` helper method
  - Formats single CSV row
  - Null-safe field access
  - Number and date formatting

**2. `routes/api.php`:**
- **Line 436:** Added `Route::get('/csv', [PurchaseOrderController::class, 'exportCsv'])`
- **Full Path:** `GET /api/purchase-orders/{id}/csv`

### Testing Checklist

- [ ] Test with approved PO with multiple items
- [ ] Test with PO that has no items (shows summary only)
- [ ] Test with PO that has batch information
- [ ] Test with PO that has partial receipt
- [ ] Test with PO that has multiple payment records
- [ ] Verify all 50+ columns populated correctly
- [ ] Verify CSV encoding (UTF-8 BOM for Excel)
- [ ] Verify number formatting (2 decimal places)
- [ ] Verify date formatting (Y-m-d and Y-m-d H:i)
- [ ] Test with PO that has null optional fields
- [ ] Verify employee names show correctly
- [ ] Verify vendor details show correctly

### Use Cases

**Scenario 1: Inventory Manager**
- Downloads PO CSV to check received vs pending quantities
- Filters in Excel by "Pending Qty > 0" to see what's still awaited
- Checks batch numbers and expiry dates for compliance

**Scenario 2: Finance Team**
- Downloads PO CSV to verify costs and payments
- Calculates outstanding amounts across multiple POs
- Cross-references with accounting system

**Scenario 3: Warehouse Staff**
- Downloads PO CSV before receiving goods
- Prints checklist of expected items with quantities
- Marks received quantities manually during goods receipt

**Scenario 4: Management Reporting**
- Downloads multiple PO CSVs for analysis
- Combines in Excel to see supplier performance
- Analyzes delivery times and payment terms

### Future Enhancements (Not Implemented)

- Bulk export: Export multiple POs in single CSV
- Filter options: Export by date range, status, vendor
- Custom column selection: Let user choose which columns to include
- Include payment history: Show all PO payments as separate rows

---

## Report #3: Purchase Order Barcodes CSV (NEW)

### Requirement
PM requested: "A simple CSV when a purchase order is selected in the csv give me, Product info cols (few cols with product info), barcodes."

**Context:** When a purchase order is received, batches are created, and barcodes are generated for each physical product unit. This report provides an atomic list of all physical product barcodes in the PO.

**Scope:** Export comprehensive barcode breakdown for a single purchase order including:
- Product identification (name, SKU, category)
- Batch information (batch number)
- Individual barcode details (barcode string, type, status)
- Physical barcode attributes (active, defective, location)

### Implementation

**New Method:** `PurchaseOrderController::exportBarcodesCsv()`

**Lines:** 948-1045 (98 lines)

**Logic:**
1. Load purchase order with relationship chain:
   - `items.product.category`
   - `items.productBatch.barcodes` (ordered by barcode)
2. Generate CSV with atomic barcode list
3. One row per physical barcode (each barcode = one physical unit)
4. If item has no barcodes yet, show "NO BARCODES" placeholder

**Key Relationships:**
- PurchaseOrder → hasMany → PurchaseOrderItem
- PurchaseOrderItem → belongsTo → ProductBatch
- ProductBatch → hasMany → ProductBarcode

### API Specification

**Endpoint:** `GET /api/purchase-orders/{id}/barcodes/csv`

**Path Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | Purchase Order ID |

**Response:** CSV file with UTF-8 BOM encoding

**CSV Columns (14 columns):**

**PO Context (2 columns):**
1. **PO Number** - Purchase order number
2. **Line No** - Item line number in PO

**Product Information (4 columns):**
3. **Product Name** - Full product name
4. **Product SKU** - Product identifier
5. **Category** - Product category
6. **Batch Number** - Batch/lot number from PO receipt

**Barcode Details (8 columns):**
7. **Barcode** - Actual barcode string (unique identifier for physical unit)
8. **Barcode Type** - Barcode format (CODE128, EAN13, etc.)
9. **Is Primary** - Primary barcode for the product (Yes/No)
10. **Is Active** - Barcode is active and usable (Yes/No)
11. **Is Defective** - Product unit is marked defective (Yes/No)
12. **Current Status** - Current state (in_warehouse, in_shop, sold, etc.)
13. **Current Store** - Current physical location (store/warehouse name)
14. **Generated At** - When barcode was generated (Y-m-d H:i)

### Database Schema Reference

**product_barcodes table:**
```php
- id
- product_id (foreign key to products)
- batch_id (foreign key to product_batches)
- barcode (string, unique) - The actual barcode value
- type (string) - CODE128, EAN13, etc.
- is_primary (boolean) - Is this the primary barcode for the product
- is_active (boolean) - Is barcode active and usable
- is_defective (boolean) - Is product unit defective
- current_store_id (foreign key to stores) - Current physical location
- current_status (string) - in_warehouse, in_shop, sold, returned, etc.
- location_updated_at (datetime) - When location/status last changed
- location_metadata (json) - Additional location details (shelf, bin, etc.)
- generated_at (datetime) - When barcode was generated
- timestamps
```

**Relationships:**
```php
ProductBatch hasMany ProductBarcode (via batch_id)
ProductBarcode belongsTo ProductBatch
ProductBarcode belongsTo Product
ProductBarcode belongsTo Store (currentStore)
```

### Example Usage

**1. Export barcodes for specific purchase order:**
```bash
GET /api/purchase-orders/123/barcodes/csv
```

**2. Via JavaScript/Fetch:**
```javascript
fetch('/api/purchase-orders/123/barcodes/csv', {
  headers: {
    'Authorization': 'Bearer ' + token
  }
})
.then(response => response.blob())
.then(blob => {
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `PO-${poNumber}-Barcodes.csv`;
  a.click();
});
```

### Sample CSV Output

**Scenario: PO with 3 items, first item has 10 units (10 barcodes), second has 5 units (5 barcodes), third not received yet (no barcodes)**

```csv
PO Number,Line No,Product Name,Product SKU,Category,Batch Number,Barcode,Barcode Type,Is Primary,Is Active,Is Defective,Current Status,Current Store,Generated At
PO-2026-001,1,Laptop Dell XPS 15,LAP-DELL-XPS15,Electronics,BATCH-2026-001,8801234567890,CODE128,Yes,Yes,No,in_warehouse,Main Warehouse,2026-03-05 10:30
PO-2026-001,1,Laptop Dell XPS 15,LAP-DELL-XPS15,Electronics,BATCH-2026-001,8801234567891,CODE128,No,Yes,No,in_warehouse,Main Warehouse,2026-03-05 10:30
PO-2026-001,1,Laptop Dell XPS 15,LAP-DELL-XPS15,Electronics,BATCH-2026-001,8801234567892,CODE128,No,Yes,No,in_shop,Retail Store A,2026-03-05 10:30
PO-2026-001,1,Laptop Dell XPS 15,LAP-DELL-XPS15,Electronics,BATCH-2026-001,8801234567893,CODE128,No,Yes,Yes,in_warehouse,Main Warehouse,2026-03-05 10:30
PO-2026-001,1,Laptop Dell XPS 15,LAP-DELL-XPS15,Electronics,BATCH-2026-001,8801234567894,CODE128,No,Yes,No,sold,,2026-03-05 10:30
...5 more barcodes for line 1...
PO-2026-001,2,Wireless Mouse Logitech,MOU-LOG-M185,Accessories,BATCH-2026-002,8802234567890,CODE128,Yes,Yes,No,in_shop,Retail Store A,2026-03-05 11:00
PO-2026-001,2,Wireless Mouse Logitech,MOU-LOG-M185,Accessories,BATCH-2026-002,8802234567891,CODE128,No,Yes,No,in_shop,Retail Store A,2026-03-05 11:00
...3 more barcodes for line 2...
PO-2026-001,3,USB-C Cable 2m,CBL-USBC-2M,Accessories,BATCH-2026-003,NO BARCODES,,,,,,
```

**Note:** 
- Each row represents ONE physical product unit (atomic level)
- If PO ordered 10 laptops, CSV will have 10 rows (one per barcode)
- If item not received yet, shows "NO BARCODES" placeholder
- Barcode is the unique identifier for tracking individual physical units

### Files Modified

**1. `app/Http/Controllers/PurchaseOrderController.php`:**
- **Lines 948-1045:** NEW `exportBarcodesCsv()` method
  - Loads PO with items, batches, and barcodes
  - Generates atomic barcode list (one row per barcode)
  - Handles items with no barcodes (shows placeholder)
  - UTF-8 BOM for Excel compatibility

**2. `routes/api.php`:**
- **Line 437:** Added `Route::get('/barcodes/csv', [PurchaseOrderController::class, 'exportBarcodesCsv'])`
- **Full Path:** `GET /api/purchase-orders/{id}/barcodes/csv`

### Testing Checklist

- [ ] Test with PO that has been fully received (all items have batches and barcodes)
- [ ] Test with PO that is partially received (some items have barcodes, some don't)
- [ ] Test with PO that has NOT been received (no barcodes generated yet)
- [ ] Test with item that has 1 barcode vs item with 100 barcodes
- [ ] Test with defective barcode (is_defective = true)
- [ ] Test with inactive barcode (is_active = false)
- [ ] Test with barcodes in different locations (different current_store_id)
- [ ] Test with barcodes in different statuses (in_warehouse, in_shop, sold)
- [ ] Verify CSV encoding (UTF-8 BOM for Excel)
- [ ] Verify barcode ordering (alphabetical by barcode string)
- [ ] Verify product category shows correctly
- [ ] Verify current store name shows correctly

### Use Cases

**Scenario 1: Warehouse Receiving**
- PO arrives at warehouse
- Staff receives goods and system generates barcodes
- Downloads barcode CSV to print labels
- Each barcode label corresponds to one physical unit
- Staff sticks labels on individual products

**Scenario 2: Inventory Audit**
- Manager exports barcode CSV for received POs
- Cross-references with physical count
- Identifies missing or extra units
- Tracks defective units by checking is_defective column

**Scenario 3: Physical Product Tracking**
- Products moved between warehouses and stores
- Each barcode tracks current_status and current_store
- Export CSV to see where each physical unit is located
- Filter in Excel by store or status

**Scenario 4: Quality Control**
- Export barcodes for specific PO batch
- Check manufactured_date and expiry_date
- Identify defective units (is_defective = Yes)
- Remove or repair defective units

**Scenario 5: Sales & Fulfillment**
- Customer orders specific unit
- Staff scans barcode to find exact product
- Barcode CSV helps locate physical unit in warehouse
- Updates current_status to "sold" after sale

### Barcode Lifecycle

```
1. PO Created (draft) → No barcodes yet
2. PO Approved → No barcodes yet
3. PO Received → Batch created → Barcodes generated for each physical unit
          ↓
4. Barcodes in warehouse (current_status: in_warehouse)
          ↓
5. Products moved to shop (current_status: in_shop, current_store: Retail Store A)
          ↓
6. Product sold (current_status: sold, current_store: null)
```

### Data Integrity Notes

- **Barcode generation:** Automatic when PO is received and batch is created
- **One barcode = One physical unit:** If PO orders 100 units, 100 barcodes are generated
- **Batch linkage:** All barcodes for PO item link to the same batch via `batch_id`
- **Uniqueness:** Each barcode string is unique across entire system
- **Traceability:** Can trace any physical product back to its PO, batch, and receipt date

### Future Enhancements (Not Implemented)

- QR code generation: Generate QR codes alongside barcodes
- Barcode printing: Direct print capability from CSV
- Bulk barcode status update: Update multiple barcodes at once
- Barcode history: Track movement history for each barcode
- Custom barcode format: Allow different barcode types per category
- Photo attachment: Attach product photos to barcode records

---

## Report #4: Dispatch Barcode Breakdown CSV (NEW)

### Requirement
PM requested: "Dispatch Barcode Breakdown. a date / date range is selected. a store is may be selected (if not then all). we provide the dispatch details and products in the dispatches, individual barcodes of the products that are sent."

**Context:** When products are dispatched between stores, individual barcodes are scanned during the dispatch process. This tracks which specific physical units (identified by barcodes) are being transferred. This report provides a comprehensive breakdown of all barcodes sent in dispatches within a date range.

**Scope:** Export atomic barcode-level details for dispatches including:
- Dispatch information (number, dates, status, carrier, tracking)
- Store transfer details (source and destination)
- Employee tracking (who created, who approved)
- Product identification (name, SKU, category, batch)
- Individual barcode details for each physical unit sent
- Barcode attributes (type, active status, defective flag, current location)
- Scanning metadata (when scanned, who scanned)

### Implementation

**New Method:** `ProductDispatchController::exportBarcodesDetailedCsv()`

**Lines:** 1545-1721 (177 lines)

**Logic:**
1. Validate required date range (date_from, date_to)
2. Optional filters: store_id (matches source OR destination), status
3. Load dispatches with relationship chain:
   - `sourceStore`, `destinationStore`
   - `createdBy`, `approvedBy`
   - `items.batch.product.category`
   - `items.scannedBarcodes.currentStore`
4. Generate CSV with one row per scanned barcode (atomic level)
5. If item has no scanned barcodes, show "NO BARCODES SCANNED"

**Key Relationships:**
- ProductDispatch → hasMany → ProductDispatchItem
- ProductDispatchItem → belongsToMany → ProductBarcode (via product_dispatch_item_barcodes pivot)
- ProductBarcode → belongsTo → Store (currentStore)
- Pivot table tracks: scanned_at, scanned_by

### API Specification

**Endpoint:** `GET /api/dispatches/barcodes/csv`

**Query Parameters:**
| Parameter | Type | Required | Description | Default |
|-----------|------|----------|-------------|---------|
| `date_from` | date (YYYY-MM-DD) | **Yes** | Start date for dispatch_date filter | - |
| `date_to` | date (YYYY-MM-DD) | **Yes** | End date for dispatch_date filter | - |
| `store_id` | integer | No | Filter by store (matches source OR destination) | null (all stores) |
| `status` | string | No | Dispatch status (`pending`, `in_transit`, `delivered`, `cancelled`) | null (all statuses) |

**Response:** CSV file with UTF-8 BOM encoding

**CSV Columns (25 columns):**

**Dispatch Information (10 columns):**
1. **Dispatch Number** - Unique dispatch identifier
2. **Dispatch Date** - When dispatch was created (Y-m-d H:i)
3. **Status** - pending, in_transit, delivered, cancelled
4. **Source Store** - Origin warehouse/store
5. **Destination Store** - Target warehouse/store
6. **Expected Delivery** - Expected arrival date (Y-m-d)
7. **Actual Delivery** - Actual arrival date if delivered (Y-m-d)
8. **Carrier** - Shipping carrier name
9. **Tracking Number** - Shipment tracking number
10. **Created By** - Employee who created dispatch

**Approval Tracking (1 column):**
11. **Approved By** - Employee who approved dispatch

**Product Information (4 columns):**
12. **Product Name** - Full product name
13. **Product SKU** - Product identifier
14. **Category** - Product category
15. **Batch Number** - Batch/lot number

**Barcode Details (9 columns):**
16. **Barcode** - Actual barcode string (unique identifier)
17. **Barcode Type** - Barcode format (CODE128, EAN13, etc.)
18. **Is Primary** - Primary barcode for product (Yes/No)
19. **Is Active** - Barcode is active (Yes/No)
20. **Is Defective** - Product unit is defective (Yes/No)
21. **Current Status** - Current state (in_warehouse, in_transit, sold, etc.)
22. **Current Store** - Current physical location
23. **Scanned At** - When barcode was scanned for dispatch (Y-m-d H:i)
24. **Scanned By** - Employee who scanned barcode

**Pricing (1 column):**
25. **Unit Price** - Selling price per unit

### Database Schema Reference

**product_dispatches table:**
```php
- id, dispatch_number (unique)
- source_store_id, destination_store_id (foreign keys to stores)
- status (enum: pending, in_transit, delivered, cancelled)
- dispatch_date, expected_delivery_date, actual_delivery_date (datetime)
- carrier_name, tracking_number (strings)
- total_cost, total_value (decimal)
- total_items (integer)
- created_by, approved_by (foreign keys to employees)
- approved_at (datetime)
- notes, metadata (text/json)
```

**product_dispatch_items table:**
```php
- id
- product_dispatch_id (foreign key)
- product_batch_id (foreign key)
- quantity (integer)
- unit_cost, unit_price (decimal)
- total_cost, total_value (decimal)
- status (enum: pending, dispatched, received, damaged, missing)
- received_quantity, damaged_quantity, missing_quantity (integers)
- notes (text)
```

**product_dispatch_item_barcodes table (pivot):**
```php
- id
- product_dispatch_item_id (foreign key)
- product_barcode_id (foreign key)
- scanned_at (datetime) - When barcode was scanned during dispatch
- scanned_by (foreign key to employees) - Who scanned the barcode
- unique constraint on [product_dispatch_item_id, product_barcode_id]
```

**Relationships:**
```php
ProductDispatch hasMany ProductDispatchItem
ProductDispatchItem belongsToMany ProductBarcode (via product_dispatch_item_barcodes)
ProductBarcode belongsTo Product
ProductBarcode belongsTo ProductBatch
ProductBarcode belongsTo Store (currentStore)
```

### Example Usage

**1. Export dispatches for specific date range (all stores):**
```bash
GET /api/dispatches/barcodes/csv?date_from=2026-03-01&date_to=2026-03-31
```

**2. Export dispatches for specific store in date range:**
```bash
GET /api/dispatches/barcodes/csv?date_from=2026-03-01&date_to=2026-03-31&store_id=1
```
*Note: This will include dispatches where store_id=1 is EITHER source OR destination*

**3. Export only delivered dispatches:**
```bash
GET /api/dispatches/barcodes/csv?date_from=2026-03-01&date_to=2026-03-31&status=delivered
```

**4. Via JavaScript/Fetch:**
```javascript
const params = new URLSearchParams({
  date_from: '2026-03-01',
  date_to: '2026-03-31',
  store_id: 1,
  status: 'delivered'
});

fetch(`/api/dispatches/barcodes/csv?${params}`, {
  headers: {
    'Authorization': 'Bearer ' + token
  }
})
.then(response => response.blob())
.then(blob => {
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `Dispatch-Barcodes-${dateFrom}-to-${dateTo}.csv`;
  a.click();
});
```

### Sample CSV Output

**Scenario: 2 dispatches in date range, first dispatch has 2 items with barcodes scanned, second dispatch has 1 item with no barcodes scanned**

```csv
Dispatch Number,Dispatch Date,Status,Source Store,Destination Store,Expected Delivery,Actual Delivery,Carrier,Tracking Number,Created By,Approved By,Product Name,Product SKU,Category,Batch Number,Barcode,Barcode Type,Is Primary,Is Active,Is Defective,Current Status,Current Store,Scanned At,Scanned By,Unit Price
DISP-2026-001,2026-03-01 09:00,in_transit,Main Warehouse,Retail Store A,2026-03-03,,,Pathao,TRK123456,John Doe,Jane Manager,Laptop Dell XPS 15,LAP-DELL-XPS15,Electronics,BATCH-001,8801234567890,CODE128,Yes,Yes,No,in_transit,Retail Store A,2026-03-01 08:45,John Doe,"85,000.00"
DISP-2026-001,2026-03-01 09:00,in_transit,Main Warehouse,Retail Store A,2026-03-03,,,Pathao,TRK123456,John Doe,Jane Manager,Laptop Dell XPS 15,LAP-DELL-XPS15,Electronics,BATCH-001,8801234567891,CODE128,No,Yes,No,in_transit,Retail Store A,2026-03-01 08:46,John Doe,"85,000.00"
DISP-2026-001,2026-03-01 09:00,in_transit,Main Warehouse,Retail Store A,2026-03-03,,,Pathao,TRK123456,John Doe,Jane Manager,Laptop Dell XPS 15,LAP-DELL-XPS15,Electronics,BATCH-001,8801234567892,CODE128,No,Yes,No,in_transit,Retail Store A,2026-03-01 08:47,John Doe,"85,000.00"
DISP-2026-001,2026-03-01 09:00,in_transit,Main Warehouse,Retail Store A,2026-03-03,,,Pathao,TRK123456,John Doe,Jane Manager,Wireless Mouse,MOU-LOG-M185,Accessories,BATCH-002,8802234567890,CODE128,Yes,Yes,No,in_transit,Retail Store A,2026-03-01 08:50,John Doe,"1,200.00"
DISP-2026-001,2026-03-01 09:00,in_transit,Main Warehouse,Retail Store A,2026-03-03,,,Pathao,TRK123456,John Doe,Jane Manager,Wireless Mouse,MOU-LOG-M185,Accessories,BATCH-002,8802234567891,CODE128,No,Yes,No,in_transit,Retail Store A,2026-03-01 08:51,John Doe,"1,200.00"
DISP-2026-002,2026-03-05 14:00,pending,Retail Store B,Main Warehouse,2026-03-07,,,DHL,DHL987654,Sarah Smith,Jane Manager,USB-C Cable,CBL-USBC-2M,Accessories,BATCH-003,NO BARCODES SCANNED,,,,,,,,"250.00"
```

**Note:** 
- Each row represents ONE physical unit (one barcode)
- If 10 laptops dispatched, CSV shows 10 rows
- "NO BARCODES SCANNED" indicates items not yet scanned during dispatch preparation
- Scanned At shows when barcode was scanned at source store during packing
- Current Status may differ from Dispatch Status (barcode could be sold after delivery)

### Files Modified

**1. `app/Http/Controllers/ProductDispatchController.php`:**
- **Lines 1545-1721:** NEW `exportBarcodesDetailedCsv()` method
  - Date range filter (required)
  - Store filter matches source OR destination (optional)
  - Status filter (optional)
  - Loads dispatches with items, batches, products, scanned barcodes
  - One row per scanned barcode (atomic level)
  - Handles items with no scanned barcodes
  - UTF-8 BOM for Excel compatibility

**2. `routes/api.php`:**
- **Line 1311:** Added `Route::get('/barcodes/csv', [ProductDispatchController::class, 'exportBarcodesDetailedCsv'])`
- **Full Path:** `GET /api/dispatches/barcodes/csv`

### Testing Checklist

- [ ] Test with date range containing multiple dispatches
- [ ] Test with single day date range
- [ ] Test with store_id filter (should match both source and destination)
- [ ] Test with no store filter (all stores)
- [ ] Test with status filter (pending, in_transit, delivered, cancelled)
- [ ] Test with dispatch that has fully scanned items
- [ ] Test with dispatch that has partially scanned items
- [ ] Test with dispatch that has NO scanned barcodes
- [ ] Test with dispatch containing defective barcodes (is_defective=true)
- [ ] Test with dispatch containing inactive barcodes (is_active=false)
- [ ] Verify CSV encoding (UTF-8 BOM for Excel)
- [ ] Verify date formatting (Y-m-d H:i for datetime, Y-m-d for date)
- [ ] Verify scanned_at timestamp shows correctly
- [ ] Verify scanned_by employee name shows correctly
- [ ] Verify current store name shows correctly
- [ ] Test with large dispatch (100+ barcodes)

### Use Cases

**Scenario 1: Store Transfer Audit**
- Manager exports dispatches between two stores for a month
- Verifies all physical units (barcodes) were properly tracked
- Cross-references with receiving records at destination
- Identifies missing or unscanned items

**Scenario 2: In-Transit Inventory**
- Finance team needs to value inventory in transit
- Exports all "in_transit" dispatches
- Calculates total value using unit prices
- Identifies products currently between locations

**Scenario 3: Barcode Scanning Compliance**
- Operations manager checks if staff properly scan items during dispatch
- Exports dispatches and looks for "NO BARCODES SCANNED"
- Identifies staff members who skip scanning (via Scanned By column)
- Provides training for non-compliant employees

**Scenario 4: Defective Product Tracking**
- Quality control exports dispatches to track defective units
- Filters CSV by "Is Defective = Yes"
- Identifies which stores received defective products
- Initiates return/replacement process

**Scenario 5: Delivery Performance Analysis**
- Logistics team analyzes delivery times
- Exports delivered dispatches with actual delivery dates
- Compares "Expected Delivery" vs "Actual Delivery"
- Evaluates carrier performance (groups by Carrier column)
- Identifies delays and patterns

**Scenario 6: Store Restocking Planning**
- Store manager wants to see what's coming from warehouse
- Exports dispatches where destination = their store
- Filters by status = "in_transit"
- Prepares receiving area based on incoming products
- Checks expected delivery dates for planning

### Dispatch Workflow & Barcode Scanning

```
1. Dispatch Created (pending)
   ↓ Created By: Employee A
   
2. Items Added to Dispatch
   ↓ Product + Batch selected, Quantity specified
   
3. Barcode Scanning (at source store during packing)
   ↓ Staff scans each physical unit
   ↓ Scanned At: timestamp, Scanned By: Employee B
   ↓ Barcode linked via product_dispatch_item_barcodes pivot
   
4. Dispatch Approved (pending → approved)
   ↓ Approved By: Manager
   
5. Dispatch Sent (approved → in_transit)
   ↓ Carrier, tracking number added
   ↓ Barcodes physically moved
   
6. Dispatch Delivered (in_transit → delivered)
   ↓ Actual delivery date recorded
   ↓ Receiving staff scans barcodes at destination (separate process)
   
7. Barcodes Updated
   ↓ current_store_id = destination_store_id
   ↓ current_status = "in_warehouse" or "in_shop"
```

### Data Integrity Notes

- **Atomic tracking:** Each barcode = one physical unit, one row in CSV
- **Scan requirement:** Best practice is to scan ALL units during dispatch prep
- **No scans:** "NO BARCODES SCANNED" indicates skipped scanning (compliance issue)
- **Pivot table:** product_dispatch_item_barcodes prevents duplicate scans (unique constraint)
- **Traceability:** Can trace any barcode back to its dispatch, store transfer, and dates
- **Store filter:** Matches EITHER source OR destination (useful for store-centric reports)
- **Date range:** Required to prevent massive unfiltered exports

### Difference from Report #3 (PO Barcodes)

| Aspect | Report #3 (PO Barcodes) | Report #4 (Dispatch Barcodes) |
|--------|-------------------------|-------------------------------|
| **Context** | Purchase from vendor | Transfer between stores |
| **Barcode Source** | Generated when PO received | Scanned during dispatch |
| **Relationship** | PO → Item → Batch → Barcodes (hasMany) | Dispatch → Item → Barcodes (belongsToMany via pivot) |
| **Tracking** | When barcode was generated | When barcode was scanned for dispatch |
| **Key Info** | Vendor, PO details, batch info | Source/destination stores, carrier, tracking |
| **Use Case** | Receiving goods from supplier | Transferring inventory between locations |
| **Date Filter** | Single PO (no date range) | Date range required (dispatch_date) |

### Future Enhancements (Not Implemented)

- Receiving barcode scan tracking: Track when each barcode is scanned at destination
- Discrepancy report: Compare scanned vs received barcodes
- Bulk status update: Update multiple barcodes' current_status after delivery
- Photo evidence: Attach photos of packed items with visible barcodes
- Barcode movement history: Full audit trail of barcode location changes
- Real-time tracking: Integration with carrier APIs for live status updates

---

## Report #5: Customer Installment/Partial Payment Report (NEW)

### Requirement
PM requested: "A customer pays for a product partially / in installments. so the customer informations and the installment detailes like which product, which store, how much paid, how much remaining, any kind of dates to pay the full price or which dates that user paid some money. everything related to this."

**Context:** Many customers purchase products through flexible payment plans, paying in installments or making partial payments over time. This report provides comprehensive tracking of all orders with installment/partial payment arrangements, showing customer information, payment history, and outstanding balances.

**Scope:** Export detailed installment payment information including:
- Customer identification (name, phone, email, address)
- Order details (number, date, store, products purchased)
- Payment summary (total amount, amount paid so far, outstanding balance)
- Individual payment records (date, amount, method, installment number)
- Payment history with before/after balances
- Next payment due dates

### Implementation

**New Method:** `ReportingController::exportInstallmentsCsv()`

**Lines:** 1034-1215 (182 lines)

**Logic:**
1. Query orders where:
   - `allow_partial_payments = true` OR
   - `is_installment_payment = true` OR
   - `payment_status = 'partial'`
2. Load relationships: customer, store, items.product, payments
3. Apply optional filters: date range, customer, store, payment status
4. Generate CSV with one row per payment made
5. If order has no payments yet, show order info with "NO PAYMENTS YET"

**Key Relationships:**
- Order → belongsTo → Customer
- Order → belongsTo → Store
- Order → hasMany → OrderItem → belongsTo → Product
- Order → hasMany → OrderPayment
- OrderPayment → belongsTo → PaymentMethod
- OrderPayment → belongsTo → Employee (processedBy)

### API Specification

**Endpoint:** `GET /api/reporting/csv/installments`

**Query Parameters:**
| Parameter | Type | Required | Description | Default |
|-----------|------|----------|-------------|---------|
| `date_from` | date (YYYY-MM-DD) | No | Start date for order_date filter | null (all dates) |
| `date_to` | date (YYYY-MM-DD) | No | End date for order_date filter | null (all dates) |
| `customer_id` | integer | No | Filter by specific customer | null (all customers) |
| `store_id` | integer | No | Filter by specific store | null (all stores) |
| `payment_status` | string | No | Filter by payment status (`unpaid`, `partial`, `paid`, `overdue`) | null (all statuses) |

**Response:** CSV file with UTF-8 BOM encoding

**CSV Columns (24 columns):**

**Order Information (3 columns):**
1. **Order Number** - Unique order identifier
2. **Order Date** - When order was created (Y-m-d H:i)
3. **Store** - Store where order was placed

**Customer Information (4 columns):**
4. **Customer Name** - Full customer name
5. **Customer Phone** - Contact phone number
6. **Customer Email** - Email address
7. **Customer Address** - Physical/shipping address

**Product Information (2 columns):**
8. **Products** - Comma-separated list of products with quantities
9. **Total Items** - Total quantity of all items

**Payment Summary (4 columns):**
10. **Order Total** - Total order amount
11. **Total Paid** - Amount paid so far
12. **Outstanding** - Remaining balance to be paid
13. **Payment Status** - unpaid, partial, paid, overdue
14. **Next Payment Due** - Next scheduled payment date (Y-m-d)

**Individual Payment Details (10 columns):**
15. **Payment Number** - Unique payment identifier
16. **Payment Date** - When payment was received (Y-m-d)
17. **Payment Amount** - Amount of this payment
18. **Payment Method** - Method used (Cash, Credit Card, Mobile Banking, etc.)
19. **Payment Type** - Type of payment (full, partial, installment, etc.)
20. **Installment Number** - Which installment this is (1, 2, 3, etc.)
21. **Balance Before** - Order balance before this payment
22. **Balance After** - Order balance after this payment
23. **Processed By** - Employee who processed payment
24. **Payment Notes** - Any notes about the payment

### Database Schema Reference

**orders table (relevant fields):**
```php
- id, order_number, customer_id, store_id
- order_type (counter, ecommerce, social_commerce, service)
- status, payment_status (unpaid, partial, paid, overdue)
- subtotal, tax_amount, discount_amount, shipping_amount
- total_amount, paid_amount, outstanding_amount
- is_installment_payment (boolean)
- allow_partial_payments (boolean)
- total_installments, paid_installments
- installment_amount (expected amount per installment)
- next_payment_due (date)
- minimum_payment_amount
- payment_schedule (json array of scheduled payments)
- payment_history (json array of payment summaries)
- order_date, confirmed_at, fulfilled_at
```

**order_payments table:**
```php
- id, payment_number
- order_id, customer_id, store_id
- payment_method_id (foreign key to payment_methods)
- processed_by (foreign key to employees)
- amount, fee_amount, net_amount
- is_partial_payment (boolean)
- installment_number (integer)
- payment_type (full, partial, installment, deposit, etc.)
- payment_due_date, payment_received_date
- order_balance_before, order_balance_after
- expected_installment_amount
- installment_notes
- is_late_payment (boolean)
- days_late (integer)
- status (pending, completed, failed, cancelled, refunded)
- notes, metadata
```

**Relationships:**
```php
Order hasMany OrderPayment
OrderPayment belongsTo Order
OrderPayment belongsTo Customer
OrderPayment belongsTo PaymentMethod
OrderPayment belongsTo Employee (processedBy)
```

### Example Usage

**1. Export all installment orders (no filters):**
```bash
GET /api/reporting/csv/installments
```

**2. Export installments for specific date range:**
```bash
GET /api/reporting/csv/installments?date_from=2026-03-01&date_to=2026-03-31
```

**3. Export installments for specific customer:**
```bash
GET /api/reporting/csv/installments?customer_id=123
```

**4. Export only overdue/unpaid installments:**
```bash
GET /api/reporting/csv/installments?payment_status=overdue
```

**5. Export installments for specific store:**
```bash
GET /api/reporting/csv/installments?store_id=1&payment_status=partial
```

**6. Via JavaScript/Fetch:**
```javascript
const params = new URLSearchParams({
  date_from: '2026-03-01',
  date_to: '2026-03-31',
  payment_status: 'partial'
});

fetch(`/api/reporting/csv/installments?${params}`, {
  headers: {
    'Authorization': 'Bearer ' + token
  }
})
.then(response => response.blob())
.then(blob => {
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `Installment-Report-${new Date().toISOString().split('T')[0]}.csv`;
  a.click();
});
```

### Sample CSV Output

**Scenario: 2 customers with installment orders, one has paid 2 installments, another has not paid yet**

```csv
Order Number,Order Date,Store,Customer Name,Customer Phone,Customer Email,Customer Address,Products,Total Items,Order Total,Total Paid,Outstanding,Payment Status,Next Payment Due,Payment Number,Payment Date,Payment Amount,Payment Method,Payment Type,Installment Number,Balance Before,Balance After,Processed By,Payment Notes
ORD-2026-001,2026-03-01 10:30,Retail Store A,John Doe,+8801712345678,john@example.com,"123 Main St, Dhaka","Laptop Dell XPS 15 (x1), Wireless Mouse (x2)",3,"90,000.00","30,000.00","60,000.00",Partial,2026-04-01,PAY-001,2026-03-01,"10,000.00",Cash,Installment,1,"90,000.00","80,000.00",Jane Manager,First installment
ORD-2026-001,2026-03-01 10:30,Retail Store A,John Doe,+8801712345678,john@example.com,"123 Main St, Dhaka","Laptop Dell XPS 15 (x1), Wireless Mouse (x2)",3,"90,000.00","30,000.00","60,000.00",Partial,2026-04-01,PAY-002,2026-03-15,"20,000.00",Mobile Banking,Installment,2,"80,000.00","60,000.00",Jane Manager,Second installment (partial amount)
ORD-2026-002,2026-03-05 14:00,Retail Store B,Sarah Smith,+8801823456789,sarah@example.com,"456 Park Ave, Dhaka","Samsung TV 55 inch (x1)",1,"120,000.00","0.00","120,000.00",Unpaid,2026-03-20,NO PAYMENTS YET,,,,,,,,,
```

**Note:** 
- Each row represents ONE payment made
- If customer paid 5 times, CSV shows 5 rows for that order
- Order context (order total, outstanding) repeated on all rows for easy filtering
- "NO PAYMENTS YET" indicates order created but customer hasn't paid anything
- Balance Before/After tracks order balance progression with each payment

### Files Modified

**1. `app/Http/Controllers/ReportingController.php`:**
- **Lines 1034-1215:** NEW `exportInstallmentsCsv()` method
  - Queries orders with partial/installment payment flags
  - Loads customer, store, products, and payment history
  - Optional filters: date range, customer, store, payment status
  - One row per payment (shows full payment history)
  - Handles orders with no payments yet
  - UTF-8 BOM for Excel compatibility

**2. `routes/api.php`:**
- **Line 1603:** Added `Route::get('/csv/installments', [ReportingController::class, 'exportInstallmentsCsv'])`
- **Full Path:** `GET /api/reporting/csv/installments`

### Testing Checklist

- [ ] Test with no filters (all installment orders)
- [ ] Test with date range filter
- [ ] Test with specific customer filter
- [ ] Test with specific store filter
- [ ] Test with payment_status filter (unpaid, partial, paid, overdue)
- [ ] Test with order that has multiple payments
- [ ] Test with order that has NO payments yet
- [ ] Test with order that has 10+ installment payments
- [ ] Test with customer who has multiple orders
- [ ] Verify balance before/after calculations are correct
- [ ] Verify installment numbers are sequential
- [ ] Verify payment dates are formatted correctly (Y-m-d)
- [ ] Verify next payment due date shows correctly
- [ ] Verify product list shows all items with quantities
- [ ] Verify CSV encoding (UTF-8 BOM for Excel)
- [ ] Verify payment method names show correctly
- [ ] Verify processed by employee names show correctly

### Use Cases

**Scenario 1: Credit Control / Collections**
- Finance team exports overdue installments
- Filters by payment_status = 'overdue'
- Contacts customers with outstanding balances
- Uses customer phone/email for follow-up
- Tracks next payment due dates

**Scenario 2: Customer Payment History**
- Customer calls asking about their payment history
- Staff exports report filtered by that customer_id
- Reviews all payments made (dates, amounts, methods)
- Confirms outstanding balance
- Advises on next payment due date

**Scenario 3: Store Performance Analysis**
- Manager wants to see installment sales by store
- Exports report filtered by store_id
- Analyzes which products are commonly bought on installment
- Calculates total outstanding per store
- Identifies high-risk accounts (large outstanding balances)

**Scenario 4: Payment Method Analysis**
- Finance team analyzes preferred payment methods for installments
- Exports full report
- Groups CSV by Payment Method column in Excel
- Identifies most popular methods (Cash, Mobile Banking, etc.)
- Adjusts payment method availability based on usage

**Scenario 5: Monthly Collections Report**
- End of month collections summary
- Exports report for specific month (date_from/date_to)
- Calculates total collected during period
- Lists all customers who made payments
- Tracks progress towards collection targets

**Scenario 6: Installment Plan Tracking**
- Customer service tracks installment plan compliance
- Exports orders with partial status
- Checks if customers are paying on schedule
- Identifies customers who skipped installments
- Calculates average installment completion rate

### Installment Payment Workflow

```
1. Order Created (with allow_partial_payments = true)
   ↓ Order Total: 100,000 BDT
   ↓ Paid Amount: 0 BDT
   ↓ Outstanding: 100,000 BDT
   ↓ Payment Status: unpaid
   
2. First Payment Received
   ↓ Payment Amount: 20,000 BDT
   ↓ Balance Before: 100,000 BDT
   ↓ Balance After: 80,000 BDT
   ↓ Payment Status: partial
   ↓ Installment Number: 1
   
3. Second Payment Received
   ↓ Payment Amount: 30,000 BDT
   ↓ Balance Before: 80,000 BDT
   ↓ Balance After: 50,000 BDT
   ↓ Payment Status: partial
   ↓ Installment Number: 2
   
4. Third Payment Received
   ↓ Payment Amount: 25,000 BDT
   ↓ Balance Before: 50,000 BDT
   ↓ Balance After: 25,000 BDT
   ↓ Payment Status: partial
   ↓ Installment Number: 3
   
5. Final Payment Received
   ↓ Payment Amount: 25,000 BDT
   ↓ Balance Before: 25,000 BDT
   ↓ Balance After: 0 BDT
   ↓ Payment Status: paid (fully paid)
   ↓ Installment Number: 4
```

### Data Integrity Notes

- **Flexible payments:** No fixed installment amounts - customer can pay any amount anytime
- **No fixed dates:** Not tied to specific due dates (unless manually set in next_payment_due)
- **Atomic tracking:** Each payment is a separate record with full audit trail
- **Balance tracking:** order_balance_before and order_balance_after calculated automatically
- **Installment numbering:** Auto-incremented for each payment (1, 2, 3, etc.)
- **Payment history:** Full history preserved in order_payments table
- **Order status:** Automatically updates from unpaid → partial → paid based on payments
- **Customer flexibility:** Implemented per PM's March 3, 2026 request for flexible payment system

### Relation to Previous Work

This report directly utilizes the **Flexible Payment Edit & Void** features implemented on March 3, 2026:
- Payments can be edited (amount, date, notes) with audit trail
- Payments can be voided with reason tracking
- Edit history stored in metadata
- All payment modifications reflected in this report
- Balance recalculation happens automatically

### Future Enhancements (Not Implemented)

- SMS/Email reminders: Auto-send payment reminders based on next_payment_due
- Payment plan templates: Predefined installment schedules (3-month, 6-month, 12-month)
- Interest calculation: Add interest for late payments based on days_late
- Auto-payment: Integration with payment gateways for recurring charges
- Credit scoring: Calculate customer creditworthiness based on payment history
- Installment modification: Allow changing payment schedule after order creation
- Guarantee tracking: Link guarantor information for high-value installments

---

## Status

- ✅ **Category Sales Report:** FIXED (subtotal, discount, VAT calculations corrected)
- ✅ **Purchase Order Detail CSV:** IMPLEMENTED (needs testing)
- ✅ **Purchase Order Barcodes CSV:** IMPLEMENTED (needs testing)
- ✅ **Dispatch Barcode Breakdown CSV:** IMPLEMENTED (needs testing)
- ✅ **Customer Installment/Partial Payment Report:** IMPLEMENTED (needs testing)
- ⏳ **Additional Reports:** Awaiting PM requirements

---

*More CSV reports will be appended to this document as they are implemented.*
