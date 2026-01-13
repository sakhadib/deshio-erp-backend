# Product Management Documentation

**Location:** `/docs/product/`  
**Purpose:** Comprehensive API documentation for product management system

---

## 📚 Documentation Files

### Core Product APIs

1. **[Product API](./2026_01_13_PRODUCT_API.md)** - `2026_01_13_PRODUCT_API.md`
   - Product CRUD operations
   - Custom fields management
   - Bulk updates (category, vendor, archive/restore)
   - Product statistics
   - Search by custom fields
   - Archive and restore functionality

2. **[Product Variants API](./2026_01_13_PRODUCT_VARIANTS_API.md)** - `2026_01_13_PRODUCT_VARIANTS_API.md`
   - Variant CRUD operations
   - Size/color matrix generation
   - Variant options management
   - Individual variant pricing and stock
   - Default variant handling
   - Variant statistics

3. **[Product Barcodes API](./2026_01_13_PRODUCT_BARCODES_API.md)** - `2026_01_13_PRODUCT_BARCODES_API.md`
   - Barcode generation (CODE128, EAN13, QR)
   - Single and batch scanning
   - Location tracking and movement history
   - Primary barcode management
   - Defective item marking
   - Current location queries

---

## 🏗️ System Architecture

### Product Hierarchy

```
┌─────────────────────────────────────────────────────────────┐
│                     Product (Parent)                         │
│  - Common Info: name, description, category, vendor, brand  │
│  - SKU (not unique - supports variations)                   │
│  - Custom Fields: dynamic attributes                        │
│  - Archive status                                           │
└───────────────┬─────────────────────────────────────────────┘
                │
      ┌─────────┼─────────┬──────────┬──────────┐
      │         │         │          │          │
┌─────▼───┐ ┌──▼────┐ ┌──▼─────┐ ┌──▼─────┐ ┌──▼────┐
│ Images  │ │Variants│ │Barcodes│ │Batches │ │Fields │
│         │ │        │ │        │ │        │ │       │
│Multiple │ │Size/   │ │Unique  │ │Stock   │ │Custom │
│Primary  │ │Color   │ │Tracking│ │Pricing │ │Values │
└─────────┘ └────────┘ └────────┘ └────────┘ └───────┘
```

### Data Flow

```
1. Create Product → 2. Add Custom Fields → 3. Upload Images
                ↓
4. Create Variants (if needed) → 5. Generate Barcodes → 6. Add Stock Batches
                ↓
7. Ready for Sale
```

---

## 🔑 Key Concepts

### Common vs Variant-Specific Fields

| Common (Parent Product) | Variant-Specific |
|------------------------|------------------|
| Name | SKU (unique) |
| Description | Barcode |
| Category | Attributes (Size, Color) |
| Vendor | Price Adjustment |
| Brand | Cost Price |
| Parent SKU | Stock Quantity |
| Custom Fields | Reserved Quantity |
|  | Image URL |
|  | Active Status |

### Field Inheritance

When you update common fields on the parent product:
- ✅ All variants automatically inherit the change
- ✅ Variant-specific fields remain unchanged
- ✅ No need to update each variant individually

Example:
```
Update Product Category: Electronics → Clothing
Result: All variants (all sizes/colors) now show under "Clothing"
```

---

## 🚀 Quick Start Guide

### 1. Creating a Simple Product

```http
POST /api/employee/products
{
  "name": "T-Shirt",
  "sku": "TSHIRT-001",
  "category_id": 10,
  "vendor_id": 5,
  "brand": "Nike",
  "description": "Cotton t-shirt"
}
```

### 2. Adding Custom Fields

```http
POST /api/employee/products/1/custom-fields
{
  "field_id": 1,
  "value": "100% Cotton"
}
```

### 3. Creating Variants (Size/Color Matrix)

```http
POST /api/employee/products/1/variants/generate-matrix
{
  "attributes": {
    "Size": ["S", "M", "L", "XL"],
    "Color": ["Red", "Blue", "Black"]
  }
}
```
*Generates 12 variants automatically*

### 4. Generating Barcodes

```http
POST /api/employee/barcodes/generate
{
  "product_id": 1,
  "type": "CODE128",
  "quantity": 12
}
```
*One barcode per variant*

### 5. Scanning at POS

```http
POST /api/employee/barcodes/scan
{
  "barcode": "1234567890123"
}
```

---

## 📊 Common Workflows

### Workflow 1: Fashion Product with Variations

**Scenario:** Add a clothing item with multiple sizes and colors

1. **Create parent product** (name, description, category)
2. **Generate variant matrix** (all size/color combinations)
3. **Update variant prices** (premium colors cost more)
4. **Set variant stock** (update quantities per variant)
5. **Generate barcodes** (one per variant)
6. **Upload images** (main product images + variant-specific)

### Workflow 2: Simple Product (No Variations)

**Scenario:** Add an electronic item with single SKU

1. **Create product** (name, description, specs)
2. **Add custom fields** (warranty period, specifications)
3. **Generate single barcode**
4. **Add stock batch** (quantity, pricing, expiry)
5. **Upload product images**

### Workflow 3: Bulk Category Update

**Scenario:** Move 50 products to a new category

```http
POST /api/employee/products/bulk-update
{
  "product_ids": [1, 2, 3, ..., 50],
  "action": "update_category",
  "category_id": 15
}
```
*All variants of all 50 products now show under new category*

### Workflow 4: Inventory Verification

**Scenario:** Physical stock count verification

1. **List barcodes** by store/product
2. **Batch scan** physical items
3. **Compare results** (found vs expected)
4. **Adjust stock** if discrepancies found

---

## 🎯 Use Cases by Role

### Store Manager
- Archive discontinued products
- Bulk update categories for seasonal reorganization
- View product statistics and inventory value
- Manage variant stock levels

### Sales Associate
- Scan barcodes at POS
- Check product availability
- View product details and pricing
- Process returns with barcode scan

### Inventory Manager
- Generate barcodes for new stock
- Track barcode location history
- Verify stock with batch scanning
- Manage product batches and expiry

### E-commerce Manager
- Update product descriptions
- Manage product images
- Set variant pricing
- Configure custom product fields

---

## 🔍 Search & Filter Capabilities

### Product Search
- By name, SKU, description
- By category or vendor
- By custom field values
- Active vs archived status

### Barcode Search
- By barcode value
- By product ID
- By location/store
- By active/primary status

### Variant Search
- By parent product
- By active status
- Low stock variants
- Default variants

---

## 📈 Analytics & Reporting

### Available Statistics

**Product Statistics:**
- Total active/archived products
- Products by category
- Products by vendor
- Recently added products
- Total inventory value

**Variant Statistics:**
- Total variants per product
- Active variants count
- Low stock variants
- Total stock quantity
- Available vs reserved stock

**Barcode Tracking:**
- Movement count
- Current location
- Location history
- Defective items count

---

## 🛡️ Data Validation Rules

### Product Creation
- `name`: Required, max 255 characters
- `sku`: Required, string (not unique)
- `category_id`: Required, must exist
- `vendor_id`: Optional, must exist if provided
- `brand`: Optional, max 255 characters

### Variant Creation
- `sku`: Required, **must be unique**
- `barcode`: Optional, **must be unique** if provided
- `attributes`: Required, JSON object
- `cost_price`: Must be >= 0
- `stock_quantity`: Must be >= 0

### Barcode Generation
- `product_id`: Required, must exist
- `type`: CODE128, EAN13, or QR
- `quantity`: 1-100 per request

---

## ⚠️ Important Constraints

### Product Management
- ❌ Cannot delete product with existing batches/orders
- ✅ Archive product instead of deleting
- ✅ Restore archived products anytime
- ✅ SKU not unique (supports variations)

### Variant Management
- ❌ Cannot delete default variant
- ✅ Must have at least one active variant
- ✅ Only one default variant per product
- ✅ Variant SKU must be unique

### Barcode Management
- ❌ Cannot deactivate last active barcode
- ✅ Barcodes are automatically unique
- ✅ Only one primary barcode per product
- ✅ Defective barcodes excluded from scans

---

## 🔗 Integration Points

### Related Systems
- **Inventory Management**: Stock batches, reordering
- **Order Management**: Product selection, stock reservation
- **POS System**: Barcode scanning, checkout
- **Dispatch System**: Product transfers, location tracking
- **E-commerce**: Product catalog, variant selection
- **Accounting**: COGS calculation, inventory valuation

### External Integrations
- **Barcode Printers**: Label generation
- **Scanner Devices**: POS scanners, inventory scanners
- **Image Storage**: Product image hosting
- **Analytics Tools**: Sales reporting, inventory analytics

---

## 🛠️ Best Practices

### Product Organization
1. Use consistent naming conventions
2. Set appropriate categories
3. Add detailed descriptions
4. Use custom fields for searchable attributes
5. Archive instead of delete

### Variant Management
1. Generate matrix for consistent variations
2. Use SKU format: `PARENT-SIZE-COLOR`
3. Set realistic reorder points
4. Mark most popular as default
5. Update prices per variant if needed

### Barcode Operations
1. Generate barcodes immediately
2. Print labels for physical items
3. Set one primary barcode
4. Use batch scan for efficiency
5. Track location for inventory

### Stock Management
1. Use batches for pricing/expiry tracking
2. Monitor reserved quantities
3. Set reorder points appropriately
4. Verify stock with physical counts
5. Handle defectives immediately

---

## 📞 API Endpoints Summary

### Products
- `GET /api/employee/products` - List products
- `POST /api/employee/products` - Create product
- `GET /api/employee/products/{id}` - Get product
- `PUT /api/employee/products/{id}` - Update product
- `DELETE /api/employee/products/{id}` - Delete product
- `POST /api/employee/products/bulk-update` - Bulk update
- `GET /api/employee/products/stats` - Statistics

### Variants
- `GET /api/employee/products/{id}/variants` - List variants
- `POST /api/employee/products/{id}/variants` - Create variant
- `GET /api/employee/products/{id}/variants/{variantId}` - Get variant
- `PUT /api/employee/products/{id}/variants/{variantId}` - Update variant
- `DELETE /api/employee/products/{id}/variants/{variantId}` - Delete variant
- `POST /api/employee/products/{id}/variants/generate-matrix` - Generate matrix
- `GET /api/employee/products/{id}/variants/statistics` - Statistics

### Barcodes
- `POST /api/employee/barcodes/scan` - Scan barcode
- `POST /api/employee/barcodes/batch-scan` - Batch scan
- `GET /api/employee/barcodes` - List barcodes
- `POST /api/employee/barcodes/generate` - Generate barcodes
- `GET /api/employee/products/{id}/barcodes` - Product barcodes
- `GET /api/employee/barcodes/{barcode}/history` - Location history
- `GET /api/employee/barcodes/{barcode}/location` - Current location
- `PATCH /api/employee/barcodes/{id}/make-primary` - Set primary
- `DELETE /api/employee/barcodes/{id}` - Deactivate barcode

---

## 🎓 Learning Resources

### Tutorial Videos (Coming Soon)
- Creating products with variants
- Barcode scanning workflows
- Inventory management basics
- Bulk operations guide

### API Playground
- Test endpoints with sample data
- View request/response examples
- Practice barcode scanning

---

## 📝 Related Documentation

### Feature Guides
- [Product Common Info Update](../features/PRODUCT_COMMON_INFO_UPDATE_API.md) - Bulk updates affecting variants
- [Dispatch Barcode System](../features/DISPATCH_BARCODE_SYSTEM.md) - Product dispatch tracking

### Other Modules
- [Inventory Management](../README.md#inventory) - Stock batches and tracking
- [Order Management](../README.md#orders) - Order processing
- [Category Management](../features/CATEGORY_HARD_DELETE_API.md) - Category operations

---

## 🆘 Troubleshooting

### Common Issues

**Issue:** Cannot delete product
- **Cause:** Product has existing batches or orders
- **Solution:** Archive the product instead

**Issue:** Variant SKU already exists
- **Cause:** SKU must be unique across all variants
- **Solution:** Use different SKU format

**Issue:** Barcode not found
- **Cause:** Barcode doesn't exist or is inactive
- **Solution:** Verify barcode value or generate new one

**Issue:** Cannot deactivate barcode
- **Cause:** Last active barcode for product
- **Solution:** Generate new barcode first

---

## 📅 Change Log

| Date | Version | Changes |
|------|---------|---------|
| 2026-01-13 | 1.0 | Initial product documentation suite created |

---

## 💬 Support & Feedback

For questions, issues, or suggestions regarding the Product Management API:
- Contact: Backend Development Team
- Email: dev@deshio-erp.com
- Slack: #backend-support

---

[← Back to Main Documentation](../README.md)
