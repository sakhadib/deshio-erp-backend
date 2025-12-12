# Order Update API Documentation

## Overview
This document describes the **Order Update APIs** for both **Employee** and **Customer** sides. These endpoints allow authorized users to modify order details before fulfillment/shipment.

---

## Key Concepts

### Update Restrictions
- **Only pending/unprocessed orders** can be updated
- **After fulfillment begins**, most fields become read-only
- **Items cannot be edited** through update API (use dedicated item endpoints)
- **Different permissions** for employees vs customers

### Allowed Statuses for Updates
Orders can be updated when in these statuses:
- `pending` - Order placed but not confirmed
- `confirmed` - Order confirmed by system/employee
- `assigned_to_store` - Store assigned but not picked yet
- `picking` - Items being collected (limited updates only)

### Cannot Update When
- `ready_for_shipment` - Already packaged
- `shipped` - In transit
- `delivered` - Completed
- `cancelled` - Order cancelled

---

## Employee Side API

### Endpoint
```http
PUT    /api/orders/{id}
PATCH  /api/orders/{id}
```

### Authentication
- **Required**: Employee JWT token
- **Header**: `Authorization: Bearer {employee_token}`

### Permissions
Employees can update:
- ✅ Customer information (name, phone, email, address)
- ✅ Shipping address
- ✅ Discount amount
- ✅ Shipping amount
- ✅ Notes/instructions
- ❌ Items (use `/orders/{id}/items` endpoints)
- ❌ Order type
- ❌ Status transitions (use lifecycle endpoints)

### Request Body
```json
{
  "customer_name": "Updated Customer Name",
  "customer_phone": "01712345678",
  "customer_email": "updated@example.com",
  "customer_address": "123 New Street, Dhaka",
  "shipping_address": {
    "address_line1": "House 45, Road 12",
    "address_line2": "Banani",
    "city": "Dhaka",
    "state": "Dhaka Division",
    "postal_code": "1213",
    "country": "Bangladesh"
  },
  "discount_amount": 50.00,
  "shipping_amount": 100.00,
  "notes": "Updated delivery instructions"
}
```

### Validation Rules

| Field | Type | Rules | Notes |
|-------|------|-------|-------|
| `customer_name` | string | optional, max:255 | Updates customer record |
| `customer_phone` | string | optional, max:20 | Updates customer record |
| `customer_email` | email | optional | Updates customer record |
| `customer_address` | string | optional | Updates customer record |
| `shipping_address` | object | optional | Must include required fields if provided |
| `shipping_address.address_line1` | string | required_with:shipping_address | Primary address |
| `shipping_address.address_line2` | string | optional | Secondary address |
| `shipping_address.city` | string | required_with:shipping_address | City name |
| `shipping_address.state` | string | optional | State/division |
| `shipping_address.postal_code` | string | optional | Postal code |
| `shipping_address.country` | string | required_with:shipping_address | Country name |
| `discount_amount` | decimal | optional, min:0 | Auto-recalculates totals |
| `shipping_amount` | decimal | optional, min:0 | Auto-recalculates totals |
| `notes` | string | optional | Order notes |

### Response - Success (200)
```json
{
  "success": true,
  "message": "Order updated successfully",
  "data": {
    "id": 123,
    "order_number": "ORD-20251212-0123",
    "customer_id": 45,
    "store_id": 2,
    "order_type": "social_commerce",
    "status": "confirmed",
    "subtotal": 5000.00,
    "discount_amount": 50.00,
    "shipping_amount": 100.00,
    "total_amount": 5050.00,
    "outstanding_amount": 5050.00,
    "notes": "Updated delivery instructions",
    "shipping_address": {
      "address_line1": "House 45, Road 12",
      "address_line2": "Banani",
      "city": "Dhaka",
      "state": "Dhaka Division",
      "postal_code": "1213",
      "country": "Bangladesh"
    },
    "customer": {
      "id": 45,
      "name": "Updated Customer Name",
      "phone": "01712345678",
      "email": "updated@example.com"
    },
    "items": [...],
    "payments": [...]
  }
}
```

### Response - Cannot Update (400)
```json
{
  "success": false,
  "message": "Cannot update order in current status: shipped"
}
```

### Response - Validation Error (422)
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "customer_email": ["The customer email must be a valid email address."],
    "shipping_address.city": ["The shipping address.city field is required when shipping address is present."]
  }
}
```

### Response - Not Found (404)
```json
{
  "success": false,
  "message": "Order not found"
}
```

---

## Customer Side API

### Endpoint
```http
PUT    /api/customer/orders/{orderNumber}
PATCH  /api/customer/orders/{orderNumber}
```

### Authentication
- **Required**: Customer JWT token
- **Header**: `Authorization: Bearer {customer_token}`

### Permissions
Customers have **limited** update access:
- ✅ Shipping address
- ✅ Delivery notes/instructions
- ❌ Customer information (use profile API)
- ❌ Discount/shipping amounts
- ❌ Items
- ❌ Any financial details

### Request Body
```json
{
  "shipping_address": {
    "address_line1": "New Address Line 1",
    "address_line2": "Apartment 5B",
    "city": "Dhaka",
    "state": "Dhaka Division",
    "postal_code": "1207",
    "country": "Bangladesh"
  },
  "notes": "Please deliver between 2-4 PM"
}
```

### Validation Rules

| Field | Type | Rules | Notes |
|-------|------|-------|-------|
| `shipping_address` | object | optional | Must include required fields if provided |
| `shipping_address.address_line1` | string | required_with:shipping_address | Primary address |
| `shipping_address.address_line2` | string | optional | Secondary address |
| `shipping_address.city` | string | required_with:shipping_address | City name |
| `shipping_address.state` | string | optional | State/division |
| `shipping_address.postal_code` | string | optional | Postal code |
| `shipping_address.country` | string | required_with:shipping_address | Country name |
| `notes` | string | optional, max:500 | Delivery instructions |

### Response - Success (200)
```json
{
  "success": true,
  "message": "Order updated successfully",
  "data": {
    "id": 456,
    "order_number": "ORD-20251212-0456",
    "status": "confirmed",
    "shipping_address": {
      "address_line1": "New Address Line 1",
      "address_line2": "Apartment 5B",
      "city": "Dhaka",
      "state": "Dhaka Division",
      "postal_code": "1207",
      "country": "Bangladesh"
    },
    "notes": "Please deliver between 2-4 PM",
    "customer": {...},
    "items": [...]
  }
}
```

### Response - Cannot Update (400)
```json
{
  "success": false,
  "message": "Cannot update order in current status: ready_for_shipment",
  "hint": "Orders can only be updated before fulfillment begins"
}
```

---

## Frontend Implementation Guide

### Employee Side - Order Edit Form

```typescript
// services/orderService.ts
export const updateOrder = async (orderId: number, updates: OrderUpdateData) => {
  const response = await api.patch(`/api/orders/${orderId}`, updates);
  return response.data;
};

// types/order.ts
interface OrderUpdateData {
  customer_name?: string;
  customer_phone?: string;
  customer_email?: string;
  customer_address?: string;
  shipping_address?: ShippingAddress;
  discount_amount?: number;
  shipping_amount?: number;
  notes?: string;
}

interface ShippingAddress {
  address_line1: string;
  address_line2?: string;
  city: string;
  state?: string;
  postal_code?: string;
  country: string;
}
```

```jsx
// components/OrderEditForm.tsx
import { useState } from 'react';
import { updateOrder } from '../services/orderService';

function OrderEditForm({ order, onUpdate }) {
  const [formData, setFormData] = useState({
    customer_name: order.customer.name,
    customer_phone: order.customer.phone,
    customer_email: order.customer.email,
    discount_amount: order.discount_amount,
    shipping_amount: order.shipping_amount,
    notes: order.notes
  });
  
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  // Check if order can be updated
  const canUpdate = ['pending', 'confirmed', 'assigned_to_store', 'picking']
    .includes(order.status);

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (!canUpdate) {
      setError('Order cannot be updated in current status');
      return;
    }
    
    setLoading(true);
    setError(null);
    
    try {
      const result = await updateOrder(order.id, formData);
      onUpdate(result.data);
      alert('Order updated successfully!');
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to update order');
    } finally {
      setLoading(false);
    }
  };

  if (!canUpdate) {
    return (
      <div className="alert alert-warning">
        <Icon name="lock" />
        Order cannot be updated (Status: {order.status})
      </div>
    );
  }

  return (
    <form onSubmit={handleSubmit}>
      {error && <div className="alert alert-danger">{error}</div>}
      
      <div className="form-section">
        <h3>Customer Information</h3>
        
        <input
          type="text"
          name="customer_name"
          label="Customer Name"
          value={formData.customer_name}
          onChange={(e) => setFormData({...formData, customer_name: e.target.value})}
        />
        
        <input
          type="tel"
          name="customer_phone"
          label="Phone"
          value={formData.customer_phone}
          onChange={(e) => setFormData({...formData, customer_phone: e.target.value})}
        />
        
        <input
          type="email"
          name="customer_email"
          label="Email"
          value={formData.customer_email}
          onChange={(e) => setFormData({...formData, customer_email: e.target.value})}
        />
      </div>
      
      <div className="form-section">
        <h3>Order Details</h3>
        
        <input
          type="number"
          name="discount_amount"
          label="Discount Amount"
          value={formData.discount_amount}
          onChange={(e) => setFormData({...formData, discount_amount: parseFloat(e.target.value)})}
          min="0"
          step="0.01"
        />
        
        <input
          type="number"
          name="shipping_amount"
          label="Shipping Amount"
          value={formData.shipping_amount}
          onChange={(e) => setFormData({...formData, shipping_amount: parseFloat(e.target.value)})}
          min="0"
          step="0.01"
        />
        
        <textarea
          name="notes"
          label="Notes"
          value={formData.notes}
          onChange={(e) => setFormData({...formData, notes: e.target.value})}
          rows={3}
        />
      </div>
      
      <div className="form-actions">
        <button type="submit" disabled={loading} className="btn btn-primary">
          {loading ? 'Updating...' : 'Update Order'}
        </button>
      </div>
    </form>
  );
}
```

### Customer Side - Update Shipping Address

```jsx
// components/UpdateShippingAddressModal.tsx
import { useState } from 'react';
import { updateCustomerOrder } from '../services/ecommerceService';

function UpdateShippingAddressModal({ order, onUpdate, onClose }) {
  const [address, setAddress] = useState(
    order.shipping_address || {
      address_line1: '',
      address_line2: '',
      city: '',
      postal_code: '',
      country: 'Bangladesh'
    }
  );
  
  const [notes, setNotes] = useState(order.notes || '');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const canUpdate = ['pending', 'confirmed', 'assigned_to_store', 'picking']
    .includes(order.status);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError(null);
    
    try {
      const result = await updateCustomerOrder(order.order_number, {
        shipping_address: address,
        notes: notes
      });
      
      onUpdate(result.data);
      onClose();
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to update order');
    } finally {
      setLoading(false);
    }
  };

  if (!canUpdate) {
    return (
      <div className="modal">
        <div className="alert alert-info">
          <p>Your order is being processed and cannot be updated at this time.</p>
          <p>Status: <strong>{order.status}</strong></p>
        </div>
        <button onClick={onClose}>Close</button>
      </div>
    );
  }

  return (
    <div className="modal">
      <div className="modal-header">
        <h2>Update Delivery Details</h2>
        <button onClick={onClose}>×</button>
      </div>
      
      <form onSubmit={handleSubmit}>
        {error && <div className="alert alert-danger">{error}</div>}
        
        <div className="form-group">
          <label>Address Line 1 *</label>
          <input
            type="text"
            value={address.address_line1}
            onChange={(e) => setAddress({...address, address_line1: e.target.value})}
            required
          />
        </div>
        
        <div className="form-group">
          <label>Address Line 2</label>
          <input
            type="text"
            value={address.address_line2}
            onChange={(e) => setAddress({...address, address_line2: e.target.value})}
          />
        </div>
        
        <div className="form-row">
          <div className="form-group">
            <label>City *</label>
            <input
              type="text"
              value={address.city}
              onChange={(e) => setAddress({...address, city: e.target.value})}
              required
            />
          </div>
          
          <div className="form-group">
            <label>Postal Code</label>
            <input
              type="text"
              value={address.postal_code}
              onChange={(e) => setAddress({...address, postal_code: e.target.value})}
            />
          </div>
        </div>
        
        <div className="form-group">
          <label>Delivery Instructions</label>
          <textarea
            value={notes}
            onChange={(e) => setNotes(e.target.value)}
            rows={3}
            maxLength={500}
            placeholder="E.g., Please ring the bell twice, deliver to security..."
          />
          <small>{notes.length}/500 characters</small>
        </div>
        
        <div className="modal-actions">
          <button type="button" onClick={onClose} className="btn btn-secondary">
            Cancel
          </button>
          <button type="submit" disabled={loading} className="btn btn-primary">
            {loading ? 'Updating...' : 'Update Details'}
          </button>
        </div>
      </form>
    </div>
  );
}
```

---

## Business Rules

### When Customers Can Update
1. **Before Store Assignment**: Full updates allowed
2. **After Store Assignment**: Limited to shipping address and notes
3. **During Picking**: Only notes can be updated
4. **After Ready for Shipment**: No updates allowed

### When Employees Can Update
1. **Pending/Confirmed**: All fields can be updated
2. **Assigned to Store**: All except customer info
3. **Picking**: Only notes and shipping info
4. **After Fulfillment**: No updates (must cancel and recreate)

### Automatic Recalculation
When discount or shipping amount changes:
```
total_amount = subtotal - discount_amount + shipping_amount
outstanding_amount = total_amount - paid_amount
```

### Customer Profile Updates
When employee updates customer information through order API:
- Changes are saved to customer record
- Walk-in customers (phone='WALK-IN') cannot be updated
- Updates apply to all future orders

---

## Common Use Cases

### 1. Customer Realized Wrong Address
```http
PATCH /api/customer/orders/ORD-20251212-0456
Authorization: Bearer {customer_token}

{
  "shipping_address": {
    "address_line1": "Correct Address",
    "city": "Dhaka",
    "country": "Bangladesh"
  }
}
```

### 2. Apply Additional Discount
```http
PATCH /api/orders/123
Authorization: Bearer {employee_token}

{
  "discount_amount": 100.00,
  "notes": "Loyalty discount applied"
}
```

### 3. Update Delivery Instructions
```http
PATCH /api/customer/orders/ORD-20251212-0456
Authorization: Bearer {customer_token}

{
  "notes": "Please call when arriving, gate code is 1234"
}
```

### 4. Fix Customer Contact Info
```http
PATCH /api/orders/123
Authorization: Bearer {employee_token}

{
  "customer_phone": "01712345678",
  "customer_email": "correct@email.com"
}
```

---

## Error Handling

### Common Errors

| Error | Status | Reason | Solution |
|-------|--------|--------|----------|
| Order not found | 404 | Invalid order ID/number | Check order exists |
| Cannot update order in current status | 400 | Order already processed | Inform user, suggest cancel+recreate |
| Validation failed | 422 | Invalid input data | Show validation errors to user |
| Unauthorized | 401 | Invalid/expired token | Re-authenticate |
| Server error | 500 | Backend issue | Retry or contact support |

### Frontend Error Handling Pattern

```typescript
try {
  const result = await updateOrder(orderId, updates);
  showSuccessMessage('Order updated successfully');
  refreshOrderData();
} catch (error) {
  if (error.response?.status === 400) {
    // Status-based restriction
    showError(error.response.data.message);
    showInfo('You can still cancel this order if needed');
  } else if (error.response?.status === 422) {
    // Validation errors
    displayValidationErrors(error.response.data.errors);
  } else if (error.response?.status === 404) {
    // Not found
    showError('Order not found');
    redirectToOrderList();
  } else {
    // Generic error
    showError('Failed to update order. Please try again.');
  }
}
```

---

## Testing Checklist

### Employee Side
- [ ] Can update customer information
- [ ] Can update shipping address
- [ ] Can change discount amount
- [ ] Can change shipping amount
- [ ] Can add/edit notes
- [ ] Totals recalculate correctly
- [ ] Cannot update shipped orders
- [ ] Validation errors displayed correctly

### Customer Side
- [ ] Can update shipping address
- [ ] Can add delivery instructions
- [ ] Cannot update after fulfillment
- [ ] Cannot change prices
- [ ] Cannot edit customer profile
- [ ] Proper error messages shown

### Edge Cases
- [ ] Walk-in customer orders
- [ ] Pre-orders
- [ ] Orders with installments
- [ ] Partially paid orders
- [ ] Multi-item orders

---

## Related APIs

- **Item Management**: `/api/orders/{id}/items/*`
- **Order Lifecycle**: `/api/orders/{id}/fulfill`, `/api/orders/{id}/complete`, `/api/orders/{id}/cancel`
- **Customer Profile**: `/api/profile/update`
- **Payment Updates**: `/api/orders/{id}/payments/*`

---

## Support & Questions

For issues or questions:
- **Backend Team**: [Add contact]
- **API Documentation**: `/api/documentation`
- **Issue Tracker**: [Add link]

Last Updated: December 12, 2025
