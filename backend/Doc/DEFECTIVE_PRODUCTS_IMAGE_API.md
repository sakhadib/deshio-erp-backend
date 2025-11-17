# Defective Products Image Management API

## Overview
This document describes the image handling features for defective products in the ERP system.

## Changes Summary

### 1. Barcode Scan Response Enhancement
**Endpoint:** `POST /api/barcodes/scan`

The barcode scan response now includes:
- `barcode_id`: The unique ID of the barcode record (needed for marking as defective)
- `is_defective`: Boolean flag indicating if the barcode is marked as defective

**Updated Response:**
```json
{
  "success": true,
  "data": {
    "barcode_id": 123,              // ✅ NEW - Barcode ID
    "barcode": "123456789012",
    "barcode_type": "CODE128",
    "is_defective": false,          // ✅ NEW - Defective flag
    "product": {
      "id": 1,
      "name": "Product Name",
      "sku": "SKU123",
      "description": "Product description"
    },
    "current_location": {...},
    "current_batch": {...}
  }
}
```

---

## Defective Product Image Management

### 2. Mark Product as Defective (with Images)
**Endpoint:** `POST /api/defective-products/mark-defective`

**Request (Multipart Form Data):**
```javascript
const formData = new FormData();
formData.append('product_barcode_id', barcodeId);
formData.append('store_id', storeId);
formData.append('defect_type', 'physical_damage');
formData.append('defect_description', 'Screen cracked');
formData.append('severity', 'major');
formData.append('original_price', 50000);

// Add multiple images (up to 5 images, max 5MB each)
for (let i = 0; i < images.length; i++) {
  formData.append('defect_images[]', images[i]);
}

fetch('/api/defective-products/mark-defective', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
  },
  body: formData
});
```

**Validation:**
- `defect_images.*`: image|mimes:jpeg,png,jpg,gif|max:5120 (5MB)

**Response:**
```json
{
  "success": true,
  "message": "Product marked as defective successfully",
  "data": {
    "id": 1,
    "defect_images": [
      "defective-products/abc123.jpg",
      "defective-products/def456.jpg"
    ],
    "defect_type": "physical_damage",
    "severity": "major"
  }
}
```

---

### 3. Upload Additional Images
**Endpoint:** `POST /api/defective-products/{id}/images`

Upload additional images after marking a product as defective.

**Request (Multipart Form Data):**
```javascript
const formData = new FormData();
formData.append('images[]', image1);
formData.append('images[]', image2);
formData.append('images[]', image3);

fetch(`/api/defective-products/${defectiveProductId}/images`, {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
  },
  body: formData
});
```

**Validation:**
- `images`: required|array|min:1|max:5
- `images.*`: required|image|mimes:jpeg,png,jpg,gif|max:5120

**Response:**
```json
{
  "success": true,
  "message": "Images uploaded successfully",
  "data": {
    "id": 1,
    "defect_images": [
      "defective-products/old1.jpg",
      "defective-products/new1.jpg",
      "defective-products/new2.jpg"
    ],
    "image_urls": [
      "http://localhost/storage/defective-products/old1.jpg",
      "http://localhost/storage/defective-products/new1.jpg",
      "http://localhost/storage/defective-products/new2.jpg"
    ]
  }
}
```

---

### 4. Get Images for Defective Product
**Endpoint:** `GET /api/defective-products/{id}/images`

Retrieve all images with full URLs.

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "images": [
      {
        "path": "defective-products/abc123.jpg",
        "url": "http://localhost/storage/defective-products/abc123.jpg"
      },
      {
        "path": "defective-products/def456.jpg",
        "url": "http://localhost/storage/defective-products/def456.jpg"
      }
    ],
    "count": 2
  }
}
```

---

### 5. Delete an Image
**Endpoint:** `DELETE /api/defective-products/{id}/images`

Remove a specific image from a defective product.

**Request Body:**
```json
{
  "image_path": "defective-products/abc123.jpg"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Image deleted successfully",
  "data": {
    "id": 1,
    "defect_images": [
      "defective-products/def456.jpg"
    ]
  }
}
```

---

## Frontend Implementation Guide

### React Component Example

```jsx
import React, { useState } from 'react';

function DefectiveProductForm({ barcodeId, onSuccess }) {
  const [images, setImages] = useState([]);
  const [previews, setPreviews] = useState([]);

  const handleImageSelect = (e) => {
    const files = Array.from(e.target.files);
    
    // Limit to 5 images
    if (files.length + images.length > 5) {
      alert('Maximum 5 images allowed');
      return;
    }

    // Validate file size (5MB)
    const oversized = files.filter(f => f.size > 5 * 1024 * 1024);
    if (oversized.length > 0) {
      alert('Some images exceed 5MB limit');
      return;
    }

    setImages([...images, ...files]);

    // Generate previews
    files.forEach(file => {
      const reader = new FileReader();
      reader.onload = (e) => {
        setPreviews(prev => [...prev, e.target.result]);
      };
      reader.readAsDataURL(file);
    });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('product_barcode_id', barcodeId);
    formData.append('store_id', storeId);
    formData.append('defect_type', defectType);
    formData.append('defect_description', description);
    formData.append('severity', severity);
    formData.append('original_price', originalPrice);

    // Append images
    images.forEach((image, index) => {
      formData.append('defect_images[]', image);
    });

    try {
      const response = await fetch('/api/defective-products/mark-defective', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
        },
        body: formData
      });

      const data = await response.json();
      if (data.success) {
        onSuccess(data.data);
      }
    } catch (error) {
      console.error('Error:', error);
    }
  };

  return (
    <form onSubmit={handleSubmit}>
      {/* Form fields */}
      
      <div>
        <label>Defect Images (Max 5, up to 5MB each)</label>
        <input
          type="file"
          accept="image/jpeg,image/png,image/jpg,image/gif"
          multiple
          onChange={handleImageSelect}
        />
        
        <div className="image-previews">
          {previews.map((preview, index) => (
            <img 
              key={index} 
              src={preview} 
              alt={`Preview ${index + 1}`}
              style={{ width: 100, height: 100, objectFit: 'cover' }}
            />
          ))}
        </div>
      </div>

      <button type="submit">Mark as Defective</button>
    </form>
  );
}
```

---

## Storage Configuration

Images are stored in the `public` disk under the `defective-products` directory.

**Storage Path:** `storage/app/public/defective-products/`

**Public URL:** `http://your-domain.com/storage/defective-products/filename.jpg`

Make sure to run:
```bash
php artisan storage:link
```

---

## Defect Types

Available defect types:
- `physical_damage` - Physical damage (cracks, dents, etc.)
- `malfunction` - Product doesn't work properly
- `cosmetic` - Cosmetic issues (scratches, discoloration)
- `missing_parts` - Missing components or accessories
- `packaging_damage` - Damaged packaging
- `expired` - Expired product
- `counterfeit` - Suspected counterfeit
- `other` - Other defects

## Severity Levels

- `minor` - Minor defects, easily sellable
- `moderate` - Moderate defects, may affect sale price
- `major` - Major defects, significantly reduces value
- `critical` - Critical defects, may not be sellable

---

## Error Handling

### Common Errors

**1. File Too Large**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "defect_images.0": ["The image must not be greater than 5120 kilobytes."]
  }
}
```

**2. Invalid Image Type**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "defect_images.0": ["The image must be a file of type: jpeg, png, jpg, gif."]
  }
}
```

**3. Already Marked as Defective**
```json
{
  "success": false,
  "message": "This product is already marked as defective"
}
```

---

## Best Practices

1. **Always compress images** before upload to reduce size
2. **Show image previews** to users before submitting
3. **Implement progress indicators** for image uploads
4. **Handle upload failures gracefully** with retry options
5. **Display full image URLs** using the Storage::url() returned paths
6. **Validate client-side** before sending to reduce server load
7. **Use appropriate image formats**: JPEG for photos, PNG for screenshots

---

## Complete Workflow Example

```javascript
// 1. Scan barcode
const scanResponse = await fetch('/api/barcodes/scan', {
  method: 'POST',
  body: JSON.stringify({ barcode: scannedCode })
});
const scanData = await scanResponse.json();

// Get barcode_id and check if defective
const barcodeId = scanData.data.barcode_id;
const isDefective = scanData.data.is_defective;

if (isDefective) {
  // Already defective - show defective product details
  console.log('This product is already marked as defective');
} else {
  // 2. Mark as defective with images
  const formData = new FormData();
  formData.append('product_barcode_id', barcodeId);
  formData.append('store_id', 1);
  formData.append('defect_type', 'physical_damage');
  formData.append('defect_description', 'Screen cracked');
  formData.append('severity', 'major');
  formData.append('original_price', 50000);
  
  selectedImages.forEach(img => {
    formData.append('defect_images[]', img);
  });

  const markResponse = await fetch('/api/defective-products/mark-defective', {
    method: 'POST',
    body: formData
  });
  const defectData = await markResponse.json();
  
  console.log('Defective product created:', defectData.data);
}
```

---

## Summary of Changes

✅ **Barcode Scan**: Now returns `barcode_id` and `is_defective` flag  
✅ **Image Upload**: Supports multiple images when marking as defective  
✅ **Additional Images**: Can upload more images after initial marking  
✅ **Image Management**: Get and delete images for defective products  
✅ **Validation**: Proper validation for image types and sizes  
✅ **Storage**: Images stored in public storage with accessible URLs
