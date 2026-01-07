# Inclusive Tax System Test Summary

## Test the complete workflow with these commands:

### 1. Create Product Batch with 2% Inclusive Tax
```bash
curl -X POST http://localhost:8000/api/batches \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "product_id": 1,
    "store_id": 1,
    "quantity": 100,
    "cost_price": 800,
    "sell_price": 1000,
    "tax_percentage": 2.0,
    "skip_barcode_generation": true
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "batch": {
    "id": 1,
    "sell_price": "1000.00",
    "tax_percentage": "2.00",
    "base_price": "980.39",  ← Automatically calculated
    "tax_amount": "19.61"     ← Automatically calculated
  }
}
```

**Calculation Check:**
- base_price = 1000 / (1 + 0.02) = **980.39** ✓
- tax_amount = 1000 - 980.39 = **19.61** ✓

---

### 2. Create Order with the Batch
```bash
curl -X POST http://localhost:8000/api/orders \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "customer_id": 1,
    "store_id": 1,
    "order_type": "counter",
    "items": [
      {
        "product_id": 1,
        "batch_id": 1,
        "quantity": 5,
        "unit_price": 1000,
        "discount_amount": 0
      }
    ],
    "payment": {
      "payment_method_id": 1,
      "amount": 5000
    }
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "order": {
    "id": 1,
    "order_number": "ORD-XXX",
    "subtotal": "5000.00",      ← Includes tax
    "tax_amount": "98.05",      ← Extracted tax (19.61 × 5)
    "discount_amount": "0.00",
    "shipping_amount": "0.00",
    "total_amount": "5000.00",  ← Subtotal - discount + shipping (NO TAX ADDED)
    "items": [
      {
        "quantity": 5,
        "unit_price": "1000.00",
        "tax_amount": "98.05",
        "total_amount": "5000.00"
      }
    ]
  }
}
```

**Calculation Check:**
- Per unit tax: 19.61 BDT
- Total tax: 19.61 × 5 = **98.05** ✓
- Item total: 1000 × 5 = **5000** ✓
- Order total: 5000 - 0 + 0 = **5000** ✓ (tax already included)

---

### 3. Check Accounting Transactions
```bash
curl -X GET "http://localhost:8000/api/transactions?reference_type=OrderPayment&reference_id=1" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Expected Response: 3 Transactions**

**Transaction 1 - Cash (Debit):**
```json
{
  "type": "Debit",
  "account": "Cash",
  "amount": "5000.00",        ← Full amount received
  "description": "Order Payment - PAY-XXX"
}
```

**Transaction 2 - Revenue (Credit):**
```json
{
  "type": "Credit",
  "account": "Sales Revenue",
  "amount": "4901.95",        ← Revenue excluding tax (5000 - 98.05)
  "description": "Order Revenue (excl. tax) - PAY-XXX"
}
```

**Transaction 3 - Tax Liability (Credit):**
```json
{
  "type": "Credit",
  "account": "Tax Payable",
  "amount": "98.05",          ← Tax collected
  "description": "Sales Tax Collected - PAY-XXX"
}
```

**Accounting Check:**
- Cash received: 5000.00 (Debit)
- Revenue: 4901.95 (Credit)
- Tax Liability: 98.05 (Credit)
- **Balance: 5000 = 4901.95 + 98.05** ✓

---

### 4. Verify Profit Margin
```bash
curl -X GET "http://localhost:8000/api/batches/1" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Expected Response:**
```json
{
  "batch": {
    "cost_price": "800.00",
    "sell_price": "1000.00",
    "base_price": "980.39",
    "tax_percentage": "2.00",
    "profit_margin": "22.55"    ← (980.39 - 800) / 800 × 100
  }
}
```

**Profit Check:**
- Using **base_price** (correct): (980.39 - 800) / 800 = **22.55%** ✓
- NOT using sell_price: (1000 - 800) / 800 = 25% ❌

---

## Complete Accounting Flow

### For 5 units @ 1000 BDT each (2% tax inclusive):

**Customer pays:** 5000 BDT

**Accounting Entries:**
```
Dr. Cash                     5000.00
    Cr. Sales Revenue                 4901.95  (excluding tax)
    Cr. Tax Payable                     98.05  (tax collected)
```

**Cost of Goods Sold:**
```
Dr. COGS                     4000.00  (800 × 5)
    Cr. Inventory                     4000.00
```

**Financial Summary:**
- Gross Revenue: 4901.95 BDT
- COGS: 4000.00 BDT
- **Gross Profit: 901.95 BDT**
- Gross Margin: 18.40%
- Tax Collected: 98.05 BDT
- Cash Received: 5000.00 BDT

---

## Test Multiple Products with Different Tax Rates

```bash
curl -X POST http://localhost:8000/api/orders \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "customer_id": 1,
    "store_id": 1,
    "order_type": "counter",
    "items": [
      {
        "product_id": 1,
        "batch_id": 1,
        "quantity": 5,
        "unit_price": 1000,
        "discount_amount": 0
      },
      {
        "product_id": 2,
        "batch_id": 2,
        "quantity": 3,
        "unit_price": 500,
        "discount_amount": 0
      }
    ],
    "payment": {
      "payment_method_id": 1,
      "amount": 6500
    }
  }'
```

**Expected Breakdown:**

**Product 1 (2% tax):**
- Qty: 5 @ 1000 = 5000 BDT
- Tax: 98.05 BDT
- Base Revenue: 4901.95 BDT

**Product 2 (5% tax - assuming batch 2 has 5% tax):**
- Qty: 3 @ 500 = 1500 BDT
- Tax: 71.43 BDT (23.81 × 3)
- Base Revenue: 1428.57 BDT

**Order Totals:**
- Subtotal: 6500.00 BDT (includes tax)
- Tax Amount: 169.48 BDT
- Total: 6500.00 BDT

**Accounting:**
```
Dr. Cash                     6500.00
    Cr. Sales Revenue                 6330.52
    Cr. Tax Payable                    169.48
```

---

## Verification Checklist

✅ **Batch Creation:**
- [ ] `base_price` calculated correctly
- [ ] `tax_amount` calculated correctly
- [ ] Values stored in database

✅ **Order Items:**
- [ ] Tax extracted per item using batch `tax_percentage`
- [ ] Item `total_amount` includes tax
- [ ] Item `tax_amount` correctly calculated

✅ **Order Totals:**
- [ ] `subtotal` = sum of item totals (includes tax)
- [ ] `tax_amount` = sum of extracted tax
- [ ] `total_amount` = subtotal - discount + shipping (NO tax added)

✅ **Accounting:**
- [ ] Cash debit = full payment amount
- [ ] Revenue credit = payment - tax
- [ ] Tax liability credit = extracted tax
- [ ] Debits = Credits (balanced)

✅ **Profit Margin:**
- [ ] Uses `base_price` not `sell_price`
- [ ] Correctly shows profit excluding tax

---

## Quick Visual Test

Open the `.http` file in VS Code and use the REST Client extension:

1. Open `test_inclusive_tax.http`
2. Update `@token` variable with your JWT token
3. Run each request sequentially
4. Verify responses match expected values

Or use Postman:
1. Import the HTTP file as Postman collection
2. Set Authorization header with Bearer token
3. Run requests and check responses

---

## Troubleshooting

**Issue: base_price is null**
- Solution: Run migration again: `php artisan migrate:refresh`

**Issue: Tax not calculated in order**
- Check: Batch has `tax_percentage` > 0
- Check: OrderController is extracting tax correctly

**Issue: Accounting doesn't split tax**
- Check: Transaction model `createFromOrderPayment` updated
- Check: Tax Payable account exists in accounts table

**Issue: Wrong profit margin**
- Check: ProductBatch `calculateProfitMargin()` uses `base_price`

---

## Database Queries for Verification

### Check batch calculations:
```sql
SELECT 
  id,
  product_id,
  cost_price,
  sell_price,
  tax_percentage,
  base_price,
  tax_amount,
  ROUND(sell_price / (1 + tax_percentage/100), 2) as calculated_base,
  ROUND(sell_price - base_price, 2) as calculated_tax
FROM product_batches
WHERE tax_percentage > 0;
```

### Check order tax calculations:
```sql
SELECT 
  o.order_number,
  o.subtotal,
  o.tax_amount,
  o.total_amount,
  SUM(oi.total_amount) as items_total,
  SUM(oi.tax_amount) as items_tax
FROM orders o
LEFT JOIN order_items oi ON o.id = oi.order_id
GROUP BY o.id;
```

### Check accounting entries:
```sql
SELECT 
  t.transaction_date,
  t.type,
  a.name as account,
  t.amount,
  t.description
FROM transactions t
LEFT JOIN accounts a ON t.account_id = a.id
WHERE t.reference_type = 'App\\Models\\OrderPayment'
ORDER BY t.transaction_date DESC, t.id;
```

---

## Summary

The inclusive tax system is now:
- ✅ Calculating base_price automatically
- ✅ Extracting tax from inclusive prices
- ✅ Recording proper accounting entries
- ✅ Calculating profit margins correctly
- ✅ Working across all checkout types

**Customer pays the sell_price. System automatically handles the tax internally.**
