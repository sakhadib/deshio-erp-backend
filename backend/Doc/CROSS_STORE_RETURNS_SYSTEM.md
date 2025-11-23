# Cross-Store Returns System Documentation

## Overview

The Cross-Store Returns System allows customers to return products to any store location, regardless of where the product was originally purchased. The system automatically handles inventory management, batch creation, barcode tracking, and movement logging across different store locations.

## Key Features

- ✅ **Multi-Store Returns**: Products can be returned to any store, not just the original purchase location
- ✅ **Automatic Inventory Management**: Inventory is automatically adjusted for the receiving store
- ✅ **Smart Batch Management**: System intelligently creates or finds appropriate product batches
- ✅ **Barcode Location Tracking**: Individual product barcodes are updated with new store locations
- ✅ **Comprehensive Audit Trail**: All cross-store movements are logged for compliance
- ✅ **Backward Compatible**: Existing return workflows continue to work without changes

## API Endpoints

### Creating a Return (POST /api/returns)

The existing return creation endpoint now accepts an optional `received_at_store_id` parameter.

**Request Format:**
```json
{
  "order_id": 123,
  "return_items": [
    {
      "order_item_id": 456,
      "quantity": 2,
      "return_reason": "defective"
    }
  ],
  "received_at_store_id": 5,  // Optional: Store where return was physically received
  "notes": "Customer returned to different store location"
}
```

**Field Descriptions:**
- `received_at_store_id` *(optional)*: The store ID where the return was physically received
- If not provided, defaults to the original purchase store
- Must be a valid store ID that exists in the system

**Response (Success - 201 Created):**
```json
{
  "success": true,
  "message": "Return created successfully",
  "data": {
    "id": 789,
    "return_number": "RET-2025-001234",
    "order_id": 123,
    "store_id": 3,              // Original purchase store
    "received_at_store_id": 5,  // Store where return was received
    "status": "pending",
    "total_amount": 45.98,
    "return_items": [...],
    "created_at": "2025-01-19T10:30:00Z"
  }
}
```

### Processing a Return (POST /api/returns/{id}/process)

The processing endpoint automatically handles cross-store inventory management.

**Request Format:**
```json
{
  "action": "approve"  // or "reject"
}
```

**Cross-Store Processing Logic:**

1. **Same Store Return** (received_at_store_id == order.store_id):
   - Restores inventory to original store
   - Standard batch and barcode updates

2. **Cross Store Return** (received_at_store_id != order.store_id):
   - Creates/finds batch for receiving store
   - Updates barcode locations to new store
   - Logs cross-store movement

**Response (Success - 200 OK):**
```json
{
  "success": true,
  "message": "Return processed successfully",
  "data": {
    "id": 789,
    "status": "completed",
    "cross_store_return": true,  // Indicates if this was a cross-store return
    "inventory_updates": [
      {
        "store_id": 5,
        "product_id": 123,
        "batch_number": "BATCH-2025-001",
        "quantity_added": 2
      }
    ],
    "processed_at": "2025-01-19T11:15:00Z"
  }
}
```

## Frontend Implementation Guide

### Store Selection UI

When creating a return, provide a store selector for the receiving location:

```javascript
// Example React component structure
const ReturnForm = ({ orderData }) => {
  const [receivedAtStore, setReceivedAtStore] = useState(null);
  const [stores] = useState(getAvailableStores()); // Your store list

  return (
    <form onSubmit={handleSubmit}>
      {/* Existing return form fields */}
      
      <div className="store-selection">
        <label>Return Received At Store:</label>
        <select 
          value={receivedAtStore} 
          onChange={(e) => setReceivedAtStore(e.target.value)}
        >
          <option value="">Select receiving store...</option>
          {stores.map(store => (
            <option key={store.id} value={store.id}>
              {store.name} - {store.address}
            </option>
          ))}
        </select>
        <small className="help-text">
          Leave blank if returned to original purchase store
        </small>
      </div>
      
      <button type="submit">Create Return</button>
    </form>
  );
};
```

### API Request Implementation

```javascript
const createReturn = async (returnData) => {
  try {
    const payload = {
      order_id: returnData.orderId,
      return_items: returnData.items,
      notes: returnData.notes
    };
    
    // Only include received_at_store_id if different from purchase store
    if (returnData.receivedAtStoreId && 
        returnData.receivedAtStoreId !== returnData.originalStoreId) {
      payload.received_at_store_id = parseInt(returnData.receivedAtStoreId);
    }
    
    const response = await fetch('/api/returns', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${authToken}`
      },
      body: JSON.stringify(payload)
    });
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const result = await response.json();
    return result;
    
  } catch (error) {
    console.error('Error creating return:', error);
    throw error;
  }
};
```

### Cross-Store Return Indicators

Show visual indicators when a return involves multiple stores:

```javascript
const ReturnCard = ({ returnData }) => {
  const isCrossStore = returnData.received_at_store_id !== returnData.store_id;
  
  return (
    <div className={`return-card ${isCrossStore ? 'cross-store' : ''}`}>
      <h3>Return #{returnData.return_number}</h3>
      
      {isCrossStore && (
        <div className="cross-store-badge">
          <span className="badge cross-store">Cross-Store Return</span>
          <div className="store-info">
            <div>Purchased at: {returnData.store.name}</div>
            <div>Returned to: {returnData.received_at_store.name}</div>
          </div>
        </div>
      )}
      
      <div className="return-details">
        {/* Rest of return information */}
      </div>
    </div>
  );
};
```

## Error Handling

### Common Validation Errors

**Invalid Store ID:**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "received_at_store_id": ["The selected store is invalid."]
  }
}
```

**Store Not Found:**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "received_at_store_id": ["The specified store does not exist."]
  }
}
```

### Error Handling Implementation

```javascript
const handleReturnCreation = async (formData) => {
  try {
    const result = await createReturn(formData);
    
    // Success handling
    showSuccessMessage('Return created successfully');
    redirectToReturnDetails(result.data.id);
    
  } catch (error) {
    if (error.status === 422) {
      // Validation errors
      const errors = await error.response.json();
      displayValidationErrors(errors.errors);
    } else if (error.status === 404) {
      showErrorMessage('Store not found. Please select a valid store.');
    } else {
      showErrorMessage('An unexpected error occurred. Please try again.');
    }
  }
};
```

## Database Schema

### ProductReturns Table Structure
```sql
-- New field added to existing table
ALTER TABLE product_returns ADD COLUMN received_at_store_id bigint unsigned nullable;
ALTER TABLE product_returns ADD CONSTRAINT fk_product_returns_received_at_store 
  FOREIGN KEY (received_at_store_id) REFERENCES stores(id);
```

### Related Tables
- `stores` - Store locations and details
- `product_batches` - Store-specific inventory batches  
- `product_barcodes` - Individual product tracking
- `product_movements` - Inventory movement audit trail

## Business Logic Flow

### Cross-Store Return Processing

1. **Validation Phase:**
   - Verify return items and quantities
   - Validate received_at_store_id if provided
   - Check store permissions and accessibility

2. **Inventory Detection:**
   - Compare received_at_store_id with original order.store_id
   - Determine if cross-store processing is needed

3. **Batch Management:**
   - Find existing batch for product at receiving store
   - Create new batch if none exists
   - Use consistent batch numbering format

4. **Barcode Updates:**
   - Update current_store_id for returned items
   - Add location change metadata
   - Maintain barcode history trail

5. **Movement Logging:**
   - Log movement type as 'cross_store_return'
   - Record from_store_id and to_store_id
   - Track quantities and timestamps

## Testing Scenarios

### Test Cases to Validate

1. **Same Store Return:**
   - Create return without received_at_store_id
   - Verify inventory restored to original store

2. **Cross Store Return:**
   - Create return with different received_at_store_id  
   - Verify batch creation at receiving store
   - Confirm barcode location updates

3. **Invalid Store ID:**
   - Test with non-existent store ID
   - Verify proper error response

4. **Edge Cases:**
   - Empty received_at_store_id (should default)
   - Same store explicitly specified
   - Multiple items with different products

## Performance Considerations

- Batch creation uses `firstOrCreate()` to prevent duplicates
- Barcode updates are batched for efficiency
- Movement logging is asynchronous where possible
- Consider indexing on `received_at_store_id` for large datasets

## Security Notes

- Validate user permissions for target stores
- Ensure staff can only process returns for authorized locations  
- Log all cross-store activities for audit compliance
- Verify store accessibility based on user roles

## Migration Notes

- **Backward Compatible**: Existing returns continue to work
- **Default Behavior**: Omitting received_at_store_id defaults to original store
- **Data Migration**: No existing data changes required
- **Rollback Safe**: Can remove received_at_store_id without data loss

---

## Support

For technical questions or issues with the cross-store returns system:

1. Check validation errors in API responses
2. Verify store IDs are valid and accessible
3. Confirm user permissions for target stores
4. Review application logs for processing errors

**Last Updated:** January 19, 2025  
**Version:** 1.0  
**Compatible with:** Laravel 11.x, ERP Backend v2.0+