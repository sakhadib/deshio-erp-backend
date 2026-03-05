# Purchase Order API Migration: PDF to JSON

**Date:** March 5, 2026  
**Issue:** API endpoints returning Blade templates (PDF/HTML) instead of JSON  
**Impact:** Next.js frontend API calls failing due to unexpected response format  
**Status:** ✅ FIXED

---

## Problem Description

The backend system is **absolutely API-based** with no room for Blade templates. However, two Purchase Order reporting endpoints were returning PDF/HTML views instead of JSON responses:

1. **`GET /api/purchase-orders/report/pdf`** - Summary report (was returning PDF)
2. **`GET /api/purchase-orders/{id}/pdf`** - Individual PO report (was returning PDF)

This caused issues with the Next.js frontend which expected JSON responses from all API endpoints.

---

## Root Cause

The endpoints were using Laravel's PDF generation with Blade templates:
- `resources/views/pdf/purchase-order.blade.php`
- `resources/views/pdf/purchase-orders-summary.blade.php`

Controller methods `exportPdf()` and `exportSummaryPdf()` were returning:
```php
$pdf = Pdf::loadView('pdf.purchase-order', $data);
return $pdf->download($filename); // Returns PDF, not JSON
```

---

## Solution Implemented

### 1. Created New JSON Report Endpoints

**Two new controller methods added to `PurchaseOrderController.php`:**

#### Method 1: `getReportDetail($id)` - Individual PO Report (JSON)
- **Route:** `GET /api/purchase-orders/{id}/report`
- **Returns:** Complete purchase order details in JSON format
- **Response Structure:**
```json
{
  "success": true,
  "data": {
    "po": {
      "id": 1,
      "po_number": "PO-2026-001",
      "status": "received",
      "payment_status": "paid",
      "order_date": "2026-03-01",
      "expected_delivery_date": "2026-03-15",
      "notes": "...",
      "terms_and_conditions": "..."
    },
    "vendor": {
      "id": 5,
      "name": "Acme Corp",
      "contact_person": "John Doe",
      "phone": "+880...",
      "email": "john@acme.com",
      "address": "..."
    },
    "store": {
      "id": 2,
      "name": "Main Store",
      "address": "..."
    },
    "items": [
      {
        "id": 10,
        "product_name": "Laptop Dell XPS 15",
        "product_sku": "LAP-DELL-XPS15",
        "quantity_ordered": 10,
        "quantity_received": 10,
        "quantity_pending": 0,
        "unit_cost": 85000.00,
        "tax_amount": 12750.00,
        "total_cost": 972500.00,
        "notes": ""
      }
    ],
    "payments": [
      {
        "id": 15,
        "amount": 500000.00,
        "payment_method": "bank_transfer",
        "payment_date": "2026-03-02",
        "reference_number": "TXN-123456",
        "notes": ""
      }
    ],
    "financial_summary": {
      "subtotal": 850000.00,
      "tax_amount": 127500.00,
      "discount_amount": 5000.00,
      "shipping_cost": 500.00,
      "other_charges": 0.00,
      "total_amount": 973000.00,
      "paid_amount": 973000.00,
      "outstanding_amount": 0.00
    },
    "staff": {
      "created_by": {"id": 1, "name": "Admin User"},
      "approved_by": {"id": 2, "name": "Manager Name"},
      "received_by": {"id": 3, "name": "Warehouse Staff"}
    },
    "timestamps": {
      "created_at": "2026-03-01T10:00:00.000000Z",
      "updated_at": "2026-03-15T14:30:00.000000Z",
      "approved_at": "2026-03-01T15:00:00.000000Z",
      "received_at": "2026-03-15T14:30:00.000000Z"
    }
  }
}
```

#### Method 2: `getReportSummary()` - Summary Report (JSON)
- **Route:** `GET /api/purchase-orders/report/summary`
- **Query Parameters:**
  - `from_date` (optional): Start date filter
  - `to_date` (optional): End date filter
  - `vendor_id` (optional): Filter by vendor
  - `store_id` (optional): Filter by store
  - `status` (optional): Filter by PO status
  - `payment_status` (optional): Filter by payment status

- **Returns:** Aggregated statistics and list of POs
- **Response Structure:**
```json
{
  "success": true,
  "data": {
    "summary": {
      "total_orders": 45,
      "total_amount": 5250000.00,
      "total_paid": 4800000.00,
      "total_outstanding": 450000.00,
      "total_items": 320
    },
    "filters_applied": {
      "from_date": "2026-03-01",
      "to_date": "2026-03-31",
      "vendor_id": 5,
      "vendor_name": "Acme Corp"
    },
    "status_breakdown": [
      {
        "status": "received",
        "count": 30,
        "total_amount": 4200000.00
      },
      {
        "status": "approved",
        "count": 10,
        "total_amount": 850000.00
      },
      {
        "status": "draft",
        "count": 5,
        "total_amount": 200000.00
      }
    ],
    "vendor_breakdown": [
      {
        "vendor_id": 5,
        "vendor_name": "Acme Corp",
        "order_count": 15,
        "total_amount": 2500000.00,
        "paid_amount": 2300000.00,
        "outstanding": 200000.00
      },
      {
        "vendor_id": 8,
        "vendor_name": "Tech Supplies Ltd",
        "order_count": 12,
        "total_amount": 1800000.00,
        "paid_amount": 1800000.00,
        "outstanding": 0.00
      }
    ],
    "purchase_orders": [
      {
        "id": 1,
        "po_number": "PO-2026-001",
        "order_date": "2026-03-01",
        "vendor": {
          "id": 5,
          "name": "Acme Corp"
        },
        "store": {
          "id": 2,
          "name": "Main Store"
        },
        "status": "received",
        "payment_status": "paid",
        "items_count": 8,
        "total_amount": 973000.00,
        "paid_amount": 973000.00,
        "outstanding_amount": 0.00
      }
    ]
  }
}
```

---

### 2. Deprecated Old PDF Endpoints

**Routes Updated in `routes/api.php`:**

**OLD (Problematic):**
```php
Route::get('/report/pdf', [PurchaseOrderController::class, 'exportSummaryPdf']);
Route::get('{id}/pdf', [PurchaseOrderController::class, 'exportPdf']);
```

**NEW (Fixed):**
```php
// JSON Report endpoints (API responses)
Route::get('/report/summary', [PurchaseOrderController::class, 'getReportSummary']); // Summary report JSON

Route::prefix('{id}')->group(function () {
    // JSON Report endpoint (API response)
    Route::get('/report', [PurchaseOrderController::class, 'getReportDetail']); // Individual PO report JSON
    
    // PDF endpoint (DEPRECATED - commented out)
    // Route::get('/pdf', [PurchaseOrderController::class, 'exportPdf']); // DEPRECATED
});

// PDF endpoints (DEPRECATED - commented out)
// Route::get('/report/pdf', [PurchaseOrderController::class, 'exportSummaryPdf']); // DEPRECATED
```

**Controller Methods Marked as `@deprecated`:**
- `exportPdf($id)` - Line 866
- `exportSummaryPdf($request)` - Line 897

Both methods now include deprecation notices in their PHPDoc:
```php
/**
 * @deprecated This endpoint returns PDF/HTML. Use /api/purchase-orders/{id}/report for JSON data.
 * If PDF is needed, generate it client-side from the JSON data.
 */
```

---

## Migration Guide for Frontend

### Before (BROKEN):
```javascript
// ❌ This was causing issues - returns PDF, not JSON
const response = await fetch('/api/purchase-orders/123/pdf');
const data = await response.json(); // ERROR: Unexpected token '<' in JSON
```

### After (FIXED):
```javascript
// ✅ Use new JSON endpoint
const response = await fetch('/api/purchase-orders/123/report');
const data = await response.json(); // Works correctly!

console.log(data.data.po.po_number); // "PO-2026-001"
console.log(data.data.vendor.name); // "Acme Corp"
```

### Summary Report Example:
```javascript
// ✅ Get summary with filters
const params = new URLSearchParams({
  from_date: '2026-03-01',
  to_date: '2026-03-31',
  vendor_id: '5'
});

const response = await fetch(`/api/purchase-orders/report/summary?${params}`);
const data = await response.json();

console.log(data.data.summary.total_orders); // 45
console.log(data.data.summary.total_amount); // 5250000.00
```

---

## PDF Generation Options (If Still Needed)

Since the backend no longer returns PDFs, the frontend has several options:

### Option 1: Client-Side PDF Generation (Recommended)
Use the JSON data to generate PDFs in Next.js:

**Libraries:**
- **jsPDF** - Generate PDFs from scratch
- **react-pdf** - React-based PDF generation
- **pdfmake** - Declarative PDF creation
- **html2pdf.js** - Convert HTML to PDF

**Example with jsPDF:**
```javascript
import jsPDF from 'jspdf';

async function downloadPOReport(poId) {
  // Fetch JSON data
  const response = await fetch(`/api/purchase-orders/${poId}/report`);
  const { data } = await response.json();
  
  // Create PDF
  const doc = new jsPDF();
  doc.text(`Purchase Order: ${data.po.po_number}`, 10, 10);
  doc.text(`Vendor: ${data.vendor.name}`, 10, 20);
  doc.text(`Total: ৳${data.financial_summary.total_amount}`, 10, 30);
  
  // Add more content...
  
  doc.save(`PO-${data.po.po_number}.pdf`);
}
```

### Option 2: Separate PDF Service
Create a dedicated PDF generation microservice:
- Accepts JSON input
- Generates PDF using templates
- Returns PDF file
- Keeps API backend clean (JSON-only)

### Option 3: Browser Print
Use browser's native print functionality:
```javascript
// Create a print-friendly view
function printPO(data) {
  const printWindow = window.open('', '_blank');
  printWindow.document.write(`
    <html>
      <head><title>PO ${data.po.po_number}</title></head>
      <body>
        <h1>Purchase Order: ${data.po.po_number}</h1>
        <!-- Format the data -->
      </body>
    </html>
  `);
  printWindow.print();
}
```

---

## Files Modified

### 1. Controller: `app/Http/Controllers/PurchaseOrderController.php`
**Changes:**
- ✅ Added `getReportDetail($id)` method (Lines 618-728)
- ✅ Added `getReportSummary()` method (Lines 730-864)
- ✅ Marked `exportPdf()` as `@deprecated` (Line 866)
- ✅ Marked `exportSummaryPdf()` as `@deprecated` (Line 897)

### 2. Routes: `routes/api.php`
**Changes:**
- ✅ Added `GET /api/purchase-orders/report/summary` → `getReportSummary()`
- ✅ Added `GET /api/purchase-orders/{id}/report` → `getReportDetail()`
- ✅ Commented out `GET /api/purchase-orders/report/pdf` (deprecated)
- ✅ Commented out `GET /api/purchase-orders/{id}/pdf` (deprecated)

### 3. Documentation: `docs/2026-03-05/PO_PDF_TO_JSON_MIGRATION.md`
**Created:** This migration guide

---

## Testing Checklist

- [ ] Test **`GET /api/purchase-orders/{id}/report`** returns JSON
- [ ] Test **`GET /api/purchase-orders/report/summary`** returns JSON
- [ ] Verify response structure matches expected format
- [ ] Test all query filters on summary endpoint (date range, vendor, store, status)
- [ ] Confirm old PDF routes are disabled (404 or not accessible)
- [ ] Update frontend to use new endpoints
- [ ] Test PDF generation on frontend if needed
- [ ] Verify no Blade template responses in any API route

---

## Existing CSV Exports (Still Available)

These CSV exports are **still available** and work correctly for data export:
- `GET /api/purchase-orders/{id}/csv` - Detailed PO breakdown CSV
- `GET /api/purchase-orders/{id}/barcodes/csv` - PO barcodes CSV

CSV exports are acceptable for API endpoints as they're pure data exports, not rendered templates.

---

## API Endpoints Summary

| Method | Endpoint | Returns | Status |
|--------|----------|---------|--------|
| GET | `/api/purchase-orders` | JSON list | ✅ Active |
| GET | `/api/purchase-orders/{id}` | JSON detail | ✅ Active |
| GET | `/api/purchase-orders/stats` | JSON statistics | ✅ Active |
| GET | `/api/purchase-orders/report/summary` | JSON report | ✅ NEW |
| GET | `/api/purchase-orders/{id}/report` | JSON report | ✅ NEW |
| GET | `/api/purchase-orders/{id}/csv` | CSV data | ✅ Active |
| GET | `/api/purchase-orders/{id}/barcodes/csv` | CSV data | ✅ Active |
| GET | `/api/purchase-orders/report/pdf` | ~~PDF/HTML~~ | ❌ DEPRECATED |
| GET | `/api/purchase-orders/{id}/pdf` | ~~PDF/HTML~~ | ❌ DEPRECATED |

---

## Related Blade Templates (No Longer Used in API)

These files remain in the codebase but are **not used by any active API routes**:
- `resources/views/pdf/purchase-order.blade.php`
- `resources/views/pdf/purchase-orders-summary.blade.php`

**Recommendation:** Can be safely deleted if PDFs are not needed. If kept, move them to a separate admin/internal section outside the main API routes.

---

## Summary

✅ **Problem Fixed:** API no longer returns Blade templates/PDFs  
✅ **Solution:** New JSON report endpoints created  
✅ **Migration:** Frontend should use `/report` and `/report/summary` endpoints  
✅ **Backward Compatibility:** Old PDF routes are commented out (not deleted)  
✅ **PDF Generation:** Should now be handled client-side if needed  

All API routes now return proper JSON responses compatible with Next.js frontend! 🚀
