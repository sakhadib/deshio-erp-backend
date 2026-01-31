# Product Search API - Frontend Integration Guide

> **Date:** January 31, 2026  
> **For:** Frontend Development Team  
> **Priority:** CRITICAL - Fix Image Loading Issue

---

## 🚨 CRITICAL ISSUE TO FIX

**Problem:** Frontend is making hundreds of thousands of `/api/products/{id}/images` calls after search results load.

**Root Cause:** Frontend is incorrectly calling a separate API to fetch images for each product.

**Solution:** **DO NOT** call any image API. The search response **ALREADY CONTAINS** the full image URL.

---

## API Endpoints

### 1. Quick Search (Autocomplete)

```
GET /api/products/quick-search?q={query}&limit={limit}
```

**Headers Required:**
```
Authorization: Bearer {jwt_token}
Content-Type: application/json
Accept: application/json
```

**Query Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `q` | string | Yes | - | Search query (min 1 character) |
| `limit` | integer | No | 10 | Max results (1-20) |

**Example Request:**
```javascript
const response = await fetch('/api/products/quick-search?q=jamdani&limit=10', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
});
```

**Example Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 5,
      "name": "2450 JAMDANI 3PIECE",
      "sku": "1015140010",
      "category": "Saree",
      "vendor": "Deshio Vendor",
      "primary_image": {
        "id": 5,
        "url": "/storage/products/5/1764399406_q3ZZF9IPym.jpg",
        "alt_text": "2450 JAMDANI 3PIECE"
      }
    },
    {
      "id": 6,
      "name": "2450 JAMDANI 3PIECE - BLUE",
      "sku": "8000",
      "category": "Saree",
      "vendor": "Deshio Vendor",
      "primary_image": {
        "id": 6,
        "url": "/storage/products/6/1764399450_abc123.jpg",
        "alt_text": "2450 JAMDANI 3PIECE - BLUE"
      }
    }
  ]
}
```

---

### 2. Advanced Search (Full Search with Filters)

```
POST /api/products/advanced-search
```

**Headers Required:**
```
Authorization: Bearer {jwt_token}
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "query": "jamdani saree",
  "category_id": 1,
  "vendor_id": null,
  "is_archived": false,
  "enable_fuzzy": true,
  "fuzzy_threshold": 60,
  "search_fields": ["name", "sku", "description", "category", "custom_fields"],
  "per_page": 15
}
```

**Body Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `query` | string | Yes | - | Search query (min 2 characters) |
| `category_id` | integer | No | null | Filter by category |
| `vendor_id` | integer | No | null | Filter by vendor |
| `is_archived` | boolean | No | false | Include archived products |
| `enable_fuzzy` | boolean | No | true | Enable fuzzy/typo matching |
| `fuzzy_threshold` | integer | No | 60 | Fuzzy match threshold (50-100) |
| `search_fields` | array | No | all | Fields to search |
| `per_page` | integer | No | 15 | Results per page (1-100) |

**Example Response:**
```json
{
  "success": true,
  "query": "jamdani saree",
  "search_terms": ["jamdani", "saree", "jamdaani", "shari", "sharee"],
  "total_results": 15,
  "data": [
    {
      "id": 5,
      "name": "2450 JAMDANI 3PIECE",
      "sku": "1015140010",
      "description": "Premium quality Jamdani saree",
      "brand": null,
      "weight": null,
      "is_archived": false,
      "category": {
        "id": 1,
        "title": "Saree",
        "slug": "saree"
      },
      "vendor": {
        "id": 1,
        "name": "Deshio Vendor"
      },
      "product_fields": [],
      "images": [
        {
          "id": 5,
          "product_id": 5,
          "image_path": "products/5/1764399406_q3ZZF9IPym.jpg",
          "image_url": "/storage/products/5/1764399406_q3ZZF9IPym.jpg",
          "alt_text": "2450 JAMDANI 3PIECE",
          "is_primary": true,
          "is_active": true,
          "sort_order": 0
        },
        {
          "id": 8,
          "product_id": 5,
          "image_path": "products/5/1764399410_xyz789.jpg",
          "image_url": "/storage/products/5/1764399410_xyz789.jpg",
          "alt_text": "2450 JAMDANI 3PIECE side view",
          "is_primary": false,
          "is_active": true,
          "sort_order": 1
        }
      ],
      "search_stage": "exact",
      "relevance_score": 95.5
    }
  ],
  "search_metadata": {
    "fuzzy_enabled": true,
    "fuzzy_threshold": 60,
    "search_fields": ["name", "sku", "description", "category", "custom_fields"]
  }
}
```

---

### 3. Search Suggestions

```
GET /api/products/search-suggestions?q={query}&limit={limit}
```

**Query Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `q` | string | Yes | - | Partial search query (min 1 char) |
| `limit` | integer | No | 5 | Max suggestions (1-10) |

**Example Response:**
```json
{
  "success": true,
  "suggestions": [
    { "text": "Jamdani Saree", "type": "product", "relevance": 95 },
    { "text": "Jamdani 3 Piece", "type": "product", "relevance": 90 },
    { "text": "Saree", "type": "category", "relevance": 85 }
  ]
}
```

---

## ✅ CORRECT Image Handling

### The `image_url` is ALREADY in the Response

**DO THIS:**
```jsx
// React Example
function ProductCard({ product }) {
  // ✅ CORRECT: Use image_url directly from response
  const imageUrl = product.primary_image?.url 
    || product.images?.[0]?.image_url 
    || '/placeholder.png';

  return (
    <div className="product-card">
      <img 
        src={imageUrl}  // ✅ Direct URL from API response
        alt={product.primary_image?.alt_text || product.name}
        loading="lazy"
      />
      <h3>{product.name}</h3>
      <p>{product.sku}</p>
    </div>
  );
}
```

```vue
<!-- Vue Example -->
<template>
  <div class="product-card">
    <!-- ✅ CORRECT: Use image_url directly from response -->
    <img 
      :src="product.primary_image?.url || product.images?.[0]?.image_url || '/placeholder.png'"
      :alt="product.primary_image?.alt_text || product.name"
      loading="lazy"
    />
    <h3>{{ product.name }}</h3>
    <p>{{ product.sku }}</p>
  </div>
</template>
```

### ❌ DO NOT DO THIS

```javascript
// ❌ WRONG: Do NOT call API for each product's images
async function loadProductImages(productId) {
  // THIS IS THE BUG! Don't do this!
  const response = await fetch(`/api/products/${productId}/images`);
  return response.json();
}

// ❌ WRONG: This causes hundreds of API calls
products.forEach(async (product) => {
  product.images = await loadProductImages(product.id); // DON'T!
});
```

---

## Image URL Structure

| Response Type | Image Location | How to Access |
|---------------|----------------|---------------|
| Quick Search | `primary_image.url` | `product.primary_image?.url` |
| Advanced Search | `images[].image_url` | `product.images[0]?.image_url` |
| Advanced Search (primary) | First in `images[]` array | `product.images.find(i => i.is_primary)?.image_url` |

### Full Image URL

The `image_url` returns a **relative path** like:
```
/storage/products/5/1764399406_q3ZZF9IPym.jpg
```

To get full URL:
```javascript
const fullUrl = `${window.location.origin}${product.images[0].image_url}`;
// Result: "https://yoursite.com/storage/products/5/1764399406_q3ZZF9IPym.jpg"
```

Or simply use directly in `<img src>` - browsers handle relative URLs.

---

## Search Features

### Multi-Language Support

The search API supports:
- **English:** "jamdani saree"
- **Bangla Unicode:** "জামদানি শাড়ি"
- **Romanized Bangla:** "jamdani shari", "sharee"
- **Typos/Misspellings:** "jamdaani", "shaari", "jamdhani"

### Fuzzy Matching

When `enable_fuzzy: true`, the API automatically:
1. Transliterates Bangla to Roman
2. Generates phonetic variations
3. Matches common misspellings
4. Returns results with relevance scores

### Search Stages

Results include a `search_stage` field:
- `exact` - Exact match (highest relevance)
- `starts_with` - Query matches start of field
- `contains` - Query found within field
- `fuzzy` - Matched via fuzzy/typo tolerance

---

## Error Handling

```javascript
async function searchProducts(query) {
  try {
    const response = await fetch(`/api/products/quick-search?q=${encodeURIComponent(query)}`, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      }
    });

    if (!response.ok) {
      if (response.status === 401) {
        // Token expired - refresh or redirect to login
        throw new Error('Authentication required');
      }
      if (response.status === 422) {
        // Validation error
        const error = await response.json();
        throw new Error(error.message || 'Invalid search query');
      }
      throw new Error('Search failed');
    }

    const data = await response.json();
    return data.data; // Array of products WITH images already included

  } catch (error) {
    console.error('Search error:', error);
    throw error;
  }
}
```

---

## Complete React Example

```jsx
import { useState, useEffect, useCallback } from 'react';
import debounce from 'lodash/debounce';

function ProductSearch() {
  const [query, setQuery] = useState('');
  const [results, setResults] = useState([]);
  const [loading, setLoading] = useState(false);

  // Debounced search function
  const performSearch = useCallback(
    debounce(async (searchQuery) => {
      if (searchQuery.length < 1) {
        setResults([]);
        return;
      }

      setLoading(true);
      try {
        const response = await fetch(
          `/api/products/quick-search?q=${encodeURIComponent(searchQuery)}&limit=10`,
          {
            headers: {
              'Authorization': `Bearer ${localStorage.getItem('token')}`,
              'Accept': 'application/json'
            }
          }
        );
        const data = await response.json();
        
        if (data.success) {
          setResults(data.data);
        }
      } catch (error) {
        console.error('Search failed:', error);
      } finally {
        setLoading(false);
      }
    }, 300),
    []
  );

  useEffect(() => {
    performSearch(query);
  }, [query, performSearch]);

  return (
    <div className="product-search">
      <input
        type="text"
        value={query}
        onChange={(e) => setQuery(e.target.value)}
        placeholder="Search products..."
      />

      {loading && <div>Loading...</div>}

      <div className="results">
        {results.map((product) => (
          <div key={product.id} className="product-card">
            {/* ✅ CORRECT: Use image URL directly from response */}
            <img
              src={product.primary_image?.url || '/placeholder.png'}
              alt={product.primary_image?.alt_text || product.name}
              loading="lazy"
              onError={(e) => { e.target.src = '/placeholder.png'; }}
            />
            <div className="product-info">
              <h4>{product.name}</h4>
              <p className="sku">SKU: {product.sku}</p>
              <p className="category">{product.category}</p>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}

export default ProductSearch;
```

---

## API Endpoints Summary

| Endpoint | Method | Auth | Purpose |
|----------|--------|------|---------|
| `/api/products/quick-search` | GET | Yes | Autocomplete/quick search |
| `/api/products/advanced-search` | POST | Yes | Full search with filters |
| `/api/products/search-suggestions` | GET | Yes | Search suggestions |
| `/api/products/search-stats` | GET | Yes | Search analytics |

### ⚠️ DO NOT USE for displaying search results:

| Endpoint | Purpose | When to Use |
|----------|---------|-------------|
| `/api/products/{id}/images` | List all product images | **Admin panel ONLY** |
| `/api/product-images/{id}` | Get single image details | **Admin panel ONLY** |

---

## Checklist for Frontend Fix

- [ ] Remove any code that calls `/api/products/{id}/images` after search
- [ ] Remove any code that calls `/api/product-images/{id}` after search
- [ ] Use `product.primary_image.url` for quick search results
- [ ] Use `product.images[0].image_url` for advanced search results
- [ ] Add `loading="lazy"` to images for performance
- [ ] Add error handler for missing images (fallback to placeholder)
- [ ] Debounce search input (300ms recommended)

---

## Contact

If you have questions about this API:
- Check existing docs in `/docs/2026-01-29/PRODUCT_SEARCH_SYSTEM.md`
- Backend team can provide additional clarification
