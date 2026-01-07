# Reporting API

## Category Sales CSV Export

**Endpoint**: `GET /api/reporting/csv/category-sales`

Downloads a CSV report of sales grouped by product category with comprehensive financial breakdown.

**Query Parameters**:
- `date_from` (optional): Start date filter (YYYY-MM-DD)
- `date_to` (optional): End date filter (YYYY-MM-DD)
- `store_id` (optional): Filter by specific store
- `status` (optional): Order status filter (e.g., `completed`, `confirmed`, `pending`) - if omitted, includes all statuses

**CSV Columns**:
1. **Category** - Product category name
2. **Sold Qty** - Total quantity sold
3. **SUB Total** - Gross sales (quantity × unit price)
4. **Discount Amount** - Total discounts applied
5. **Exchange Amount** - Value of exchanged products
6. **Return Amount** - Value of returned products
7. **Net Sales (without VAT)** - Subtotal minus discounts, returns, and exchanges
8. **VAT Amount (7.5)** - Tax amount at 7.5%
9. **Net Amount** - Final amount including VAT

**Response**: CSV file download (`category-sales-report-{timestamp}.csv`)

**Example**: `GET /api/reporting/csv/category-sales?date_from=2025-01-01&date_to=2025-12-31` (all statuses)

---

## Sales CSV Export

**Endpoint**: `GET /api/reporting/csv/sales`

Downloads a detailed CSV report with order-level sales data including customer, product, delivery, and payment information.

**Query Parameters**:
- `date_from` (optional): Start date filter (YYYY-MM-DD)
- `date_to` (optional): End date filter (YYYY-MM-DD)
- `store_id` (optional): Filter by specific store
- `status` (optional): Order status filter (e.g., `confirmed`, `pending_assignment`)
- `customer_id` (optional): Filter by customer

**CSV Columns**: 19 columns with comprehensive order details (Creation Date, Invoice Number, Customer Name, Customer Phone, Customer Address, Product Name And QTY, Product Specification, Product Attribute, Sub Total Price, Discount, Price After Discount, Delivery Charge, Total Price, Paid Amount, Due Amount, Delivery Partner, Delivery Area, Payment Method, Order Status)

**Response**: CSV file download (`sales-report-{timestamp}.csv`)

---

## Stock CSV Export

**Endpoint**: `GET /api/reporting/csv/stock`

Downloads a detailed CSV report of product stock levels with sold quantities and stock values. Data is organized by product batches since pricing is attached to batches.

**Query Parameters**:
- `store_id` (optional): Filter by specific store
- `category_id` (optional): Filter by product category
- `product_id` (optional): Filter by specific product
- `include_inactive` (optional): Include inactive batches (default: false)

**CSV Columns**: Category, Product Code (SKU), Product Name, Product Brand, Product Description, Batch Number, Sold Quantity, Sub Total (sales revenue), Remaining Stock Quantity, Stock Volume (value of remaining stock)

**Response**: CSV file download (`stock-report-{timestamp}.csv`)

**Example**: `GET /api/reporting/csv/category-sales?status=confirmed` (only confirmed orders)

---

## Booking CSV Export

**Endpoint**: `GET /api/reporting/csv/booking`

Downloads a detailed CSV report of order bookings (order items) with customer information, product details including specific barcodes, and pricing from batches.

**Query Parameters**:
- `date_from` (optional): Start date filter (YYYY-MM-DD)
- `date_to` (optional): End date filter (YYYY-MM-DD)
- `store_id` (optional): Filter by specific store
- `status` (optional): Order status filter (e.g., `confirmed`, `pending_assignment`)
- `customer_id` (optional): Filter by customer
- `product_id` (optional): Filter by specific product

**CSV Columns**: Order Number, Order Date, Customer Name, Customer Phone, Customer Code, Product Name, Product Code (SKU), Product Barcode, Batch Number, Quantity, Selling Price (from batch), Cost Price (from batch), Item Subtotal, Payable (Order Total), Paid Amount, Due Amount

**Response**: CSV file download (`booking-report-{timestamp}.csv`)
