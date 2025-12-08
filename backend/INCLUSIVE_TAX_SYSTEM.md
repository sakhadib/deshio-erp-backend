# Inclusive Tax System Documentation

## Overview
The ERP system has been updated to use **inclusive tax pricing** where the tax is embedded within the product's selling price, rather than added on top.

## Inclusive vs Exclusive Tax

### Previous System (EXCLUSIVE)
```
Cost Price: 800 BDT
Sell Price: 1000 BDT
Tax: 2% → 20 BDT
Customer Pays: 1020 BDT
```

### New System (INCLUSIVE) ✅
```
Cost Price: 800 BDT
Sell Price: 1000 BDT (includes 2% tax)
Base Price: 980.39 BDT
Tax Amount: 19.61 BDT
Customer Pays: 1000 BDT (tax included)
```

## Database Changes

### Product Batches Table
Added three new fields to `product_batches`:

| Field | Type | Description |
|-------|------|-------------|
| `tax_percentage` | decimal(5,2) | Tax rate for this batch (e.g., 2.00 for 2%) |
| `base_price` | decimal(10,2) | Price excluding tax (auto-calculated) |
| `tax_amount` | decimal(10,2) | Tax amount per unit (auto-calculated) |

### Calculations
The system automatically calculates when creating/updating batches:

```php
base_price = sell_price / (1 + (tax_percentage / 100))
tax_amount = sell_price - base_price
```

**Example with 2% tax on 1000 BDT:**
- `base_price = 1000 / (1 + 0.02) = 980.39 BDT`
- `tax_amount = 1000 - 980.39 = 19.61 BDT`

## Creating Product Batches

### API Request
```json
POST /api/batches
{
  "product_id": 1,
  "store_id": 1,
  "quantity": 100,
  "cost_price": 800,
  "sell_price": 1000,
  "tax_percentage": 2.0
}
```

### What Happens Automatically
1. System calculates `base_price = 980.39`
2. System calculates `tax_amount = 19.61`
3. Both values stored in database
4. Customer pays 1000 BDT (inclusive)

## Order Processing

### All Checkout Types
The system extracts tax from the inclusive price in all checkout scenarios:

1. **Counter Sales** (`OrderController`)
   - Reads `tax_percentage` from batch
   - Calculates tax per item from inclusive `unit_price`

2. **Guest Checkout** (`GuestCheckoutController`)
   - Extracts tax from product prices
   - Uses batch-specific tax rates

3. **E-commerce Checkout** (`EcommerceOrderController`)
   - Calculates tax from cart item prices
   - Applies per-batch tax rates

### Order Item Calculation
```php
// For each item:
unit_price = 1000 BDT (from batch sell_price)
quantity = 5
tax_percentage = 2% (from batch)

// Calculate base price per unit
base_price = 1000 / 1.02 = 980.39 BDT
tax_per_unit = 1000 - 980.39 = 19.61 BDT

// Item totals
item_subtotal = 1000 × 5 = 5000 BDT (includes tax)
tax_amount = 19.61 × 5 = 98.05 BDT
```

### Order Total Calculation
```php
subtotal = sum of item totals (tax included)
tax_amount = sum of extracted tax from items
discount = order discount
shipping = delivery charge

// Tax already in subtotal, don't add again
total_amount = subtotal - discount + shipping
```

## Financial Impact

### Profit Margin Calculation
Updated to use `base_price` (excluding tax) for accurate profit:

```php
// OLD: Used sell_price (wrong)
profit_margin = ((sell_price - cost_price) / cost_price) × 100

// NEW: Uses base_price (correct)
profit_margin = ((base_price - cost_price) / cost_price) × 100
```

**Example:**
- Cost: 800 BDT
- Sell: 1000 BDT (2% tax)
- Base: 980.39 BDT
- **Correct Margin:** (980.39 - 800) / 800 = 22.55%
- **Wrong (old):** (1000 - 800) / 800 = 25% ❌

### Accounting Impact
- **Revenue:** Base price amount (excluding tax)
- **Tax Liability:** Extracted tax amount
- **COGS:** Cost price × quantity
- **Gross Profit:** Revenue - COGS

## Code Changes Summary

### 1. Migration
`2025_12_07_094939_add_tax_fields_to_product_batches_table.php`
- Added `tax_percentage`, `base_price`, `tax_amount` fields

### 2. Model Updates
**`ProductBatch.php`:**
- Added fields to `$fillable` and `$casts`
- Added `boot()` hooks to auto-calculate base_price and tax_amount
- Updated `calculateProfitMargin()` to use base_price

### 3. Controller Updates
**`ProductBatchController.php`:**
- Added `tax_percentage` validation
- Accepts tax_percentage in create/update requests

**`OrderController.php`:**
- Extract tax from inclusive unit_price using batch tax_percentage
- Updated total calculation (don't add tax)

**`GuestCheckoutController.php`:**
- Removed hardcoded 5% tax
- Extract tax per item from inclusive prices
- Updated total calculation

**`EcommerceOrderController.php`:**
- Removed hardcoded 5% tax
- Calculate tax per cart item using batch data
- Updated total calculation

**`Order.php` (Model):**
- Updated `calculateTotals()` method
- Tax already in subtotal, don't add again

## Testing Scenarios

### Scenario 1: Single Item with 2% Tax
```
Batch: sell_price=1000, tax_percentage=2
Expected Results:
- base_price: 980.39 BDT
- tax_amount: 19.61 BDT
- Customer pays: 1000 BDT

Order (qty 1):
- item total: 1000 BDT
- tax extracted: 19.61 BDT
- order total: 1000 BDT
```

### Scenario 2: Multiple Items, Different Tax Rates
```
Item A: 1000 BDT @ 2% tax = 19.61 BDT tax
Item B: 500 BDT @ 5% tax = 23.81 BDT tax

Order:
- subtotal: 1500 BDT (includes tax)
- tax_amount: 43.42 BDT
- total: 1500 BDT
```

### Scenario 3: With Discount and Shipping
```
Item: 1000 BDT @ 2% tax (qty 5)
Discount: 200 BDT
Shipping: 100 BDT

Order:
- subtotal: 5000 BDT (tax included)
- tax_amount: 98.05 BDT
- discount: 200 BDT
- shipping: 100 BDT
- total: 4900 BDT
```

## Migration Notes

### Existing Batches
- All existing batches will have `tax_percentage = 0`
- Their `base_price` will equal `sell_price`
- No tax will be extracted from old batches
- **Action Required:** Update existing batches with appropriate tax percentages

### Backward Compatibility
- Orders created before this update remain unchanged
- New orders use inclusive tax calculation
- Mixed orders (old + new batches) handle correctly

## Best Practices

### Setting Tax Percentage
1. Determine the tax rate for your product category
2. Enter the **rate** not the amount (e.g., 2 for 2%)
3. System calculates base_price and tax_amount automatically

### Pricing Strategy
1. Set `sell_price` to what customer pays (inclusive)
2. System extracts tax automatically
3. Profit margins calculated on base_price (excluding tax)

### Reporting
- Use `base_price` for revenue reporting (excluding tax)
- Use `tax_amount` for tax liability reporting
- Sum order `tax_amount` fields for total tax collected

## API Response Examples

### Batch Creation Response
```json
{
  "success": true,
  "batch": {
    "id": 1,
    "product_id": 1,
    "quantity": 100,
    "cost_price": "800.00",
    "sell_price": "1000.00",
    "tax_percentage": "2.00",
    "base_price": "980.39",
    "tax_amount": "19.61",
    "created_at": "2025-12-07T10:00:00Z"
  }
}
```

### Order Response
```json
{
  "order": {
    "id": 1,
    "order_number": "ORD-001",
    "subtotal": "5000.00",
    "tax_amount": "98.05",
    "discount_amount": "200.00",
    "shipping_amount": "100.00",
    "total_amount": "4900.00",
    "items": [
      {
        "product_name": "Product A",
        "quantity": 5,
        "unit_price": "1000.00",
        "tax_amount": "98.05",
        "total_amount": "5000.00"
      }
    ]
  }
}
```

## Troubleshooting

### Issue: Tax calculated twice
**Cause:** Adding tax_amount to subtotal in total calculation
**Fix:** Total = subtotal - discount + shipping (tax already in subtotal)

### Issue: Wrong profit margins
**Cause:** Using sell_price instead of base_price
**Fix:** Use base_price for profit calculations

### Issue: Existing batches showing zero tax
**Cause:** Old batches have tax_percentage = 0
**Fix:** Update batches with correct tax_percentage via batch update API

## Formula Reference

### Basic Calculations
```
Given: sell_price (inclusive), tax_percentage

base_price = sell_price / (1 + (tax_percentage / 100))
tax_amount = sell_price - base_price

Example (2% tax on 1000):
base_price = 1000 / 1.02 = 980.39
tax_amount = 1000 - 980.39 = 19.61
```

### Reverse Calculation
```
Given: base_price (exclusive), tax_percentage

sell_price = base_price × (1 + (tax_percentage / 100))
tax_amount = sell_price - base_price

Example (2% tax on base 980.39):
sell_price = 980.39 × 1.02 = 1000
tax_amount = 1000 - 980.39 = 19.61
```

## Summary
✅ Tax is now **included** in the selling price
✅ Customers pay the `sell_price` (not sell_price + tax)
✅ System automatically calculates base_price and tax_amount
✅ All checkout types handle inclusive tax correctly
✅ Profit margins calculated accurately using base_price
✅ Accounting reports show revenue vs tax separately
