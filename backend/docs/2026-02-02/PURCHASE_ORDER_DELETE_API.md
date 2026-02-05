# Purchase Order Hard Delete API

> **Date:** February 2, 2026  
> **Feature:** Safe permanent deletion of Purchase Orders

---

## New Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `DELETE` | `/api/purchase-orders/{id}` | Hard delete a PO |
| `GET` | `/api/purchase-orders/{id}/can-delete` | Check if PO can be deleted |

---

## Safety Rules

A Purchase Order can **ONLY** be deleted if:

| ✅ Allowed | ❌ Blocked |
|-----------|-----------|
| Status: `draft`, `approved`, `cancelled` | Status: `received`, `partially_received` |
| No payments made | Has vendor payments |
| No items received | Items have been received into stock |

---

## 1. Check Before Delete (Recommended)

Call this first to show user what's blocking deletion:

```javascript
// GET /api/purchase-orders/123/can-delete

const response = await fetch(`/api/purchase-orders/${poId}/can-delete`, {
  headers: { 'Authorization': `Bearer ${token}` }
});
const data = await response.json();

// Response when CAN delete:
{
  "success": true,
  "data": {
    "can_delete": true,
    "po_number": "PO-20260202-000001",
    "vendor_name": "ABC Supplier",
    "status": "draft",
    "total_amount": "15000.00",
    "items_count": 3,
    "blockers": []
  }
}

// Response when CANNOT delete:
{
  "success": true,
  "data": {
    "can_delete": false,
    "po_number": "PO-20260202-000001",
    "vendor_name": "ABC Supplier",
    "status": "partially_received",
    "total_amount": "15000.00",
    "items_count": 3,
    "blockers": [
      {
        "type": "status",
        "message": "Purchase order has status 'partially_received' - items have been received"
      },
      {
        "type": "payments",
        "message": "Purchase order has 2 payment(s) totaling 5000.00",
        "details": {
          "payment_count": 2,
          "total_paid": "5000.00"
        }
      }
    ]
  }
}
```

---

## 2. Delete the PO

```javascript
// DELETE /api/purchase-orders/123

const deletePO = async (poId) => {
  const response = await fetch(`/api/purchase-orders/${poId}`, {
    method: 'DELETE',
    headers: { 'Authorization': `Bearer ${token}` }
  });
  
  return response.json();
};

// Success Response (200):
{
  "success": true,
  "message": "Purchase order PO-20260202-000001 has been permanently deleted",
  "data": {
    "id": 123,
    "po_number": "PO-20260202-000001",
    "vendor_name": "ABC Supplier",
    "total_amount": "15000.00",
    "status": "draft",
    "items_count": 3
  }
}

// Error Response (422) - Has Payments:
{
  "success": false,
  "message": "Cannot delete purchase order with existing payments. Found 2 payment(s) totaling 5000.00",
  "error_code": "PO_HAS_PAYMENTS",
  "data": {
    "payment_count": 2,
    "total_paid": "5000.00"
  }
}

// Error Response (422) - Received Items:
{
  "success": false,
  "message": "Cannot delete purchase order with received inventory. Some items have been received into stock.",
  "error_code": "PO_ITEMS_RECEIVED",
  "data": {
    "received_items": [
      {
        "item_id": 45,
        "product_name": "Widget A",
        "quantity_received": 50
      }
    ]
  }
}

// Error Response (422) - Wrong Status:
{
  "success": false,
  "message": "Cannot delete purchase order that has received items. Status: received",
  "error_code": "PO_RECEIVED"
}
```

---

## 3. React Integration Example

```jsx
import { useState } from 'react';

function DeletePOButton({ purchaseOrder, onDeleted }) {
  const [loading, setLoading] = useState(false);
  const [blockers, setBlockers] = useState(null);

  const handleDelete = async () => {
    setLoading(true);
    
    // Step 1: Check if can delete
    const checkRes = await fetch(`/api/purchase-orders/${purchaseOrder.id}/can-delete`, {
      headers: { 'Authorization': `Bearer ${localStorage.getItem('token')}` }
    });
    const checkData = await checkRes.json();
    
    if (!checkData.data.can_delete) {
      setBlockers(checkData.data.blockers);
      setLoading(false);
      return;
    }
    
    // Step 2: Confirm with user
    const confirmed = window.confirm(
      `Are you sure you want to permanently delete ${purchaseOrder.po_number}?\n\n` +
      `This will delete the PO and all ${checkData.data.items_count} items.\n\n` +
      `This action cannot be undone!`
    );
    
    if (!confirmed) {
      setLoading(false);
      return;
    }
    
    // Step 3: Delete
    const deleteRes = await fetch(`/api/purchase-orders/${purchaseOrder.id}`, {
      method: 'DELETE',
      headers: { 'Authorization': `Bearer ${localStorage.getItem('token')}` }
    });
    const deleteData = await deleteRes.json();
    
    setLoading(false);
    
    if (deleteData.success) {
      alert(`Deleted: ${deleteData.data.po_number}`);
      onDeleted(purchaseOrder.id);
    } else {
      alert(`Error: ${deleteData.message}`);
    }
  };

  return (
    <div>
      <button 
        onClick={handleDelete} 
        disabled={loading}
        className="btn-danger"
      >
        {loading ? 'Checking...' : 'Delete PO'}
      </button>
      
      {blockers && blockers.length > 0 && (
        <div className="blockers-warning">
          <strong>Cannot delete:</strong>
          <ul>
            {blockers.map((b, i) => (
              <li key={i}>{b.message}</li>
            ))}
          </ul>
        </div>
      )}
    </div>
  );
}
```

---

## Error Codes Reference

| Code | Meaning | User Action |
|------|---------|-------------|
| `PO_RECEIVED` | PO status is received/partially_received | Cannot delete - items in stock |
| `PO_HAS_PAYMENTS` | Vendor payments exist | Refund/void payments first |
| `PO_ITEMS_RECEIVED` | Some items received into inventory | Cannot delete - affects stock |

---

## Notes

- **This is a HARD DELETE** - permanently removes from database
- All PO items are deleted along with the PO
- Activity logs will still show the deletion occurred
- Use `cancel` endpoint instead if you want to keep history
