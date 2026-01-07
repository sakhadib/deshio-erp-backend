# Tax Mode Configuration

## Overview

The system now supports **two tax calculation modes** to accommodate different business requirements and tax jurisdictions:

1. **Inclusive Tax**: Tax is included in the selling price
2. **Exclusive Tax**: Tax is calculated on top of the selling price

## Configuration

### Environment Variable

Add to your `.env` file:

```env
TAX_MODE=inclusive  # or exclusive
```

### Supported Values

- `inclusive` (default) - Tax is embedded in prices
- `exclusive` - Tax is added on top of prices

## How It Works

### Inclusive Mode (Default)

When selling a product for **$1,100** with **10% tax**:

```
Selling Price: $1,100 (includes tax)
├─ Base Price: $1,000 ($1,100 ÷ 1.10)
└─ Tax Amount: $100 ($1,100 - $1,000)

Customer Pays: $1,100
```

**Use Case**: Retail B2C where displayed prices include tax (common in many countries)

### Exclusive Mode

When selling a product for **$1,000** with **10% tax**:

```
Selling Price: $1,000 (base price)
├─ Base Price: $1,000
└─ Tax Amount: $100 ($1,000 × 10%)

Customer Pays: $1,100 ($1,000 + $100)
```

**Use Case**: B2B sales or jurisdictions where tax is added at checkout

## Implementation Details

### ProductBatch Model

The `ProductBatch` model automatically calculates tax fields based on the configured mode:

```php
// When creating/updating a batch:
$batch = ProductBatch::create([
    'sell_price' => 1000,
    'tax_percentage' => 10,
    // ... other fields
]);

// Inclusive mode:
// base_price = 909.09, tax_amount = 90.91, total = 1000

// Exclusive mode:
// base_price = 1000, tax_amount = 100, total = 1100
```

### Order Calculations

Orders respect the tax mode when calculating totals:

**Inclusive Mode:**
```
Total = Subtotal - Discount + Shipping
(Tax is already in subtotal)
```

**Exclusive Mode:**
```
Total = Subtotal + Tax - Discount + Shipping
(Tax is added separately)
```

### Accounting/Transactions

The accounting system works correctly in both modes:

- Revenue is always recorded **excluding tax**
- Tax is recorded separately in Tax Payable account
- Double-entry bookkeeping remains balanced
- Proportional tax split works for partial payments

## Testing

Run the tax mode test:

```bash
php test_tax_modes.php
```

This will verify:
- ✓ ProductBatch calculations
- ✓ Order total calculations
- ✓ Accounting transactions
- ✓ Double-entry balance
- ✓ Revenue/tax split accuracy

## Migration Guide

### Switching from Inclusive to Exclusive

1. Update `.env`:
   ```env
   TAX_MODE=exclusive
   ```

2. Clear config cache:
   ```bash
   php artisan config:clear
   ```

3. **Important**: Existing product batches retain their calculated values. New batches will use the exclusive mode.

### Switching from Exclusive to Inclusive

Same process as above. The system will automatically adjust calculations for new data.

## Examples

### Example 1: Inclusive Mode

```php
// Product batch with 10% tax
$batch = ProductBatch::create([
    'sell_price' => 1100,  // Price customer sees
    'tax_percentage' => 10
]);

// Calculated:
// base_price = 1000
// tax_amount = 100
// total_price = 1100

// Order with 2 units:
// Subtotal: 2200
// Tax: 200 (extracted from subtotal)
// Total: 2200
// Customer pays: $2,200
```

### Example 2: Exclusive Mode

```php
// Product batch with 10% tax
$batch = ProductBatch::create([
    'sell_price' => 1000,  // Base price
    'tax_percentage' => 10
]);

// Calculated:
// base_price = 1000
// tax_amount = 100
// total_price = 1100

// Order with 2 units:
// Subtotal: 2000
// Tax: 200 (added to subtotal)
// Total: 2200
// Customer pays: $2,200
```

## API Response Changes

Order responses now clearly show the tax mode behavior:

```json
{
  "order": {
    "subtotal": 2000.00,
    "tax_amount": 200.00,
    "total_amount": 2200.00,
    "items": [
      {
        "unit_price": 1000.00,
        "quantity": 2,
        "tax_amount": 200.00,
        "total_amount": 2000.00
      }
    ]
  }
}
```

In **inclusive mode**, `total_amount = subtotal`  
In **exclusive mode**, `total_amount = subtotal + tax_amount`

## Frontend Considerations

The frontend should:

1. **Check the tax mode** via API or config endpoint
2. **Display prices accordingly**:
   - Inclusive: Show "Price (incl. tax)"
   - Exclusive: Show "Price + Tax = Total"
3. **Calculate totals** correctly in cart/checkout

## Files Modified

- `.env.example` - Added TAX_MODE variable
- `config/app.php` - Added tax_mode config
- `app/Models/ProductBatch.php` - Dynamic tax calculation
- `app/Models/Order.php` - Mode-aware total calculation
- `app/Http/Controllers/OrderController.php` - Tax helper method
- `app/Models/Transaction.php` - Already handles both (proportional split)

## Troubleshooting

**Issue**: Prices seem wrong after switching modes

**Solution**: The mode affects **new calculations** only. Existing batches retain their values. Create new batches or update existing ones to recalculate.

**Issue**: Accounting doesn't balance

**Solution**: The accounting system extracts tax proportionally and works in both modes. Ensure you're using the fixed `Transaction::createFromOrderPayment` method.

## Support

For issues or questions, check:
- Test file: `test_tax_modes.php`
- Accounting test: `test_accounting.php`
- Configuration: `config/app.php`
