# Product Archive & Unarchive API Documentation

## Overview
The product archive system provides a **soft delete** mechanism for products. Archived products are hidden from active listings but can be restored at any time. This is safer than permanent deletion and allows for product lifecycle management.

**✅ CONFIRMED: Both archive and unarchive (restore) endpoints exist and are functional.**

---

## Key Concepts

### What is Archiving?
- **Archive** = Soft delete, sets `is_archived = true`
- **Restore** = Unarchive, sets `is_archived = false`
- **Archived products** are hidden from default product listings
- **All data remains intact** (batches, images, custom fields)
- **Can be restored** anytime with full data recovery

### When to Archive vs Delete
| Action | Use Case | Data | Reversible |
|--------|----------|------|------------|
| **Archive** | Discontinued products, seasonal items, temporary removal | Preserved | ✅ Yes |
| **Delete** | Duplicate entries, test data, data cleanup | Removed | ❌ No* |

*Products with batches cannot be permanently deleted

### Default Behavior
- **Product listings** show only active products (`is_archived = false`) by default
- **Filtering** by `is_archived` parameter allows viewing archived products
- **Archived products** don't appear in search results unless explicitly requested

---

## API Endpoints

### 1. Archive Single Product

**Endpoint:**
```http
PATCH /api/products/{id}/archive
```

**Authentication:** Employee JWT token required

**Request:**
```http
PATCH /api/products/123/archive
Authorization: Bearer {employee_token}
```

**Response - Success (200):**
```json
{
  "success": true,
  "message": "Product archived successfully"
}
```

**Response - Not Found (404):**
```json
{
  "success": false,
  "message": "Product not found"
}
```

---

### 2. Restore (Unarchive) Single Product

**Endpoint:**
```http
PATCH /api/products/{id}/restore
```

**Authentication:** Employee JWT token required

**Request:**
```http
PATCH /api/products/123/restore
Authorization: Bearer {employee_token}
```

**Response - Success (200):**
```json
{
  "success": true,
  "message": "Product restored successfully",
  "data": {
    "id": 123,
    "category_id": 5,
    "vendor_id": 10,
    "sku": "PROD-001",
    "name": "Product Name",
    "description": "Product description",
    "brand": "Brand Name",
    "is_archived": false,
    "created_at": "2025-12-01T10:00:00.000000Z",
    "updated_at": "2025-12-12T15:30:00.000000Z"
  }
}
```

**Response - Not Found (404):**
```json
{
  "success": false,
  "message": "Product not found"
}
```

---

### 3. Bulk Archive/Restore Products

**Endpoint:**
```http
POST /api/products/bulk-update
```

**Authentication:** Employee JWT token required

**Request Body - Archive Multiple:**
```json
{
  "product_ids": [123, 456, 789],
  "action": "archive"
}
```

**Request Body - Restore Multiple:**
```json
{
  "product_ids": [123, 456, 789],
  "action": "restore"
}
```

**Validation Rules:**

| Field | Type | Rules | Description |
|-------|------|-------|-------------|
| `product_ids` | array | required, array of integers | Product IDs to update |
| `product_ids.*` | integer | exists in products table | Each ID must be valid |
| `action` | string | required, one of: `archive`, `restore`, `update_category`, `update_vendor` | Action to perform |

**Response - Success (200):**
```json
{
  "success": true,
  "message": "Archived 3 products"
}
```

OR

```json
{
  "success": true,
  "message": "Restored 3 products"
}
```

**Response - Validation Error (422):**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "product_ids": ["The product ids field is required."],
    "action": ["The selected action is invalid."]
  }
}
```

**Response - Server Error (500):**
```json
{
  "success": false,
  "message": "Bulk update failed: {error_details}"
}
```

---

### 4. List Products with Archive Filter

**Endpoint:**
```http
GET /api/products
```

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `is_archived` | boolean | `false` | Filter by archive status |
| `page` | integer | 1 | Page number |
| `per_page` | integer | 15 | Items per page |
| `search` | string | - | Search term |
| `category_id` | integer | - | Filter by category |
| `vendor_id` | integer | - | Filter by vendor |

**Examples:**

**Get Active Products (default):**
```http
GET /api/products
Authorization: Bearer {employee_token}
```

**Get Archived Products:**
```http
GET /api/products?is_archived=true
Authorization: Bearer {employee_token}
```

**Get All Products (active + archived):**
```http
GET /api/products?is_archived=all
Authorization: Bearer {employee_token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "products": [
      {
        "id": 123,
        "name": "Product Name",
        "sku": "PROD-001",
        "is_archived": false,
        "category": {...},
        "vendor": {...}
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 5,
      "per_page": 15,
      "total": 73
    }
  }
}
```

---

## Frontend Implementation Guide

### TypeScript Interfaces

```typescript
// types/product.ts
interface Product {
  id: number;
  category_id: number;
  vendor_id: number | null;
  sku: string;
  name: string;
  description: string | null;
  brand: string | null;
  is_archived: boolean;
  created_at: string;
  updated_at: string;
  deleted_at: string | null;
}

interface BulkUpdateRequest {
  product_ids: number[];
  action: 'archive' | 'restore' | 'update_category' | 'update_vendor';
  category_id?: number;
  vendor_id?: number;
}

interface ApiResponse<T> {
  success: boolean;
  message: string;
  data?: T;
  errors?: Record<string, string[]>;
}
```

### Service Layer

```typescript
// services/productService.ts
import api from './api';

export const productService = {
  /**
   * Archive a single product
   */
  archiveProduct: async (productId: number): Promise<ApiResponse<void>> => {
    const response = await api.patch(`/api/products/${productId}/archive`);
    return response.data;
  },

  /**
   * Restore (unarchive) a single product
   */
  restoreProduct: async (productId: number): Promise<ApiResponse<Product>> => {
    const response = await api.patch(`/api/products/${productId}/restore`);
    return response.data;
  },

  /**
   * Bulk archive products
   */
  bulkArchive: async (productIds: number[]): Promise<ApiResponse<void>> => {
    const response = await api.post('/api/products/bulk-update', {
      product_ids: productIds,
      action: 'archive'
    });
    return response.data;
  },

  /**
   * Bulk restore products
   */
  bulkRestore: async (productIds: number[]): Promise<ApiResponse<void>> => {
    const response = await api.post('/api/products/bulk-update', {
      product_ids: productIds,
      action: 'restore'
    });
    return response.data;
  },

  /**
   * Get products with archive filter
   */
  getProducts: async (params: {
    is_archived?: boolean;
    page?: number;
    per_page?: number;
    search?: string;
  }) => {
    const response = await api.get('/api/products', { params });
    return response.data;
  }
};
```

### React Components

#### Single Product Archive/Restore

```jsx
// components/ProductArchiveButton.tsx
import { useState } from 'react';
import { productService } from '../services/productService';

interface Props {
  product: Product;
  onUpdate: (product: Product) => void;
}

export function ProductArchiveButton({ product, onUpdate }: Props) {
  const [loading, setLoading] = useState(false);

  const handleArchive = async () => {
    if (!confirm(`Archive "${product.name}"? It can be restored later.`)) {
      return;
    }

    setLoading(true);
    try {
      await productService.archiveProduct(product.id);
      onUpdate({ ...product, is_archived: true });
      showToast('Product archived successfully', 'success');
    } catch (error) {
      showToast('Failed to archive product', 'error');
      console.error(error);
    } finally {
      setLoading(false);
    }
  };

  const handleRestore = async () => {
    setLoading(true);
    try {
      const result = await productService.restoreProduct(product.id);
      onUpdate(result.data);
      showToast('Product restored successfully', 'success');
    } catch (error) {
      showToast('Failed to restore product', 'error');
      console.error(error);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="product-archive-controls">
      {product.is_archived ? (
        <button
          onClick={handleRestore}
          disabled={loading}
          className="btn btn-success"
        >
          {loading ? 'Restoring...' : '↻ Restore'}
        </button>
      ) : (
        <button
          onClick={handleArchive}
          disabled={loading}
          className="btn btn-warning"
        >
          {loading ? 'Archiving...' : '📦 Archive'}
        </button>
      )}
    </div>
  );
}
```

#### Product List with Archive Toggle

```jsx
// components/ProductList.tsx
import { useState, useEffect } from 'react';
import { productService } from '../services/productService';

export function ProductList() {
  const [products, setProducts] = useState<Product[]>([]);
  const [showArchived, setShowArchived] = useState(false);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    loadProducts();
  }, [showArchived]);

  const loadProducts = async () => {
    setLoading(true);
    try {
      const result = await productService.getProducts({
        is_archived: showArchived
      });
      setProducts(result.data.products);
    } catch (error) {
      console.error('Failed to load products:', error);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="product-list">
      <div className="toolbar">
        <h2>Products</h2>
        
        <label className="toggle-switch">
          <input
            type="checkbox"
            checked={showArchived}
            onChange={(e) => setShowArchived(e.target.checked)}
          />
          <span>Show Archived</span>
        </label>
      </div>

      {loading ? (
        <div>Loading...</div>
      ) : (
        <table className="products-table">
          <thead>
            <tr>
              <th>SKU</th>
              <th>Name</th>
              <th>Category</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            {products.map(product => (
              <tr key={product.id} className={product.is_archived ? 'archived' : ''}>
                <td>{product.sku}</td>
                <td>{product.name}</td>
                <td>{product.category?.name}</td>
                <td>
                  {product.is_archived ? (
                    <span className="badge badge-warning">Archived</span>
                  ) : (
                    <span className="badge badge-success">Active</span>
                  )}
                </td>
                <td>
                  <ProductArchiveButton
                    product={product}
                    onUpdate={(updated) => {
                      setProducts(prev =>
                        prev.map(p => p.id === updated.id ? updated : p)
                      );
                    }}
                  />
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      )}
    </div>
  );
}
```

#### Bulk Archive/Restore

```jsx
// components/BulkArchiveActions.tsx
import { useState } from 'react';
import { productService } from '../services/productService';

interface Props {
  selectedProductIds: number[];
  onComplete: () => void;
}

export function BulkArchiveActions({ selectedProductIds, onComplete }: Props) {
  const [loading, setLoading] = useState(false);

  const handleBulkArchive = async () => {
    if (!selectedProductIds.length) {
      alert('Please select products first');
      return;
    }

    if (!confirm(`Archive ${selectedProductIds.length} products?`)) {
      return;
    }

    setLoading(true);
    try {
      await productService.bulkArchive(selectedProductIds);
      showToast(`Archived ${selectedProductIds.length} products`, 'success');
      onComplete();
    } catch (error) {
      showToast('Bulk archive failed', 'error');
      console.error(error);
    } finally {
      setLoading(false);
    }
  };

  const handleBulkRestore = async () => {
    if (!selectedProductIds.length) {
      alert('Please select products first');
      return;
    }

    setLoading(true);
    try {
      await productService.bulkRestore(selectedProductIds);
      showToast(`Restored ${selectedProductIds.length} products`, 'success');
      onComplete();
    } catch (error) {
      showToast('Bulk restore failed', 'error');
      console.error(error);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="bulk-actions">
      <span className="selected-count">
        {selectedProductIds.length} selected
      </span>
      
      <button
        onClick={handleBulkArchive}
        disabled={loading || !selectedProductIds.length}
        className="btn btn-warning"
      >
        {loading ? 'Archiving...' : '📦 Archive Selected'}
      </button>
      
      <button
        onClick={handleBulkRestore}
        disabled={loading || !selectedProductIds.length}
        className="btn btn-success"
      >
        {loading ? 'Restoring...' : '↻ Restore Selected'}
      </button>
    </div>
  );
}
```

---

## Business Rules

### Archive Behavior
1. **Product is hidden** from default listings
2. **All related data preserved**: batches, images, custom fields, barcodes
3. **Inventory remains tracked** but product unavailable for new orders
4. **Can be restored** at any time with all data intact

### Restore Behavior
1. **Product becomes active** immediately
2. **Appears in listings** again
3. **All data accessible** as before archiving
4. **Available for orders** if inventory exists

### Deletion Rules
- **Products with batches**: Can only be archived, not deleted
- **Products without batches**: Can be permanently deleted
- **Archived products**: Can be permanently deleted after archiving

### Permission Requirements
- **Archive**: Requires employee authentication
- **Restore**: Requires employee authentication
- **View archived**: Requires employee authentication

---

## Common Use Cases

### 1. Discontinue a Product
```typescript
// Archive instead of delete to preserve history
await productService.archiveProduct(productId);
```

### 2. Seasonal Products
```typescript
// Archive at end of season
await productService.bulkArchive(summerProductIds);

// Restore for next season
await productService.bulkRestore(summerProductIds);
```

### 3. View Archived Products
```typescript
// Toggle to show archived products
const archivedProducts = await productService.getProducts({
  is_archived: true
});
```

### 4. Bulk Archive During Inventory Cleanup
```typescript
// Select old/discontinued products
const oldProductIds = [123, 456, 789, 101, 112];
await productService.bulkArchive(oldProductIds);
```

---

## Error Handling

### Common Errors

| Error | Status | Reason | Solution |
|-------|--------|--------|----------|
| Product not found | 404 | Invalid product ID | Verify product exists |
| Unauthorized | 401 | No/invalid token | Re-authenticate |
| Validation failed | 422 | Invalid request data | Check request format |
| Server error | 500 | Backend issue | Retry or contact support |

### Frontend Error Handling Pattern

```typescript
try {
  await productService.archiveProduct(productId);
  showSuccessMessage('Product archived');
  refreshProductList();
} catch (error) {
  if (error.response?.status === 404) {
    showError('Product not found');
  } else if (error.response?.status === 401) {
    showError('Please log in again');
    redirectToLogin();
  } else {
    showError('Failed to archive product. Please try again.');
  }
  console.error('Archive error:', error);
}
```

---

## UI/UX Recommendations

### Visual Indicators
- ✅ Use **orange/yellow** for archive button (warning color)
- ✅ Use **green** for restore button (success color)
- ✅ Show **"Archived"** badge on archived products
- ✅ Gray out or dim archived product rows

### Confirmation Dialogs
- ✅ Always confirm before archiving
- ✅ Show "can be restored later" in confirmation
- ⚠️ Optional confirmation for restore (less destructive)

### Bulk Operations
- ✅ Show count of selected items
- ✅ Disable buttons when no items selected
- ✅ Show loading state during operation
- ✅ Show success message with count

### Filter/Toggle
- ✅ Provide easy toggle between active/archived views
- ✅ Default to showing active products only
- ✅ Clear visual indicator of current filter state

---

## Testing Checklist

### Single Product Operations
- [ ] Can archive an active product
- [ ] Can restore an archived product
- [ ] Archived product hidden from default listings
- [ ] Restored product appears in listings
- [ ] Error handling for invalid product ID
- [ ] Loading states work correctly

### Bulk Operations
- [ ] Can archive multiple products at once
- [ ] Can restore multiple products at once
- [ ] Correct count shown in success message
- [ ] All selected products updated
- [ ] Validation errors handled properly
- [ ] Empty selection handled gracefully

### Filtering
- [ ] Default shows only active products
- [ ] Can toggle to show archived products
- [ ] Filter persists across navigation
- [ ] Search works with archived products

### Edge Cases
- [ ] Products with batches can be archived
- [ ] Products with orders can be archived
- [ ] Archived products maintain all relationships
- [ ] Bulk operations handle partial failures

---

## Related Documentation

- **Product Management**: [PRODUCT_MANAGEMENT_SYSTEM.md](PRODUCT_MANAGEMENT_SYSTEM.md)
- **Product Search**: [PRODUCT_SEARCH_SYSTEM.md](PRODUCT_SEARCH_SYSTEM.md)
- **Recycle Bin**: [RECYCLE_BIN.md](RECYCLE_BIN.md) (for permanent deletion)

---

## Quick Reference

```bash
# Archive single product
PATCH /api/products/{id}/archive

# Restore single product  
PATCH /api/products/{id}/restore

# Bulk archive
POST /api/products/bulk-update
{"product_ids": [1,2,3], "action": "archive"}

# Bulk restore
POST /api/products/bulk-update
{"product_ids": [1,2,3], "action": "restore"}

# List archived products
GET /api/products?is_archived=true

# List active products (default)
GET /api/products
```

---

## Support

For questions or issues:
- **Backend Team**: [Add contact]
- **API Documentation**: `/api/documentation`
- **Issue Tracker**: [Add link]

Last Updated: December 12, 2025
