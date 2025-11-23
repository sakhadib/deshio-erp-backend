# Pathao Courier - Quick Reference Guide

## 🎯 When to Create Shipments

| Sales Channel | Customer Wants | Create Shipment? | Send to Pathao |
|--------------|----------------|------------------|----------------|
| **POS** | Takes product home | ❌ **NO** | N/A |
| **POS** | Wants delivery | ✅ **YES** | Immediately |
| **Phone Order** | Delivery | ✅ **YES** | Manually later |
| **E-commerce** | Delivery | ✅ **YES** | Manually/Auto later |

---

## 📋 Quick Workflows

### Workflow 1: POS - No Delivery
```bash
1. POST /api/orders                    # Create order
2. PATCH /api/orders/{id}/complete     # Complete (reduce inventory)
3. DONE ✅                             # Customer left with product
```

### Workflow 2: POS - With Delivery (Immediate)
```bash
1. POST /api/orders                    # Create order
2. PATCH /api/orders/{id}/complete     # Complete (reduce inventory)
3. POST /api/shipments                 # Create + send_to_pathao: true
   → Pathao picks up from store
```

### Workflow 3: Phone Order - Manual Send
```bash
1. POST /api/orders                    # Create order
2. POST /api/shipments                 # Create with send_to_pathao: false
3. Wait for payment...
4. PATCH /api/orders/{id}/complete     # Complete order
5. POST /api/shipments/{id}/send-to-pathao  # Manually send when ready
   → Pathao picks up
```

### Workflow 4: E-commerce - Batch Processing
```bash
# Throughout the day:
1. POST /api/orders (×50)              # Multiple orders
2. POST /api/shipments (×50)           # Create all (send_to_pathao: false)
3. PATCH /api/orders/{id}/complete (×50)  # Complete all

# End of day:
4. POST /api/shipments/bulk-send-to-pathao  # Send all at once
   {shipment_ids: [1,2,3...50]}
   → One Pathao pickup for all
```

---

## 🔧 Key API Calls

### Create Shipment - Immediate Send
```json
POST /api/shipments
{
  "order_id": 123,
  "delivery_type": "express",
  "send_to_pathao": true  // ✅ Send now
}
```

### Create Shipment - Manual Send Later
```json
POST /api/shipments
{
  "order_id": 123,
  "delivery_type": "home_delivery",
  "send_to_pathao": false  // ⏸️ Don't send yet
}
```

### Send to Pathao (Manual)
```json
POST /api/shipments/{id}/send-to-pathao
```

### Bulk Send to Pathao
```json
POST /api/shipments/bulk-send-to-pathao
{
  "shipment_ids": [1, 2, 3, 4, 5]
}
```

### Get Pending Pathao Submissions
```json
GET /api/shipments?pending_pathao=true
```

### Bulk Sync Status
```json
POST /api/shipments/bulk-sync-pathao-status
{
  "shipment_ids": [1, 2, 3]  // Optional
}
```

---

## 🌍 Pathao Area Lookup

### Step 1: Get Cities
```json
GET /api/shipments/pathao/cities

Response:
[
  {"city_id": 1, "city_name": "Dhaka"},
  {"city_id": 2, "city_name": "Chittagong"}
]
```

### Step 2: Get Zones for City
```json
GET /api/shipments/pathao/zones/1

Response:
[
  {"zone_id": 10, "zone_name": "Gulshan"},
  {"zone_id": 12, "zone_name": "Banani"}
]
```

### Step 3: Get Areas for Zone
```json
GET /api/shipments/pathao/areas/10

Response:
[
  {"area_id": 52, "area_name": "Gulshan-2"},
  {"area_id": 53, "area_name": "Gulshan-1"}
]
```

### Use in Order
```json
POST /api/orders
{
  ...
  "shipping_address": {
    "name": "Customer Name",
    "phone": "01712345678",
    "street": "House 12, Road 5",
    "area": "Gulshan-2",
    "city": "Dhaka",
    "pathao_city_id": 1,
    "pathao_zone_id": 10,
    "pathao_area_id": 52
  }
}
```

---

## 📊 Status Flow

```
pending
    ↓ (send to Pathao)
pickup_requested
    ↓ (Pathao rider picks up)
picked_up
    ↓ (rider delivers)
in_transit
    ↓
delivered ✅

OR

returned ⚠️
cancelled ❌
```

---

## 💰 COD (Cash on Delivery)

**Automatic Calculation:**

```
Order Total: ৳50,000
Paid Amount: ৳20,000
→ COD: ৳30,000 (automatically set)

OR

Order Total: ৳50,000
Paid Amount: ৳50,000
→ COD: ৳0 (fully paid)
```

---

## 🚚 Delivery Types

| Type | Pathao Code | Speed | Use Case |
|------|------------|-------|----------|
| `home_delivery` | 48 | Normal | Standard delivery |
| `express` | 12 | Fast | Same-day delivery |

---

## 📦 Package Weight

```json
{
  "package_weight": 0.5,  // 0.5 kg
  "package_weight": 2.5,  // 2.5 kg
  "package_weight": 10.0  // 10 kg
}
```

---

## 🔍 Filters

```bash
# All shipments
GET /api/shipments

# Pending only
GET /api/shipments?status=pending

# In transit
GET /api/shipments?status=in_transit

# Delivered
GET /api/shipments?status=delivered

# NOT sent to Pathao yet
GET /api/shipments?pending_pathao=true

# By store
GET /api/shipments?store_id=1

# By date
GET /api/shipments?date_from=2025-01-01&date_to=2025-01-31

# Search
GET /api/shipments?search=SHP-001
```

---

## ⚙️ Configuration

### .env File
```env
PATHAO_SANDBOX=true
PATHAO_CLIENT_ID=your_client_id
PATHAO_CLIENT_SECRET=your_client_secret
PATHAO_USERNAME=your_username
PATHAO_PASSWORD=your_password
```

### Store Setup
```sql
ALTER TABLE stores ADD COLUMN pathao_store_id INT NULL;
UPDATE stores SET pathao_store_id = 1 WHERE id = 1;
```

---

## ✅ Daily Operations Checklist

### Morning
- [ ] Check pending Pathao submissions
- [ ] Review yesterday's deliveries
- [ ] Sync status for in-transit shipments

### Throughout Day
- [ ] Create orders as they come
- [ ] Create shipments (send_to_pathao: false)
- [ ] Complete orders after payment

### End of Day
- [ ] Bulk send all pending shipments to Pathao
- [ ] Sync all statuses
- [ ] Review statistics

---

## 🚨 Common Scenarios

### Scenario: Customer wants immediate delivery
```bash
POST /api/shipments {send_to_pathao: true}
```

### Scenario: Wait for payment confirmation
```bash
POST /api/shipments {send_to_pathao: false}
# Later...
POST /api/shipments/{id}/send-to-pathao
```

### Scenario: Batch 50 orders daily
```bash
# Create all shipments
POST /api/shipments (×50) {send_to_pathao: false}

# End of day bulk send
POST /api/shipments/bulk-send-to-pathao
{shipment_ids: [1..50]}
```

### Scenario: Check delivery status
```bash
GET /api/shipments/{id}/sync-pathao-status
```

### Scenario: Cancel delivery
```bash
PATCH /api/shipments/{id}/cancel
{reason: "Customer changed address"}
```

---

## 📱 Mobile App Integration

```javascript
// Create order with delivery
const order = await createOrder({...});

// If customer wants delivery
if (customer_wants_delivery) {
  const shipment = await createShipment({
    order_id: order.id,
    delivery_type: "express",
    send_to_pathao: true  // Immediate for POS
  });
} else {
  // Complete order, no shipment
  await completeOrder(order.id);
}
```

---

## 🎓 Training Tips

1. **POS Staff**: 
   - Ask customer: "Delivery or take now?"
   - If delivery: Create shipment with send_to_pathao=true
   - If take: No shipment needed

2. **Phone Order Staff**:
   - Create order + shipment
   - Don't send to Pathao yet
   - Wait for payment confirmation
   - Manually send when ready

3. **Warehouse Staff**:
   - Check pending Pathao list daily
   - Bulk send at end of day
   - Sync statuses regularly

---

## 📈 Statistics

```bash
GET /api/shipments/statistics

Returns:
- Total shipments
- Pending Pathao submissions
- In transit count
- Delivered count
- Total delivery fees
- Total COD amount
```

---

**Quick Start**: For POS with delivery, use `send_to_pathao: true`. For phone/ecommerce orders, use `send_to_pathao: false` and bulk send later! 🚀
