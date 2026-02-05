# Purchase Order PDF Reports API

## Overview

Two PDF report endpoints for Purchase Orders:
1. **Individual PO PDF** - Single purchase order details
2. **Summary Report PDF** - Multiple POs with filters and aggregations

---

## Endpoints

### 1. Individual Purchase Order PDF

**Endpoint:** `GET /api/purchase-orders/{id}/pdf`

**Description:** Download/view PDF of a single purchase order with full details

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `inline` | boolean | false | If true, opens in browser. If false, downloads |

**Example Requests:**
```
# Download PDF
GET /api/purchase-orders/123/pdf

# View in browser
GET /api/purchase-orders/123/pdf?inline=true
```

**Response:** PDF file download or stream

**PDF Contains:**
- Company header
- PO number and status badge
- Vendor information
- Order date and expected delivery
- Items table (product, SKU, qty, unit cost, total)
- Subtotal, tax, discount, shipping breakdown
- Payment status section
- Notes and terms & conditions
- Signature areas

---

### 2. Summary Report PDF

**Endpoint:** `GET /api/purchase-orders/report/pdf`

**Description:** Download/view summary report of multiple POs with filters

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `from_date` | date (YYYY-MM-DD) | No | Start date filter |
| `to_date` | date (YYYY-MM-DD) | No | End date filter |
| `vendor_id` | integer | No | Filter by vendor |
| `store_id` | integer | No | Filter by store/warehouse |
| `status` | string | No | Filter by PO status |
| `payment_status` | string | No | Filter by payment status |
| `inline` | boolean | No | View in browser if true |

**Status Values:**
- `draft`
- `approved`
- `partially_received`
- `received`
- `cancelled`

**Payment Status Values:**
- `unpaid`
- `partial`
- `paid`

**Example Requests:**
```
# All POs this month
GET /api/purchase-orders/report/pdf?from_date=2026-02-01&to_date=2026-02-28

# Specific vendor, pending payment
GET /api/purchase-orders/report/pdf?vendor_id=5&payment_status=unpaid

# View in browser with all filters
GET /api/purchase-orders/report/pdf?from_date=2026-01-01&to_date=2026-02-28&status=approved&inline=true
```

**Response:** PDF file download or stream (Landscape A4)

**PDF Contains:**
- Report header with generation timestamp
- Applied filters display
- Summary boxes: Total Orders, Total Amount, Paid, Outstanding, Items
- PO list table with:
  - PO Number
  - Date
  - Vendor name
  - Store name
  - Status (color badge)
  - Payment status
  - Item count
  - Total, Paid, Due amounts
- Grand totals row
- Status breakdown section
- Top vendors by purchase amount

---

## Frontend Integration

### Print Button for Individual PO

```jsx
// React example
const handlePrintPO = (poId) => {
  // Open PDF in new tab for printing
  window.open(`/api/purchase-orders/${poId}/pdf?inline=true`, '_blank');
};

// Or download
const handleDownloadPO = (poId) => {
  window.location.href = `/api/purchase-orders/${poId}/pdf`;
};
```

### Summary Report with Filter Form

```jsx
const [filters, setFilters] = useState({
  from_date: '',
  to_date: '',
  vendor_id: '',
  status: '',
  payment_status: ''
});

const generateReport = () => {
  const params = new URLSearchParams();
  
  Object.entries(filters).forEach(([key, value]) => {
    if (value) params.append(key, value);
  });
  
  // Open in new tab
  window.open(`/api/purchase-orders/report/pdf?${params.toString()}&inline=true`, '_blank');
};
```

### Direct Axios Download

```javascript
import axios from 'axios';

const downloadPdf = async (poId) => {
  const response = await axios.get(`/api/purchase-orders/${poId}/pdf`, {
    responseType: 'blob'
  });
  
  const url = window.URL.createObjectURL(new Blob([response.data]));
  const link = document.createElement('a');
  link.href = url;
  link.setAttribute('download', `PO-${poId}.pdf`);
  document.body.appendChild(link);
  link.click();
  link.remove();
};
```

---

## UI/UX Suggestions

### Individual PO Page
Add a "Print" or "Download PDF" button:
```
[📄 Download PDF]  [🖨️ Print]
```

### PO List Page
Add a report button in the toolbar:
```
[📊 Generate Report] → Opens filter modal → Download/Preview
```

### Filter Modal Example
```
┌─────────────────────────────────────────┐
│  Generate Purchase Orders Report        │
├─────────────────────────────────────────┤
│  Date Range:                            │
│  [From: ________] [To: ________]        │
│                                         │
│  Vendor: [Select vendor      ▼]         │
│  Store:  [Select store       ▼]         │
│  Status: [Select status      ▼]         │
│  Payment: [Select payment    ▼]         │
│                                         │
│  [Cancel]  [Preview]  [Download PDF]    │
└─────────────────────────────────────────┘
```

---

## Deployment Notes

### PM Checklist
1. **Install dompdf package:**
   ```bash
   composer update
   ```

2. **Publish config (optional):**
   ```bash
   php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"
   ```

3. **Clear cache:**
   ```bash
   php artisan config:clear
   php artisan view:clear
   ```

### PDF Files Created
- `resources/views/pdf/purchase-order.blade.php` - Individual PO template
- `resources/views/pdf/purchase-orders-summary.blade.php` - Summary report template

### Dependencies Added
- `barryvdh/laravel-dompdf: ^2.0` in composer.json

---

## Error Handling

| Error | Response |
|-------|----------|
| PO not found | 404 - Purchase order not found |
| Invalid date range | 422 - to_date must be after from_date |
| Invalid status | 422 - The selected status is invalid |

---

## PDF Customization

To modify PDF appearance, edit the Blade templates:
- Individual: `resources/views/pdf/purchase-order.blade.php`
- Summary: `resources/views/pdf/purchase-orders-summary.blade.php`

**Note:** dompdf uses a subset of CSS. Supported features:
- Basic layouts (tables recommended over flexbox)
- Colors, fonts, borders
- Page breaks: `page-break-before: always`
- Not supported: CSS Grid, Flexbox (limited)
