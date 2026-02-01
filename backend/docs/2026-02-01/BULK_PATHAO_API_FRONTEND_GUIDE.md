# Bulk Pathao Send API - Frontend Integration Guide

> **Date:** February 1, 2026  
> **Feature:** Queue-based bulk shipment sending to Pathao

---

## Overview

The new bulk send API uses **background queues** instead of synchronous processing. This means:
- ✅ Instant response (no timeout for large batches)
- ✅ Automatic retries on failure
- ✅ Real-time progress tracking
- ✅ Can process 100+ shipments reliably

---

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/shipments/bulk-send-to-pathao` | Start bulk send |
| `GET` | `/api/shipments/bulk-status/{batchCode}` | Check progress |
| `GET` | `/api/shipments/bulk-status/{batchCode}/details` | Get detailed results |
| `POST` | `/api/shipments/bulk-status/{batchCode}/cancel` | Cancel batch |
| `GET` | `/api/shipments/bulk-batches` | List recent batches |

---

## Step-by-Step Integration

### 1. Start Bulk Send

```javascript
const startBulkSend = async (shipmentIds) => {
  const response = await fetch('/api/shipments/bulk-send-to-pathao', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({
      shipment_ids: shipmentIds  // e.g., [1, 2, 3, 4, 5]
    })
  });
  
  return response.json();
};

// Response:
{
  "success": true,
  "message": "5 shipments queued for processing",
  "data": {
    "batch_code": "PB-20260201-ABC123",  // ← Save this!
    "batch_id": 42,
    "queued_count": 5,
    "immediate_failures": [],  // Shipments that failed validation
    "status_url": "/api/shipments/bulk-status/PB-20260201-ABC123"
  }
}
```

### 2. Poll for Progress

```javascript
const checkBatchStatus = async (batchCode) => {
  const response = await fetch(`/api/shipments/bulk-status/${batchCode}`, {
    headers: { 'Authorization': `Bearer ${token}` }
  });
  
  return response.json();
};

// Response:
{
  "success": true,
  "data": {
    "batch_code": "PB-20260201-ABC123",
    "status": "processing",  // pending | processing | completed | cancelled
    "total": 5,
    "processed": 3,
    "success": 2,
    "failed": 1,
    "pending": 2,
    "progress": 60.0,  // percentage
    "started_at": "2026-02-01T10:30:00.000Z",
    "completed_at": null
  }
}
```

### 3. Complete React Implementation

```jsx
import { useState, useEffect, useCallback } from 'react';

function BulkPathaoSender({ selectedShipmentIds }) {
  const [batchCode, setBatchCode] = useState(null);
  const [status, setStatus] = useState(null);
  const [isPolling, setIsPolling] = useState(false);

  // Start bulk send
  const handleBulkSend = async () => {
    try {
      const res = await fetch('/api/shipments/bulk-send-to-pathao', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        },
        body: JSON.stringify({ shipment_ids: selectedShipmentIds })
      });
      
      const data = await res.json();
      
      if (data.success) {
        setBatchCode(data.data.batch_code);
        setIsPolling(true);
        
        // Show immediate failures if any
        if (data.data.immediate_failures.length > 0) {
          alert(`${data.data.immediate_failures.length} shipments failed validation`);
        }
      } else {
        alert(data.message);
      }
    } catch (error) {
      console.error('Failed to start bulk send:', error);
    }
  };

  // Poll for status
  useEffect(() => {
    if (!isPolling || !batchCode) return;

    const pollInterval = setInterval(async () => {
      try {
        const res = await fetch(`/api/shipments/bulk-status/${batchCode}`, {
          headers: { 'Authorization': `Bearer ${localStorage.getItem('token')}` }
        });
        const data = await res.json();
        
        if (data.success) {
          setStatus(data.data);
          
          // Stop polling when complete
          if (data.data.status === 'completed' || data.data.status === 'cancelled') {
            setIsPolling(false);
          }
        }
      } catch (error) {
        console.error('Poll error:', error);
      }
    }, 2000); // Poll every 2 seconds

    return () => clearInterval(pollInterval);
  }, [isPolling, batchCode]);

  // Cancel batch
  const handleCancel = async () => {
    if (!batchCode) return;
    
    await fetch(`/api/shipments/bulk-status/${batchCode}/cancel`, {
      method: 'POST',
      headers: { 'Authorization': `Bearer ${localStorage.getItem('token')}` }
    });
  };

  return (
    <div>
      {!batchCode ? (
        <button onClick={handleBulkSend} disabled={selectedShipmentIds.length === 0}>
          Send {selectedShipmentIds.length} to Pathao
        </button>
      ) : (
        <div className="progress-container">
          <h3>Batch: {batchCode}</h3>
          
          {status && (
            <>
              <div className="progress-bar">
                <div style={{ width: `${status.progress}%` }} />
              </div>
              
              <p>
                Progress: {status.processed}/{status.total} 
                ({status.success} ✓, {status.failed} ✗)
              </p>
              
              <p>Status: <strong>{status.status}</strong></p>
              
              {status.status === 'processing' && (
                <button onClick={handleCancel}>Cancel</button>
              )}
              
              {status.status === 'completed' && (
                <p className="success">
                  ✅ Complete! {status.success} sent, {status.failed} failed
                </p>
              )}
            </>
          )}
        </div>
      )}
    </div>
  );
}

export default BulkPathaoSender;
```

---

## Get Detailed Results

After batch completes, get per-shipment results:

```javascript
const getDetails = async (batchCode) => {
  const res = await fetch(`/api/shipments/bulk-status/${batchCode}/details`);
  return res.json();
};

// Response:
{
  "success": true,
  "data": {
    "summary": { /* same as status */ },
    "results": [
      {
        "shipment_id": 1,
        "shipment_number": "SHP-20260201-001",
        "order_number": "ORD-001",
        "success": true,
        "message": "Sent to Pathao successfully",
        "consignment_id": "DX12345678",
        "processed_at": "2026-02-01T10:31:00.000Z"
      },
      {
        "shipment_id": 2,
        "shipment_number": "SHP-20260201-002",
        "order_number": "ORD-002",
        "success": false,
        "message": "Delivery address is empty",
        "consignment_id": null,
        "processed_at": "2026-02-01T10:31:05.000Z"
      }
    ]
  }
}
```

---

## List Recent Batches

```javascript
// GET /api/shipments/bulk-batches?status=processing&days=7

// Response:
{
  "success": true,
  "data": {
    "data": [
      { "batch_code": "PB-20260201-ABC123", "status": "completed", ... },
      { "batch_code": "PB-20260131-XYZ789", "status": "processing", ... }
    ],
    "current_page": 1,
    "total": 15
  }
}
```

---

## Sync Mode (Small Batches)

For small batches (<10), you can use sync mode to get instant results:

```javascript
const response = await fetch('/api/shipments/bulk-send-to-pathao', {
  method: 'POST',
  body: JSON.stringify({
    shipment_ids: [1, 2, 3],
    sync: true  // ← Synchronous mode
  })
});

// Returns immediately with results (old behavior)
{
  "success": true,
  "data": {
    "success": [...],
    "failed": [...]
  }
}
```

---

## Error Handling

### Immediate Validation Failures

These are returned instantly (before queueing):
- Shipment not in `pending` status
- Already has Pathao consignment ID
- Store not registered with Pathao

### Queue Processing Failures

These appear in detailed results after processing:
- Empty delivery address
- Pathao API errors
- Network timeouts (auto-retried 3x)

---

## Status Meanings

| Status | Description |
|--------|-------------|
| `pending` | Batch created, jobs not started |
| `processing` | Jobs are running |
| `completed` | All jobs finished |
| `cancelled` | Batch was cancelled |

---

## Tips

1. **Poll interval**: 2-3 seconds is good. Don't poll too fast.
2. **Show progress**: Users love seeing the progress bar move!
3. **Handle failures**: Show which shipments failed and why.
4. **Refresh list**: After batch completes, refresh shipment list to show new statuses.
