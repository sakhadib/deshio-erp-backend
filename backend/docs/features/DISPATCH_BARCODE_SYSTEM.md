# Product Dispatch Barcode System - Frontend Guide

**Created:** December 27, 2025  
**Version:** 2.0  
**Purpose:** Complete guide for implementing barcode-based dispatch sending and receiving

---

## Table of Contents

1. [Overview](#overview)
2. [Complete Dispatch Workflow](#complete-dispatch-workflow)
3. [API Endpoints Reference](#api-endpoints-reference)
4. [Frontend Implementation Examples](#frontend-implementation-examples)
5. [Common Use Cases](#common-use-cases)
6. [Error Handling](#error-handling)
7. [UI/UX Recommendations](#ui-ux-recommendations)

---

## Overview

### What This System Does

The dispatch system tracks **individual physical products** (via barcodes) as they move from one store to another. 

**Two-Way Barcode Scanning:**
- 🔵 **SENDING** - Source store scans each item before sending
- 🟢 **RECEIVING** - Destination store scans each item upon arrival

### Why Barcode Scanning?

- **Accountability**: Know exactly which units were sent and received
- **Loss Prevention**: Detect missing items immediately
- **Inventory Accuracy**: Real-time location tracking
- **Dispute Resolution**: Clear audit trail of who sent/received what

---

## Complete Dispatch Workflow

### Step-by-Step Process

```
┌─────────────────────────────────────────────────────────────────┐
│ 1. CREATE DISPATCH                                              │
│    Admin/Manager creates dispatch request                       │
│    POST /api/dispatches                                         │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ 2. ADD ITEMS TO DISPATCH                                        │
│    Specify products, batches, quantities                        │
│    POST /api/dispatches/{id}/items                             │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ 3. APPROVE DISPATCH                                             │
│    Manager approves the dispatch request                        │
│    PATCH /api/dispatches/{id}/approve                          │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ 4. START TRANSIT (Mark as Dispatched)                          │
│    Changes status from 'approved' to 'in_transit'              │
│    PATCH /api/dispatches/{id}/dispatch                         │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ 5. SCAN BARCODES AT SOURCE STORE 🔵                            │
│    Source store employee scans each physical item               │
│    POST /api/dispatches/{id}/items/{itemId}/scan-barcode       │
│    Repeat for EVERY item being sent                            │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ 6. SCAN BARCODES AT DESTINATION STORE 🟢                       │
│    Destination store employee scans each received item          │
│    POST /api/dispatches/{id}/items/{itemId}/receive-barcode    │
│    Repeat for EVERY item received                               │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ 7. COMPLETE DELIVERY                                            │
│    System validates all items sent = all items received        │
│    PATCH /api/dispatches/{id}/deliver                          │
│    → Updates inventory at both stores                           │
└─────────────────────────────────────────────────────────────────┘
```

### Dispatch Statuses

| Status | Description | Who Can Edit |
|--------|-------------|--------------|
| `draft` | Just created, not submitted | Creator |
| `pending_approval` | Waiting for manager approval | Manager |
| `approved` | Approved, ready to send | Source store |
| `in_transit` | Items being sent/received | Both stores |
| `delivered` | Completed successfully | Nobody (final) |
| `cancelled` | Cancelled before delivery | Manager |

---

## API Endpoints Reference

### 1. Create Dispatch

**POST** `/api/dispatches`

```json
{
  "source_store_id": 1,
  "destination_store_id": 2,
  "dispatch_date": "2025-12-27",
  "expected_delivery_date": "2025-12-28",
  "notes": "Urgent restock request"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Dispatch created successfully",
  "data": {
    "id": 123,
    "dispatch_number": "DSP-20251227-ABC123",
    "status": "draft",
    "source_store": { "id": 1, "name": "Main Store" },
    "destination_store": { "id": 2, "name": "Branch Store" }
  }
}
```

---

### 2. Add Items to Dispatch

**POST** `/api/dispatches/{id}/items`

```json
{
  "items": [
    {
      "product_batch_id": 45,
      "quantity": 10,
      "notes": "Handle with care"
    },
    {
      "product_batch_id": 67,
      "quantity": 5
    }
  ]
}
```

**Response:**
```json
{
  "success": true,
  "message": "Items added successfully",
  "data": {
    "items": [
      {
        "id": 501,
        "product_batch_id": 45,
        "product": {
          "id": 12,
          "name": "Premium Headphones",
          "sku": "TECH-001"
        },
        "quantity": 10,
        "unit_price": 1500.00
      }
    ]
  }
}
```

---

### 3. Approve Dispatch

**PATCH** `/api/dispatches/{id}/approve`

**Response:**
```json
{
  "success": true,
  "message": "Dispatch approved successfully",
  "data": {
    "id": 123,
    "status": "approved",
    "approved_by": {
      "id": 5,
      "name": "Manager Name"
    },
    "approved_at": "2025-12-27T10:30:00Z"
  }
}
```

---

### 4. Start Transit (Mark as Dispatched)

**PATCH** `/api/dispatches/{id}/dispatch`

**Response:**
```json
{
  "success": true,
  "message": "Dispatch marked as in transit",
  "data": {
    "id": 123,
    "status": "in_transit",
    "dispatched_at": "2025-12-27T11:00:00Z"
  }
}
```

---

### 5. 🔵 SCAN BARCODE AT SOURCE (Sending)

**POST** `/api/dispatches/{dispatchId}/items/{itemId}/scan-barcode`

**Request:**
```json
{
  "barcode": "BRC-20251227-001"
}
```

**Success Response:**
```json
{
  "success": true,
  "message": "Barcode scanned successfully. 3 of 10 items scanned.",
  "data": {
    "barcode": "BRC-20251227-001",
    "scanned_count": 3,
    "required_quantity": 10,
    "remaining_count": 7,
    "all_scanned": false,
    "scanned_at": "2025-12-27T11:05:00Z",
    "scanned_by": "John Doe"
  }
}
```

**Error Response (Wrong Product):**
```json
{
  "success": false,
  "message": "Barcode does not match the product for this dispatch item"
}
```

**Error Response (Already Scanned):**
```json
{
  "success": false,
  "message": "This barcode has already been scanned for this item"
}
```

**Error Response (Not at Source Store):**
```json
{
  "success": false,
  "message": "Barcode is not currently at the source store"
}
```

---

### 6. Get Scanned Barcodes (Sending Progress)

**GET** `/api/dispatches/{dispatchId}/items/{itemId}/scanned-barcodes`

**Response:**
```json
{
  "success": true,
  "data": {
    "dispatch_item_id": 501,
    "required_quantity": 10,
    "scanned_count": 3,
    "remaining_count": 7,
    "scanned_barcodes": [
      {
        "id": 1001,
        "barcode": "BRC-20251227-001",
        "product": {
          "id": 12,
          "name": "Premium Headphones"
        },
        "current_store": {
          "id": 1,
          "name": "Main Store"
        },
        "scanned_at": "2025-12-27T11:05:00Z",
        "scanned_by": "John Doe"
      }
    ]
  }
}
```

---

### 7. 🟢 RECEIVE BARCODE AT DESTINATION (Receiving)

**POST** `/api/dispatches/{dispatchId}/items/{itemId}/receive-barcode`

**Request:**
```json
{
  "barcode": "BRC-20251227-001"
}
```

**Success Response:**
```json
{
  "success": true,
  "message": "Barcode received successfully. 1 of 3 items received.",
  "data": {
    "barcode": "BRC-20251227-001",
    "received_count": 1,
    "total_sent": 3,
    "remaining_count": 2,
    "all_received": false,
    "received_at": "2025-12-27T14:30:00Z",
    "received_by": "Jane Smith",
    "current_store": {
      "id": 2,
      "name": "Branch Store"
    }
  }
}
```

**Error Response (Not in This Dispatch):**
```json
{
  "success": false,
  "message": "This barcode was not sent in this dispatch"
}
```

**Error Response (Already Received):**
```json
{
  "success": false,
  "message": "This barcode has already been received at destination"
}
```

---

### 8. Get Received Barcodes (Receiving Progress)

**GET** `/api/dispatches/{dispatchId}/items/{itemId}/received-barcodes`

**Response:**
```json
{
  "success": true,
  "data": {
    "dispatch_item_id": 501,
    "total_sent": 3,
    "received_count": 1,
    "pending_count": 2,
    "received_barcodes": [
      {
        "id": 1001,
        "barcode": "BRC-20251227-001",
        "received_at": "2025-12-27T14:30:00Z",
        "received_by_id": 8,
        "current_store": {
          "id": 2,
          "name": "Branch Store"
        }
      }
    ]
  }
}
```

---

### 9. Complete Delivery

**PATCH** `/api/dispatches/{id}/deliver`

**Request (Optional):**
```json
{
  "items": [
    {
      "item_id": 501,
      "received_quantity": 10,
      "damaged_quantity": 0,
      "missing_quantity": 0
    }
  ]
}
```

**Success Response:**
```json
{
  "success": true,
  "message": "Dispatch delivered successfully. Inventory movements have been processed.",
  "data": {
    "id": 123,
    "status": "delivered",
    "delivered_at": "2025-12-27T15:00:00Z"
  }
}
```

**Error Response (Items Not Sent):**
```json
{
  "success": false,
  "message": "Cannot deliver dispatch: Not all barcodes have been scanned at source",
  "items_with_missing_barcodes": [
    {
      "item_id": 501,
      "product": "Premium Headphones",
      "required": 10,
      "scanned": 7,
      "missing": 3
    }
  ]
}
```

**Error Response (Items Not Received):**
```json
{
  "success": false,
  "message": "Cannot deliver dispatch: Not all barcodes have been received at destination",
  "hint": "Use POST /api/dispatches/123/items/{itemId}/receive-barcode to scan and receive items",
  "items_with_pending_receipt": [
    {
      "item_id": 501,
      "product": "Premium Headphones",
      "sent": 10,
      "received": 7,
      "pending": 3
    }
  ]
}
```

---

## Frontend Implementation Examples

### React Component - Sending Barcode Scanner

```jsx
import React, { useState, useEffect } from 'react';
import axios from 'axios';
import BarcodeScanner from './BarcodeScanner'; // Your barcode scanner component

const DispatchSendingScanner = ({ dispatchId, itemId, onComplete }) => {
  const [progress, setProgress] = useState(null);
  const [scanning, setScanning] = useState(false);
  const [error, setError] = useState(null);

  useEffect(() => {
    fetchProgress();
  }, []);

  const fetchProgress = async () => {
    try {
      const response = await axios.get(
        `/api/dispatches/${dispatchId}/items/${itemId}/scanned-barcodes`,
        {
          headers: { Authorization: `Bearer ${localStorage.getItem('token')}` }
        }
      );
      setProgress(response.data.data);
    } catch (err) {
      console.error('Error fetching progress:', err);
    }
  };

  const handleBarcodeScan = async (barcode) => {
    if (scanning) return; // Prevent duplicate scans
    
    setScanning(true);
    setError(null);

    try {
      const response = await axios.post(
        `/api/dispatches/${dispatchId}/items/${itemId}/scan-barcode`,
        { barcode },
        {
          headers: { Authorization: `Bearer ${localStorage.getItem('token')}` }
        }
      );

      // Update progress
      setProgress({
        ...progress,
        scanned_count: response.data.data.scanned_count,
        remaining_count: response.data.data.remaining_count
      });

      // Success feedback
      showSuccessToast(response.data.message);

      // Check if all items scanned
      if (response.data.data.all_scanned) {
        showSuccessToast('All items scanned! Ready to send.');
        if (onComplete) onComplete();
      }

      // Refresh the list
      await fetchProgress();

    } catch (err) {
      setError(err.response?.data?.message || 'Failed to scan barcode');
      showErrorToast(err.response?.data?.message);
    } finally {
      setScanning(false);
    }
  };

  if (!progress) return <div>Loading...</div>;

  return (
    <div className="dispatch-sending-scanner">
      <div className="progress-header">
        <h3>🔵 Scan Items to Send</h3>
        <div className="progress-stats">
          <span className="scanned">{progress.scanned_count}</span>
          <span className="separator">/</span>
          <span className="total">{progress.required_quantity}</span>
          <span className="label">scanned</span>
        </div>
        <div className="progress-bar">
          <div 
            className="progress-fill" 
            style={{ 
              width: `${(progress.scanned_count / progress.required_quantity) * 100}%` 
            }}
          />
        </div>
      </div>

      {error && (
        <div className="error-message">
          ❌ {error}
        </div>
      )}

      <BarcodeScanner 
        onScan={handleBarcodeScan}
        disabled={scanning || progress.remaining_count === 0}
        placeholder="Scan barcode..."
      />

      <div className="scanned-list">
        <h4>Scanned Barcodes:</h4>
        {progress.scanned_barcodes.map((item, index) => (
          <div key={item.id} className="barcode-item">
            <span className="index">{index + 1}.</span>
            <span className="barcode">{item.barcode}</span>
            <span className="time">{new Date(item.scanned_at).toLocaleTimeString()}</span>
            <span className="status">✓</span>
          </div>
        ))}
      </div>
    </div>
  );
};

export default DispatchSendingScanner;
```

---

### React Component - Receiving Barcode Scanner

```jsx
import React, { useState, useEffect } from 'react';
import axios from 'axios';
import BarcodeScanner from './BarcodeScanner';

const DispatchReceivingScanner = ({ dispatchId, itemId, onComplete }) => {
  const [progress, setProgress] = useState(null);
  const [scanning, setScanning] = useState(false);
  const [error, setError] = useState(null);

  useEffect(() => {
    fetchProgress();
  }, []);

  const fetchProgress = async () => {
    try {
      const response = await axios.get(
        `/api/dispatches/${dispatchId}/items/${itemId}/received-barcodes`,
        {
          headers: { Authorization: `Bearer ${localStorage.getItem('token')}` }
        }
      );
      setProgress(response.data.data);
    } catch (err) {
      console.error('Error fetching progress:', err);
    }
  };

  const handleBarcodeScan = async (barcode) => {
    if (scanning) return;
    
    setScanning(true);
    setError(null);

    try {
      const response = await axios.post(
        `/api/dispatches/${dispatchId}/items/${itemId}/receive-barcode`,
        { barcode },
        {
          headers: { Authorization: `Bearer ${localStorage.getItem('token')}` }
        }
      );

      // Update progress
      setProgress({
        ...progress,
        received_count: response.data.data.received_count,
        pending_count: response.data.data.remaining_count
      });

      // Success feedback
      showSuccessToast(response.data.message);

      // Check if all items received
      if (response.data.data.all_received) {
        showSuccessToast('All items received! Ready to complete delivery.');
        if (onComplete) onComplete();
      }

      // Refresh the list
      await fetchProgress();

    } catch (err) {
      setError(err.response?.data?.message || 'Failed to receive barcode');
      showErrorToast(err.response?.data?.message);
    } finally {
      setScanning(false);
    }
  };

  if (!progress) return <div>Loading...</div>;

  return (
    <div className="dispatch-receiving-scanner">
      <div className="progress-header">
        <h3>🟢 Scan Items Received</h3>
        <div className="progress-stats">
          <span className="received">{progress.received_count}</span>
          <span className="separator">/</span>
          <span className="total">{progress.total_sent}</span>
          <span className="label">received</span>
        </div>
        <div className="progress-bar">
          <div 
            className="progress-fill green" 
            style={{ 
              width: `${(progress.received_count / progress.total_sent) * 100}%` 
            }}
          />
        </div>
        {progress.pending_count > 0 && (
          <div className="warning-message">
            ⚠️ {progress.pending_count} item(s) still in transit
          </div>
        )}
      </div>

      {error && (
        <div className="error-message">
          ❌ {error}
        </div>
      )}

      <BarcodeScanner 
        onScan={handleBarcodeScan}
        disabled={scanning || progress.pending_count === 0}
        placeholder="Scan received barcode..."
      />

      <div className="received-list">
        <h4>Received Barcodes:</h4>
        {progress.received_barcodes.map((item, index) => (
          <div key={item.id} className="barcode-item">
            <span className="index">{index + 1}.</span>
            <span className="barcode">{item.barcode}</span>
            <span className="time">{new Date(item.received_at).toLocaleTimeString()}</span>
            <span className="status">✓ Received</span>
          </div>
        ))}
      </div>
    </div>
  );
};

export default DispatchReceivingScanner;
```

---

### Complete Dispatch Page

```jsx
import React, { useState, useEffect } from 'react';
import axios from 'axios';
import DispatchSendingScanner from './DispatchSendingScanner';
import DispatchReceivingScanner from './DispatchReceivingScanner';

const DispatchDetailsPage = ({ dispatchId }) => {
  const [dispatch, setDispatch] = useState(null);
  const [currentStore, setCurrentStore] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchDispatchDetails();
    fetchCurrentUser();
  }, [dispatchId]);

  const fetchDispatchDetails = async () => {
    try {
      const response = await axios.get(`/api/dispatches/${dispatchId}`, {
        headers: { Authorization: `Bearer ${localStorage.getItem('token')}` }
      });
      setDispatch(response.data.data);
    } catch (err) {
      console.error('Error fetching dispatch:', err);
    } finally {
      setLoading(false);
    }
  };

  const fetchCurrentUser = async () => {
    try {
      const response = await axios.get('/api/auth/me', {
        headers: { Authorization: `Bearer ${localStorage.getItem('token')}` }
      });
      setCurrentStore(response.data.data.store_id);
    } catch (err) {
      console.error('Error fetching user:', err);
    }
  };

  const handleCompleteDelivery = async () => {
    if (!confirm('Complete this delivery? This will update inventory at both stores.')) {
      return;
    }

    try {
      await axios.patch(`/api/dispatches/${dispatchId}/deliver`, {}, {
        headers: { Authorization: `Bearer ${localStorage.getItem('token')}` }
      });
      
      showSuccessToast('Delivery completed successfully!');
      fetchDispatchDetails(); // Refresh
    } catch (err) {
      const errorMsg = err.response?.data?.message || 'Failed to complete delivery';
      showErrorToast(errorMsg);
      
      // Show detailed error if available
      if (err.response?.data?.items_with_pending_receipt) {
        alert('Some items have not been received yet. Please scan all items first.');
      }
    }
  };

  if (loading) return <div>Loading...</div>;
  if (!dispatch) return <div>Dispatch not found</div>;

  const isSourceStore = currentStore === dispatch.source_store.id;
  const isDestinationStore = currentStore === dispatch.destination_store.id;

  return (
    <div className="dispatch-details-page">
      <div className="dispatch-header">
        <h1>Dispatch {dispatch.dispatch_number}</h1>
        <div className="status-badge" data-status={dispatch.status}>
          {dispatch.status.replace('_', ' ').toUpperCase()}
        </div>
      </div>

      <div className="dispatch-info">
        <div className="info-row">
          <span>From:</span>
          <strong>{dispatch.source_store.name}</strong>
        </div>
        <div className="info-row">
          <span>To:</span>
          <strong>{dispatch.destination_store.name}</strong>
        </div>
        <div className="info-row">
          <span>Expected Delivery:</span>
          <strong>{new Date(dispatch.expected_delivery_date).toLocaleDateString()}</strong>
        </div>
      </div>

      {/* Show appropriate scanner based on user's store */}
      {dispatch.status === 'in_transit' && (
        <div className="scanning-section">
          {isSourceStore && (
            <div className="source-scanning">
              <h2>Send Items</h2>
              {dispatch.items.map(item => (
                <div key={item.id} className="dispatch-item-card">
                  <h3>{item.product.name}</h3>
                  <p>Quantity: {item.quantity}</p>
                  <DispatchSendingScanner
                    dispatchId={dispatchId}
                    itemId={item.id}
                    onComplete={() => fetchDispatchDetails()}
                  />
                </div>
              ))}
            </div>
          )}

          {isDestinationStore && (
            <div className="destination-scanning">
              <h2>Receive Items</h2>
              {dispatch.items.map(item => (
                <div key={item.id} className="dispatch-item-card">
                  <h3>{item.product.name}</h3>
                  <p>Quantity Expected: {item.quantity}</p>
                  <DispatchReceivingScanner
                    dispatchId={dispatchId}
                    itemId={item.id}
                    onComplete={() => fetchDispatchDetails()}
                  />
                </div>
              ))}
              
              <button 
                className="btn-complete-delivery"
                onClick={handleCompleteDelivery}
              >
                Complete Delivery
              </button>
            </div>
          )}
        </div>
      )}
    </div>
  );
};

export default DispatchDetailsPage;
```

---

## Common Use Cases

### Use Case 1: Quick Dispatch (Same Day Delivery)

```javascript
// 1. Create and immediately approve
const createQuickDispatch = async (sourceStoreId, destStoreId, items) => {
  // Create
  const dispatch = await axios.post('/api/dispatches', {
    source_store_id: sourceStoreId,
    destination_store_id: destStoreId,
    dispatch_date: new Date().toISOString().split('T')[0],
    expected_delivery_date: new Date().toISOString().split('T')[0]
  });

  const dispatchId = dispatch.data.data.id;

  // Add items
  await axios.post(`/api/dispatches/${dispatchId}/items`, { items });

  // Approve
  await axios.patch(`/api/dispatches/${dispatchId}/approve`);

  // Start transit
  await axios.patch(`/api/dispatches/${dispatchId}/dispatch`);

  return dispatchId;
};
```

### Use Case 2: Batch Barcode Scanning with Audio Feedback

```javascript
const scanBarcodeWithFeedback = async (dispatchId, itemId, barcode) => {
  try {
    const response = await axios.post(
      `/api/dispatches/${dispatchId}/items/${itemId}/scan-barcode`,
      { barcode }
    );

    // Success sound
    playSound('beep-success.mp3');
    
    // Visual feedback
    showGreenFlash();
    
    return response.data;
  } catch (error) {
    // Error sound
    playSound('error.mp3');
    
    // Visual feedback
    showRedFlash();
    
    throw error;
  }
};

const playSound = (filename) => {
  const audio = new Audio(`/sounds/${filename}`);
  audio.play();
};

const showGreenFlash = () => {
  document.body.style.backgroundColor = '#00ff00';
  setTimeout(() => {
    document.body.style.backgroundColor = '';
  }, 200);
};

const showRedFlash = () => {
  document.body.style.backgroundColor = '#ff0000';
  setTimeout(() => {
    document.body.style.backgroundColor = '';
  }, 200);
};
```

### Use Case 3: Handle Damaged/Missing Items

```javascript
const completeDeliveryWithIssues = async (dispatchId, items) => {
  // items = [{ item_id, received_quantity, damaged_quantity, missing_quantity }]
  
  try {
    const response = await axios.patch(
      `/api/dispatches/${dispatchId}/deliver`,
      { items }
    );
    
    return response.data;
  } catch (error) {
    // Handle validation errors
    if (error.response?.data?.items_with_pending_receipt) {
      alert('Please scan all items before completing delivery');
    }
    throw error;
  }
};
```

---

## Error Handling

### Common Errors and Solutions

| Error Message | Cause | Solution |
|--------------|-------|----------|
| "Barcode not found in system" | Invalid/unregistered barcode | Verify barcode is correct |
| "Barcode does not match the product" | Wrong product scanned | Check dispatch item details |
| "Barcode is not currently at the source store" | Item at wrong location | Verify inventory location |
| "This barcode has already been scanned" | Duplicate scan | Skip and scan next item |
| "This barcode was not sent in this dispatch" | Receiving wrong item | Check if item belongs to different dispatch |
| "Cannot deliver: Not all barcodes scanned at source" | Incomplete sending | Scan remaining items at source |
| "Cannot deliver: Not all barcodes received at destination" | Incomplete receiving | Scan remaining items at destination |

### Error Handling Pattern

```javascript
const handleDispatchError = (error) => {
  if (!error.response) {
    return 'Network error. Please check your connection.';
  }

  const status = error.response.status;
  const data = error.response.data;

  switch (status) {
    case 404:
      return 'Dispatch or item not found';
    
    case 422:
      // Validation error - show specific message
      if (data.items_with_missing_barcodes) {
        return `${data.message}\n\nMissing scans:\n${
          data.items_with_missing_barcodes
            .map(item => `- ${item.product}: ${item.missing} of ${item.required}`)
            .join('\n')
        }`;
      }
      
      if (data.items_with_pending_receipt) {
        return `${data.message}\n\nPending receipt:\n${
          data.items_with_pending_receipt
            .map(item => `- ${item.product}: ${item.pending} items`)
            .join('\n')
        }`;
      }
      
      return data.message;
    
    case 401:
      // Redirect to login
      window.location.href = '/login';
      return 'Session expired. Please login again.';
    
    default:
      return data.message || 'An error occurred';
  }
};
```

---

## UI/UX Recommendations

### 1. Scanning Interface

✅ **DO:**
- Use large, clear text for progress (3/10)
- Show visual progress bar
- Provide audio feedback (beep on success, buzz on error)
- Auto-focus on barcode input field
- Clear previous error message on new scan
- Show list of already-scanned barcodes

❌ **DON'T:**
- Don't allow scanning when status is wrong
- Don't show technical error details to warehouse staff
- Don't require manual input if barcode scanner available

### 2. Progress Indicators

```jsx
const ProgressDisplay = ({ current, total }) => {
  const percentage = (current / total) * 100;
  const isComplete = current === total;

  return (
    <div className="progress-display">
      <div className="progress-numbers">
        <span className={`current ${isComplete ? 'complete' : ''}`}>
          {current}
        </span>
        <span className="separator">/</span>
        <span className="total">{total}</span>
      </div>
      
      <div className="progress-bar">
        <div 
          className={`progress-fill ${isComplete ? 'complete' : ''}`}
          style={{ width: `${percentage}%` }}
        />
      </div>
      
      {isComplete && (
        <div className="complete-badge">
          ✓ All items scanned
        </div>
      )}
    </div>
  );
};
```

### 3. Mobile-Friendly Design

```css
/* Optimized for handheld barcode scanners */
.barcode-scanner-page {
  font-size: 18px; /* Larger text for readability */
  padding: 20px;
}

.barcode-input {
  font-size: 24px;
  padding: 15px;
  border: 3px solid #ccc;
  border-radius: 8px;
  width: 100%;
}

.progress-numbers {
  font-size: 48px; /* Extra large for visibility */
  font-weight: bold;
  text-align: center;
}

.success-message {
  background: #00ff00;
  color: #000;
  padding: 20px;
  font-size: 20px;
  text-align: center;
  animation: flash 0.3s;
}

.error-message {
  background: #ff0000;
  color: #fff;
  padding: 20px;
  font-size: 20px;
  text-align: center;
  animation: shake 0.3s;
}

@keyframes flash {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-10px); }
  75% { transform: translateX(10px); }
}
```

### 4. Keyboard Shortcuts

```javascript
// For desktop barcode scanners that act as keyboard
useEffect(() => {
  let buffer = '';
  let timeout;

  const handleKeyPress = (e) => {
    // Most barcode scanners send Enter after scanning
    if (e.key === 'Enter' && buffer.length > 0) {
      handleBarcodeScan(buffer);
      buffer = '';
    } else if (e.key.length === 1) {
      // Accumulate characters
      buffer += e.key;
      
      // Reset buffer after 100ms of no input
      clearTimeout(timeout);
      timeout = setTimeout(() => {
        buffer = '';
      }, 100);
    }
  };

  window.addEventListener('keypress', handleKeyPress);
  return () => window.removeEventListener('keypress', handleKeyPress);
}, []);
```

---

## Testing Checklist

### Before Going Live

- [ ] Test scanning at source store
- [ ] Test receiving at destination store
- [ ] Test with wrong barcode (should show error)
- [ ] Test with duplicate barcode (should show error)
- [ ] Test completing delivery before all scanned (should show error)
- [ ] Test completing delivery before all received (should show error)
- [ ] Test audio feedback works
- [ ] Test on mobile devices
- [ ] Test with actual barcode scanner hardware
- [ ] Test network error handling
- [ ] Test session timeout handling

---

## Frequently Asked Questions

**Q: What if we lose internet connection while scanning?**  
A: Store scans locally and sync when connection restored. Implement offline queue.

**Q: Can we skip barcode scanning for urgent dispatches?**  
A: No. The system requires barcode scanning for inventory accuracy.

**Q: What if a barcode is damaged and can't be scanned?**  
A: Manual entry option should be available, but requires manager approval.

**Q: Can we receive items before they're all sent?**  
A: Yes! Receiving can start as soon as items are marked in_transit.

**Q: What happens if we receive wrong items?**  
A: System will show error "This barcode was not sent in this dispatch".

**Q: How do we handle damaged items?**  
A: When completing delivery, specify damaged_quantity for each item.

---

## Support

For backend API issues, contact the backend team.  
For scanner hardware issues, contact IT support.

**Last Updated:** December 27, 2025
