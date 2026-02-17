# Payment Breakdown CSV Export API

**Date:** February 17, 2026  
**Feature:** Payment Breakdown Report with Payment Method Categorization  
**Endpoint:** `GET /api/reporting/csv/payment-breakdown`

---

## Overview

Export a detailed payment breakdown report showing how customers paid for orders, categorized by payment method type (Cash, Mobile Banking, Bank Transfer). This report is useful for reconciliation and understanding payment preferences.

**Key Features:**
- Breaks down payments by method type
- Shows customer information and order details
- Distinguishes between POS and Online orders
- Supports multiple filtering options
- Exports only completed payments

---

## Endpoint

**URL:** `GET /api/reporting/csv/payment-breakdown`

**Method:** GET

**Authentication:** Required (JWT Token)

**Permissions:** `reports.view` or `reports.export`

---

## Query Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `date_from` | date | No | - | Start date (YYYY-MM-DD format) |
| `date_to` | date | No | - | End date (YYYY-MM-DD format, must be >= date_from) |
| `today` | boolean | No | false | Get today's orders only (overrides date_from/date_to) |
| `store_id` | integer | No | - | Filter by specific store/branch |
| `order_type` | string | No | - | Filter by order type: `counter`, `ecommerce`, `social_commerce`, `service` |
| `status` | string | No | - | Filter by order status: `pending`, `confirmed`, `completed`, `cancelled`, etc. |

**Parameter Notes:**
- If `today=true`, the `date_from` and `date_to` parameters are ignored
- If no date filters are provided, all orders are included
- Multiple filters can be combined

---

## Response

**Content-Type:** `text/csv`

**File Name:** `payment-breakdown-{YYYY-MM-DD-HHMMSS}.csv`

**CSV Columns (14 total):**

| Column | Data Source | Description |
|--------|-------------|-------------|
| **Date** | `orders.order_date` | Order creation date and time (YYYY-MM-DD HH:MM:SS) |
| **Invoice Number** | `orders.order_number` | System-generated order number |
| **Store/Branch** | `stores.name` | Store/branch name where order was placed |
| **Customer Name** | `customers.name` | Customer name (or "Walk-in Customer" if no customer record) |
| **Customer Phone** | `customers.phone` | Customer phone number |
| **Customer Address** | `shipping_address` or `customers.address` | Full address (street, area, city) |
| **Product Name** | `order_items.product_name` | Concatenated product names with quantities |
| **Quantity** | `SUM(order_items.quantity)` | Total quantity of all items in order |
| **Cash Paid** | `SUM(payments.amount)` | Total paid via Cash (payment_method_id = 1) |
| **Bkash/Mobile Banking Paid** | `SUM(payments.amount)` | Total paid via mobile banking (bKash, Nagad, Rocket, etc.) |
| **Bank Paid** | `SUM(payments.amount)` | Total paid via bank transfer or online banking |
| **Due** | `orders.outstanding_amount` | Remaining unpaid amount |
| **System (Online/POS)** | `orders.order_type` | POS, Online (E-commerce), Online (Social), or Service Order |
| **Order Status** | `orders.status` | Current order status |

---

## Payment Method Categorization

The API categorizes payments into 3 main groups:

### 1. Cash Paid
- Payment Method ID: 1
- Payment Method Type: `cash`
- Includes: Physical cash payments

### 2. Bkash/Mobile Banking Paid
- Payment Method ID: 6
- Payment Method Type: `mobile_banking`
- Includes: bKash, Nagad, Rocket, Upay, and other mobile wallets
- Matched by: method type OR method name containing keywords

### 3. Bank Paid
- Payment Method IDs: 4
- Payment Method Types: `bank_transfer`, `online_banking`
- Includes: Bank transfers, online banking, card payments

**Important:** Only **completed** payments are counted. Pending, failed, or cancelled payments are excluded.

---

## Example Requests

### Get Today's Orders
```bash
GET /api/reporting/csv/payment-breakdown?today=true
```

### Get Orders for Date Range
```bash
GET /api/reporting/csv/payment-breakdown?date_from=2026-02-01&date_to=2026-02-17
```

### Get POS Orders from Specific Store
```bash
GET /api/reporting/csv/payment-breakdown?store_id=1&order_type=counter
```

### Get Completed Online Orders for Last Week
```bash
GET /api/reporting/csv/payment-breakdown?date_from=2026-02-10&date_to=2026-02-17&order_type=ecommerce&status=completed
```

### Get All Orders from Store 2
```bash
GET /api/reporting/csv/payment-breakdown?store_id=2
```

---

## Sample CSV Output

```csv
Date,Invoice Number,Store/Branch,Customer Name,Customer Phone,Customer Address,Product Name,Quantity,Cash Paid,Bkash/Mobile Banking Paid,Bank Paid,Due,System (Online/POS),Order Status
2026-02-17 14:30:00,ORD-2026-001234,Main Store,John Doe,01712345678,"123 Street, Gulshan, Dhaka","T-Shirt (x2), Jeans (x1)",3,1500.00,0.00,0.00,0.00,POS,Completed
2026-02-17 15:45:00,ORD-2026-001235,Branch 1,Jane Smith,01898765432,"456 Road, Banani, Dhaka","Shoes (x1)",1,0.00,3500.00,0.00,500.00,Online (E-commerce),Confirmed
2026-02-17 16:20:00,ORD-2026-001236,Main Store,Walk-in Customer,N/A,N/A,"Watch (x1), Wallet (x1)",2,2000.00,1000.00,0.00,0.00,POS,Completed
2026-02-17 17:10:00,ORD-2026-001237,Branch 2,Ali Rahman,01723456789,"789 Lane, Dhanmondi, Dhaka","Laptop (x1)",1,0.00,0.00,50000.00,15000.00,Online (Social),Pending
```

---

## Data Validation

### Input Validation
- `date_from` and `date_to` must be valid dates in YYYY-MM-DD format
- `date_to` must be greater than or equal to `date_from`
- `store_id` must exist in stores table
- `order_type` must be one of: counter, ecommerce, social_commerce, service

### Response Codes
| Code | Description |
|------|-------------|
| 200 | Success - CSV file download |
| 401 | Unauthorized - Invalid or missing token |
| 403 | Forbidden - User lacks required permissions |
| 422 | Validation Error - Invalid parameters |
| 500 | Server Error - Internal error occurred |

---

## Use Cases

### 1. Daily Cash Reconciliation
**Scenario:** Manager wants to reconcile cash drawer at end of day

**Request:**
```bash
GET /api/reporting/csv/payment-breakdown?today=true&store_id=1&order_type=counter
```

**Result:** CSV showing all POS orders from today with cash amounts

---

### 2. Monthly Mobile Banking Report
**Scenario:** Accountant needs to verify bKash transactions for the month

**Request:**
```bash
GET /api/reporting/csv/payment-breakdown?date_from=2026-02-01&date_to=2026-02-28
```

**Result:** CSV showing all orders with bKash/mobile banking breakdown

---

### 3. Outstanding Dues Follow-up
**Scenario:** Sales team needs list of customers with pending payments

**Request:**
```bash
GET /api/reporting/csv/payment-breakdown?status=confirmed&date_from=2026-02-01
```

**Result:** CSV filtered to confirmed orders, check "Due" column for outstanding amounts

---

### 4. Online vs POS Analysis
**Scenario:** Management wants to compare online and offline sales

**Request 1 (POS):**
```bash
GET /api/reporting/csv/payment-breakdown?order_type=counter&date_from=2026-02-01&date_to=2026-02-17
```

**Request 2 (Online):**
```bash
GET /api/reporting/csv/payment-breakdown?order_type=ecommerce&date_from=2026-02-01&date_to=2026-02-17
```

**Result:** Compare two CSV files for analysis

---

## Technical Notes

### Payment Method Mapping

The system uses the following logic to categorize payments:

```php
foreach ($order->payments as $payment) {
    if ($payment->status !== 'completed') continue;
    
    $amount = $payment->amount;
    $methodType = $payment->paymentMethod->type;
    
    // Cash
    if ($payment->payment_method_id == 1 || $methodType == 'cash') {
        $cashPaid += $amount;
    }
    // Mobile Banking
    elseif ($payment->payment_method_id == 6 || $methodType == 'mobile_banking') {
        $mobileBankingPaid += $amount;
    }
    // Bank Transfer
    elseif ($methodType == 'bank_transfer' || $methodType == 'online_banking') {
        $bankPaid += $amount;
    }
}
```

### Order Type Display Mapping

| Database Value | CSV Display |
|----------------|-------------|
| `counter` | POS |
| `ecommerce` | Online (E-commerce) |
| `social_commerce` | Online (Social) |
| `service` | Service Order |

### Customer Address Priority

1. `orders.shipping_address` (JSON)
   - Combines: street/address_line_1, area/address_line_2, city
2. `customers.address` (fallback)

### Product Display Format

Products are shown as: `Product Name (xQuantity)`

Multiple products separated by commas:
```
T-Shirt (x2), Jeans (x1), Shoes (x3)
```

---

## Database Tables Used

- `orders` - Main order data, totals, outstanding amounts
- `customers` - Customer information
- `stores` - Store/branch names
- `order_items` - Product names and quantities
- `order_payments` - Payment records with amounts
- `payment_methods` - Payment method types (cash, mobile_banking, etc.)

---

## Performance Considerations

- Query uses eager loading (`with()`) to prevent N+1 queries
- Filters applied at database level for efficiency
- Large date ranges may take longer to process
- CSV generation streams data (doesn't load all in memory)
- Consider adding pagination for very large datasets in future

---

## Future Enhancements

Potential additions:
- [ ] Excel format export (XLSX)
- [ ] Email scheduled reports
- [ ] Group by date/customer/store
- [ ] Include refund/return breakdown
- [ ] Add discount amount column
- [ ] Include payment transaction references

---

## Related APIs

- `GET /api/reporting/csv/sales` - Detailed sales report
- `GET /api/reporting/csv/category-sales` - Category-wise sales
- `GET /api/reporting/csv/stock` - Stock report

---

## Support & Troubleshooting

### Common Issues

**Issue:** Empty CSV file  
**Solution:** Check date range and filters, ensure orders exist in that period

**Issue:** Permission denied  
**Solution:** Verify user has `reports.view` or `reports.export` permission

**Issue:** Wrong payment amounts  
**Solution:** Ensure payment status is "completed", pending payments are excluded

**Issue:** Missing customer data  
**Solution:** Walk-in customers show as "Walk-in Customer" with N/A phone/address

---

**Last Updated:** February 17, 2026  
**Version:** 1.0  
**Endpoint:** `/api/reporting/csv/payment-breakdown`
