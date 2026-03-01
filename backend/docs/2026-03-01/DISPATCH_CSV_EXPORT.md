# Product Dispatch CSV Export Report

**Date:** March 1, 2026  
**Feature:** CSV Export for Product Dispatch Reports  
**Endpoint:** `GET /api/dispatches/export-csv`

---

## Overview

This feature allows exporting product dispatch data as a CSV file for reporting, analytics, and record-keeping purposes. The report includes detailed information about dispatched products with barcodes grouped by product.

---

## CSV Columns

The exported CSV contains the following columns:

| Column | Description | Example |
|--------|-------------|---------|
| **Dispatch Number** | Unique dispatch identifier | DISP-2026-000123 |
| **Dispatch Date** | Date and time of dispatch | 2026-03-01 14:30:00 |
| **Source Store** | Store sending the products | Main Warehouse |
| **Destination Store** | Store receiving the products | Retail Store A |
| **Status** | Current dispatch status | In_transit |
| **Product Name** | Base product name | Samsung Galaxy S23 |
| **Category** | Product category | Mobile Phones |
| **Vendor** | Product vendor/supplier | Samsung Bangladesh |
| **Barcode** | Individual barcode string | SG23-001-20260301 |
| **Unit Price** | Selling price per unit | 85000.00 |
| **Quantity** | Number of units (1 per barcode) | 1 |

---

## API Endpoint

### Request

**Method:** `GET`  
**Endpoint:** `/api/dispatches/export-csv`  
**Authentication:** Required (JWT Bearer Token)  
**Permission:** `product_dispatches.view`

#### Query Parameters

All parameters are optional for filtering:

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `dispatch_id` | integer | Filter by specific dispatch ID | `?dispatch_id=123` |
| `status` | string | Filter by dispatch status | `?status=in_transit` |
| `source_store_id` | integer | Filter by source store | `?source_store_id=5` |
| `destination_store_id` | integer | Filter by destination store | `?destination_store_id=8` |
| `date_from` | date | Start date (YYYY-MM-DD) | `?date_from=2026-03-01` |
| `date_to` | date | End date (YYYY-MM-DD) | `?date_to=2026-03-31` |

**Valid Status Values:**
- `draft`
- `pending`
- `approved`
- `in_transit`
- `delivered`
- `cancelled`

#### Example Requests

**Export all dispatches:**
```bash
GET /api/dispatches/export-csv
```

**Export specific dispatch:**
```bash
GET /api/dispatches/export-csv?dispatch_id=123
```

**Export dispatches in transit:**
```bash
GET /api/dispatches/export-csv?status=in_transit
```

**Export dispatches for a date range:**
```bash
GET /api/dispatches/export-csv?date_from=2026-03-01&date_to=2026-03-31
```

**Export from specific source store:**
```bash
GET /api/dispatches/export-csv?source_store_id=5&destination_store_id=8
```

**Complex filter:**
```bash
GET /api/dispatches/export-csv?status=delivered&date_from=2026-02-01&date_to=2026-02-29&source_store_id=1
```

### Response

#### Success Response

**Status Code:** `200 OK`  
**Content-Type:** `text/csv`  
**Content-Disposition:** `attachment; filename="dispatch_report_2026-03-01_143052.csv"`

The response streams a CSV file directly to the browser/client for download.

**Sample CSV Output:**
```csv
Dispatch Number,Dispatch Date,Source Store,Destination Store,Status,Product Name,Category,Vendor,Barcode,Unit Price,Quantity
DISP-2026-000123,2026-03-01 14:30:00,Main Warehouse,Retail Store A,In_transit,Samsung Galaxy S23,Mobile Phones,Samsung Bangladesh,SG23-001-20260301,85000.00,1
DISP-2026-000123,2026-03-01 14:30:00,Main Warehouse,Retail Store A,In_transit,Samsung Galaxy S23,Mobile Phones,Samsung Bangladesh,SG23-002-20260301,85000.00,1
DISP-2026-000123,2026-03-01 14:30:00,Main Warehouse,Retail Store A,In_transit,iPhone 14 Pro,Mobile Phones,Apple Inc,IP14-100-20260301,120000.00,1
DISP-2026-000123,2026-03-01 14:30:00,Main Warehouse,Retail Store A,In_transit,Sony Headphones,Electronics,Sony Corporation,SH-XM5-001,15000.00,1
```

#### Error Responses

**Validation Error (422):**
```json
{
  "success": false,
  "errors": {
    "date_from": ["The date from field must be a valid date."],
    "status": ["The selected status is invalid."]
  }
}
```

**Server Error (500):**
```json
{
  "success": false,
  "message": "Failed to export CSV: Database connection error"
}
```

---

## Data Grouping Logic

### Barcode Grouping

The CSV export groups barcodes by product with the following logic:

1. **One Row Per Barcode:**
   - Each scanned barcode appears as a separate row
   - Shows product details repeated for each barcode
   - Quantity column shows `1` (each barcode = 1 physical unit)

2. **No Barcodes Scanned:**
   - If a dispatch item has no scanned barcodes yet
   - Shows "No barcode scanned" in the Barcode column
   - Quantity shows the total item quantity

**Example:**

```csv
Dispatch Number,Product Name,Barcode,Quantity
DISP-2026-000123,Samsung S23,SG23-001,1
DISP-2026-000123,Samsung S23,SG23-002,1
DISP-2026-000123,Samsung S23,SG23-003,1
DISP-2026-000124,iPhone 14,No barcode scanned,5
```

---

## Use Cases

### 1. Financial Reporting
Generate reports for accounting and finance teams showing:
- Value of goods in transit
- Dispatch history by vendor
- Store-to-store transfer values

### 2. Inventory Reconciliation
Compare dispatched items with:
- Store receiving records
- Physical inventory counts
- Barcode scanning records

### 3. Performance Analytics
Analyze:
- Dispatch frequency by store
- Average dispatch values
- Product movement patterns
- Vendor product distribution

### 4. Compliance & Audit
Maintain records for:
- Regulatory compliance
- Internal audits
- Dispute resolution
- Insurance claims

### 5. Logistics Optimization
Review:
- Transit times between stores
- Popular transfer routes
- Seasonal dispatch patterns

---

## Excel Integration

### Opening in Excel

The CSV file is UTF-8 encoded with BOM (Byte Order Mark) for proper display of special characters in Excel.

**Steps to open:**
1. Download the CSV file
2. Open with Microsoft Excel or Google Sheets
3. All special characters (Bengali, symbols) display correctly

### Excel Formulas Examples

**Calculate total value:**
```excel
=SUM(J2:J1000)
```

**Count items by category:**
```excel
=COUNTIF(G:G,"Mobile Phones")
```

**Get unique products:**
```excel
=UNIQUE(F:F)
```

**Filter by status:**
```excel
=FILTER(A:K, E:E="Delivered")
```

### Pivot Table Suggestions

**Recommended Pivot Tables:**

1. **Dispatch Value by Category:**
   - Rows: Category
   - Values: Sum of Unit Price

2. **Products by Vendor:**
   - Rows: Vendor
   - Columns: Product Name
   - Values: Count of Barcode

3. **Store Transfer Summary:**
   - Rows: Source Store, Destination Store
   - Values: Sum of Quantity

4. **Status Distribution:**
   - Rows: Status
   - Values: Count of Dispatch Number

---

## Implementation Details

### File Location
**Controller:** `app/Http/Controllers/ProductDispatchController.php`  
**Method:** `exportCSV()` (Lines 1368-1540)  
**Route:** `routes/api.php` (Line 1304)

### Database Queries

The export uses eager loading for optimal performance:

```php
ProductDispatch::with([
    'sourceStore',
    'destinationStore',
    'items.batch.product.category',
    'items.batch.product.vendor',
    'items.scannedBarcodes'
])
```

### Performance Considerations

- **Eager Loading:** Prevents N+1 query problems
- **Streaming Response:** No memory issues with large datasets
- **UTF-8 BOM:** Ensures Excel compatibility
- **Chunking:** For very large reports (>10,000 rows), consider implementing chunking

---

## Testing

### Test Scenarios

#### 1. Basic Export
```bash
curl -X GET "http://backend.errumbd.com/api/dispatches/export-csv" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -o dispatch_report.csv
```

**Expected:**
- CSV file downloads successfully
- Contains all dispatches
- All columns present

#### 2. Filtered Export
```bash
curl -X GET "http://backend.errumbd.com/api/dispatches/export-csv?status=delivered&date_from=2026-02-01" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -o delivered_dispatches.csv
```

**Expected:**
- Only delivered dispatches included
- Filtered by date correctly

#### 3. Empty Result
```bash
curl -X GET "http://backend.errumbd.com/api/dispatches/export-csv?date_from=2030-01-01" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Expected:**
- CSV with header row only
- No data rows (future date)

#### 4. Special Characters
Test with products/stores containing:
- Bengali characters: "স্যামসাং গ্যালাক্সি"
- Special symbols: "&", ",", quotes
- Unicode emojis

**Expected:**
- All characters display correctly in Excel
- No encoding issues

---

## Frontend Integration

### JavaScript/React Example

```javascript
const exportDispatchCSV = async (filters = {}) => {
  try {
    const queryParams = new URLSearchParams(filters).toString();
    const url = `/api/dispatches/export-csv${queryParams ? '?' + queryParams : ''}`;
    
    const response = await fetch(url, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
      },
    });

    // Get the filename from Content-Disposition header
    const contentDisposition = response.headers.get('Content-Disposition');
    const filename = contentDisposition
      ? contentDisposition.split('filename=')[1].replace(/"/g, '')
      : 'dispatch_report.csv';

    // Create blob and trigger download
    const blob = await response.blob();
    const downloadUrl = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = downloadUrl;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(downloadUrl);

    console.log('CSV exported successfully');
  } catch (error) {
    console.error('Failed to export CSV:', error);
  }
};

// Usage examples
exportDispatchCSV(); // All dispatches
exportDispatchCSV({ status: 'in_transit' }); // Filtered
exportDispatchCSV({ 
  date_from: '2026-03-01', 
  date_to: '2026-03-31',
  source_store_id: 5 
}); // Multiple filters
```

### Vue.js Example

```vue
<template>
  <div>
    <button @click="exportCSV" class="btn-export">
      Export CSV
    </button>
  </div>
</template>

<script>
export default {
  methods: {
    async exportCSV() {
      try {
        const response = await this.$axios.get('/dispatches/export-csv', {
          params: this.filters,
          responseType: 'blob'
        });

        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `dispatch_report_${Date.now()}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
      } catch (error) {
        this.$toast.error('Failed to export CSV');
      }
    }
  }
}
</script>
```

---

## Troubleshooting

### Issue: CSV Opens with Garbled Text in Excel

**Cause:** Encoding issue  
**Solution:** The export already includes UTF-8 BOM. If still garbled:
1. Open Excel
2. Go to Data > Get Data > From Text/CSV
3. Select file
4. Choose "UTF-8" encoding
5. Click "Load"

### Issue: Numbers Showing as Text

**Cause:** Excel treating numbers as text strings  
**Solution:** 
1. Select the column
2. Click "Text to Columns"
3. Click "Finish" without changing settings
4. Numbers convert to numeric format

### Issue: Large File (>100MB)

**Cause:** Too many dispatch records  
**Solution:** Use date range filters to export smaller chunks

### Issue: Export Times Out

**Cause:** Database query too slow  
**Solution:** 
- Add more specific filters
- Check database indexes
- Consider pagination or chunking

---

## Future Enhancements

### Potential Improvements:

1. **Excel Format Export:**
   - Generate .xlsx instead of CSV
   - Include formatting and charts
   - Multiple sheets (summary + details)

2. **Custom Column Selection:**
   - Allow users to choose which columns to export
   - Save column preferences per user

3. **Scheduled Exports:**
   - Automatic daily/weekly reports
   - Email delivery to stakeholders

4. **Advanced Filters:**
   - Product name search
   - Price range filtering
   - Multiple status selection

5. **Export Queue:**
   - Background job processing for large exports
   - Email notification when ready

6. **Template Support:**
   - Save filter combinations as templates
   - Quick access to common report types

---

## Security Considerations

### Access Control
- ✅ Requires authentication (JWT token)
- ✅ Permission check: `product_dispatches.view`
- ✅ Store-level access control applies

### Data Sensitivity
- Contains business-critical data (pricing, inventory)
- Should only be accessible to authorized personnel
- Consider adding audit logging for export actions

### Rate Limiting
Consider implementing rate limiting to prevent:
- Excessive report generation
- System resource exhaustion
- Data exfiltration attempts

**Recommended limits:**
- 10 exports per user per hour
- 100 exports per IP per day

---

## Related Documentation

- [Product Dispatch System](../IMPLEMENTATION_SUMMARY.md)
- [Dispatch Barcode Tracking](../../DISPATCH_BARCODE_ISSUE_REPORT.md)
- [Barcode Management](../../docs/BARCODE_MANAGEMENT.md)
- [Store Transfer Workflow](../../docs/STORE_TRANSFERS.md)

---

## Questions & Support

For questions or issues:
- Check Laravel logs: `storage/logs/laravel.log`
- Review dispatch permissions
- Verify database relationships
- Test with small date ranges first

---

## Changelog

### 2026-03-01 - Initial Release
- ✅ CSV export endpoint implemented
- ✅ Barcode grouping by product
- ✅ Multiple filter support
- ✅ UTF-8 BOM for Excel compatibility
- ✅ Permission-based access control
- ✅ Comprehensive documentation created
