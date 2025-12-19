# Batch Price Update API - Frontend Integration Guide

## Date: December 19, 2025
## API Version: 1.0

---

## Overview

This API allows you to update the selling price of **ALL batches** for a specific product to a single price in one request.

**Use Case:** When the same product has multiple batches with different selling prices (e.g., Batch A: ৳2000, Batch B: ৳3000), you can now set all batches to the same price (e.g., ৳4000) with one API call.

---

## API Endpoint

### Update All Batch Prices for a Product

**Method:** `POST`  
**Endpoint:** `/api/products/{product_id}/batches/update-price`  
**Authentication:** Required (Employee/Admin)

---

## Request Format

### URL Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `product_id` | integer | Yes | The ID of the product whose batch prices you want to update |

### Request Body

```json
{
  "sell_price": 4000.00
}
```

| Field | Type | Required | Validation | Description |
|-------|------|----------|------------|-------------|
| `sell_price` | number | Yes | numeric, min:0 | The new selling price to apply to all batches |

---

## Response Format

### Success Response (200 OK)

```json
{
  "success": true,
  "message": "Successfully updated selling price for all batches",
  "data": {
    "product_id": 123,
    "product_name": "Samsung Galaxy S23",
    "product_sku": "SAM-S23-001",
    "new_sell_price": "4000.00",
    "batches_updated": 3,
    "updates": [
      {
        "batch_id": 45,
        "batch_number": "BATCH-001",
        "store": "Main Store",
        "old_price": "2000.00",
        "new_price": "4000.00"
      },
      {
        "batch_id": 46,
        "batch_number": "BATCH-002",
        "store": "Branch Store",
        "old_price": "3000.00",
        "new_price": "4000.00"
      },
      {
        "batch_id": 47,
        "batch_number": "BATCH-003",
        "store": "Main Store",
        "old_price": "2500.00",
        "new_price": "4000.00"
      }
    ]
  }
}
```

### Error Responses

#### Validation Error (422 Unprocessable Entity)

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "sell_price": [
      "The sell price field is required."
    ]
  }
}
```

#### Product Not Found (404 Not Found)

```json
{
  "success": false,
  "message": "Product not found"
}
```

#### No Batches Found (404 Not Found)

```json
{
  "success": false,
  "message": "No batches found for this product"
}
```

#### Server Error (500 Internal Server Error)

```json
{
  "success": false,
  "message": "Failed to update batch prices: [error details]"
}
```

---

## Frontend Integration Examples

### JavaScript (Fetch API)

```javascript
async function updateAllBatchPrices(productId, newPrice) {
  const token = localStorage.getItem('auth_token'); // Your auth token
  
  try {
    const response = await fetch(
      `http://your-api-domain.com/api/products/${productId}/batches/update-price`,
      {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify({
          sell_price: newPrice
        })
      }
    );

    const data = await response.json();

    if (response.ok && data.success) {
      console.log('✅ Success:', data.message);
      console.log(`Updated ${data.data.batches_updated} batches`);
      return data;
    } else {
      console.error('❌ Error:', data.message);
      throw new Error(data.message);
    }
  } catch (error) {
    console.error('❌ Request failed:', error);
    throw error;
  }
}

// Usage
updateAllBatchPrices(123, 4000.00)
  .then(result => {
    alert('All batch prices updated successfully!');
    // Refresh your product/batch list
  })
  .catch(error => {
    alert('Failed to update prices: ' + error.message);
  });
```

### JavaScript (Axios)

```javascript
import axios from 'axios';

async function updateAllBatchPrices(productId, newPrice) {
  try {
    const response = await axios.post(
      `/api/products/${productId}/batches/update-price`,
      {
        sell_price: newPrice
      },
      {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
        }
      }
    );

    if (response.data.success) {
      console.log('✅ Updated batches:', response.data.data.batches_updated);
      return response.data;
    }
  } catch (error) {
    if (error.response) {
      // Server responded with error status
      console.error('❌ Error:', error.response.data.message);
      throw new Error(error.response.data.message);
    } else {
      // Network or other error
      console.error('❌ Request failed:', error.message);
      throw error;
    }
  }
}

// Usage
updateAllBatchPrices(123, 4000.00)
  .then(result => {
    console.log('Success:', result.data);
  })
  .catch(error => {
    console.error('Failed:', error);
  });
```

### React Example with State Management

```jsx
import React, { useState } from 'react';
import axios from 'axios';

function BatchPriceUpdateForm({ productId, productName }) {
  const [newPrice, setNewPrice] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError('');
    setSuccess('');

    try {
      const response = await axios.post(
        `/api/products/${productId}/batches/update-price`,
        {
          sell_price: parseFloat(newPrice)
        },
        {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
          }
        }
      );

      if (response.data.success) {
        setSuccess(
          `Successfully updated ${response.data.data.batches_updated} batches to ৳${response.data.data.new_sell_price}`
        );
        setNewPrice('');
        
        // Optional: Call callback to refresh parent component
        // onSuccess && onSuccess(response.data);
      }
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to update prices');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="batch-price-update-form">
      <h3>Update All Batch Prices for: {productName}</h3>
      
      <form onSubmit={handleSubmit}>
        <div className="form-group">
          <label>New Selling Price (৳)</label>
          <input
            type="number"
            step="0.01"
            min="0"
            value={newPrice}
            onChange={(e) => setNewPrice(e.target.value)}
            placeholder="Enter new price"
            required
            disabled={loading}
          />
        </div>

        {error && <div className="alert alert-error">{error}</div>}
        {success && <div className="alert alert-success">{success}</div>}

        <button type="submit" disabled={loading}>
          {loading ? 'Updating...' : 'Update All Batch Prices'}
        </button>
      </form>
    </div>
  );
}

export default BatchPriceUpdateForm;
```

---

## UI/UX Recommendations

### Before Updating - Show Confirmation Dialog

```javascript
function showConfirmationDialog(productName, currentBatches, newPrice) {
  const message = `
    Are you sure you want to update ALL batches for "${productName}"?
    
    Current batches: ${currentBatches} batches with different prices
    New price: ৳${newPrice}
    
    This action will update all batches immediately.
  `;
  
  return confirm(message);
}
```

### After Update - Show Detailed Results

```javascript
function displayUpdateResults(data) {
  const { product_name, batches_updated, updates } = data.data;
  
  let message = `Successfully updated ${batches_updated} batches for ${product_name}\n\n`;
  
  updates.forEach(update => {
    message += `✓ ${update.batch_number} (${update.store}): ৳${update.old_price} → ৳${update.new_price}\n`;
  });
  
  alert(message);
  // Or display in a modal/toast notification
}
```

---

## Important Notes

### 1. **Immediate Effect**
- This update is **immediate** and affects ALL batches
- There is **no undo** - consider adding confirmation dialogs
- Stock quantities are NOT affected, only selling prices

### 2. **Batch Scope**
- Updates ALL batches for the given product across ALL stores
- If you need store-specific updates, use the individual batch update API instead

### 3. **Transaction Safety**
- All updates happen in a database transaction
- If any batch update fails, ALL changes are rolled back
- Ensures data consistency

### 4. **Authentication**
- Requires employee/admin authentication
- Use appropriate authorization headers

### 5. **Price Validation**
- Price must be numeric and ≥ 0
- Negative prices are not allowed
- Price is stored with 2 decimal places

---

## Testing the API

### Using Postman

1. **Set Request Type:** POST
2. **URL:** `http://your-api-domain.com/api/products/123/batches/update-price`
3. **Headers:**
   ```
   Content-Type: application/json
   Authorization: Bearer your_auth_token_here
   ```
4. **Body (raw JSON):**
   ```json
   {
     "sell_price": 4000.00
   }
   ```
5. **Send Request**

### Using cURL

```bash
curl -X POST \
  'http://your-api-domain.com/api/products/123/batches/update-price' \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer your_auth_token_here' \
  -d '{
    "sell_price": 4000.00
  }'
```

---

## Workflow Example

### Typical Use Case Flow

1. **User selects a product** from the product list
2. **System shows current batch prices:**
   - Batch A: ৳2000
   - Batch B: ৳3000
   - Batch C: ৳2500
3. **User clicks "Update All Prices"**
4. **Form appears** with input for new price
5. **User enters** ৳4000 and clicks submit
6. **Confirmation dialog** appears showing details
7. **User confirms**
8. **API call is made**
9. **Success response** shows all 3 batches updated
10. **UI refreshes** to show new prices

---

## Related APIs

If you need more granular control, consider these alternatives:

- `PUT /api/batches/{batch_id}` - Update single batch (including price)
- `GET /api/batches?product_id={id}` - Get all batches for a product
- `GET /api/batches/{batch_id}` - Get single batch details

---

## Support & Questions

For API issues or questions, contact:
- **Backend Team:** backend-team@example.com
- **Documentation:** See full API documentation at `/docs`

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | 2025-12-19 | Initial release |

---

**Happy Coding! 🚀**
