# Order Update API - Quick Reference

## Endpoints

### Employee Side
```
PUT/PATCH  /api/orders/{id}
Auth: Bearer {employee_token}
```

**Can Update:**
- Customer info (name, phone, email, address)
- Shipping address
- Discount amount
- Shipping amount  
- Notes

**Cannot Update:**
- Items (use item endpoints)
- Order type
- Status after fulfillment

---

### Customer Side
```
PUT/PATCH  /api/customer/orders/{orderNumber}
Auth: Bearer {customer_token}
```

**Can Update:**
- Shipping address
- Delivery notes (max 500 chars)

**Cannot Update:**
- Customer profile info
- Prices
- Items
- Financial details

---

## Status Requirements

Both APIs only work when order status is:
- ✅ `pending`
- ✅ `confirmed`
- ✅ `assigned_to_store`
- ✅ `picking` (limited)

Cannot update when:
- ❌ `ready_for_shipment`
- ❌ `shipped`
- ❌ `delivered`
- ❌ `cancelled`

---

## Quick Examples

### Update Customer Info (Employee)
```bash
curl -X PATCH https://api.example.com/api/orders/123 \
  -H "Authorization: Bearer {employee_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "customer_name": "John Doe",
    "customer_phone": "01712345678",
    "discount_amount": 50.00
  }'
```

### Update Shipping Address (Customer)
```bash
curl -X PATCH https://api.example.com/api/customer/orders/ORD-20251212-0456 \
  -H "Authorization: Bearer {customer_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "shipping_address": {
      "address_line1": "123 New Street",
      "city": "Dhaka",
      "country": "Bangladesh"
    },
    "notes": "Please call before delivery"
  }'
```

---

## Response Codes

| Code | Meaning |
|------|---------|
| 200 | ✅ Updated successfully |
| 400 | ❌ Cannot update (wrong status) |
| 401 | ❌ Unauthorized |
| 404 | ❌ Order not found |
| 422 | ❌ Validation failed |
| 500 | ❌ Server error |

---

## Frontend Integration

```typescript
// Employee
await api.patch(`/api/orders/${orderId}`, {
  discount_amount: 100,
  notes: "VIP customer"
});

// Customer
await api.patch(`/api/customer/orders/${orderNumber}`, {
  shipping_address: newAddress,
  notes: "Updated delivery instructions"
});
```

---

## Important Notes

⚠️ **Automatic Recalculation**
- Changing discount/shipping amounts auto-updates `total_amount` and `outstanding_amount`

⚠️ **Customer Profile Updates**
- Employee updates to customer info save to customer record
- Walk-in customers (phone='WALK-IN') cannot be updated

⚠️ **Order Items**
- Use dedicated endpoints: `/api/orders/{id}/items/*`
- Cannot be updated through this API

---

For full documentation, see [ORDER_UPDATE_API.md](ORDER_UPDATE_API.md)
