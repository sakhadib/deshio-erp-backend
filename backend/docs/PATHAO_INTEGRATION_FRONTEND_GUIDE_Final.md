# Pathao Courier Integration - Frontend Implementation Guide

## Overview
This guide explains how to implement Pathao courier integration in the frontend. **All changes are additive and optional** - existing functionality will continue to work without implementing these features.

## Important Notes
- ✅ **Backward Compatible**: Existing orders and shipments work without Pathao location IDs
- ✅ **Database Safe**: All new fields are nullable, no data loss
- ✅ **Non-Breaking**: Frontend won't crash if Pathao IDs are missing
- ⚠️ **Gradual Implementation**: Implement features step-by-step, test incrementally

---

## Phase 1: Store Configuration (Admin/Manager Feature)

### 1.1 Add Pathao Configuration to Store Settings

**Purpose**: Allow stores to register with Pathao before creating shipments.

**API Endpoints**:
```javascript
// Get location dropdowns
GET /api/pathao/cities
GET /api/pathao/cities/{cityId}/zones
GET /api/pathao/cities/{zoneId}/areas

// Check if store is registered
GET /api/pathao/stores/{storeId}/status

// Register store with Pathao
POST /api/pathao/stores/{storeId}/register
{
  "pathao_contact_name": "John Doe",
  "pathao_contact_number": "01712345678",
  "pathao_secondary_contact": "01898765432", // optional
  "pathao_city_id": 1,
  "pathao_zone_id": 23,
  "pathao_area_id": 456,
  "address": "123 Main Street, Gulshan"
}

// Update store config (without re-registering)
PATCH /api/pathao/stores/{storeId}/config
{
  "pathao_contact_name": "Jane Doe",
  "pathao_contact_number": "01712345678"
}
```

**UI Implementation**:
```javascript
// Store Settings Page - New "Pathao Configuration" Tab

import { useState, useEffect } from 'react';

function PathaoStoreConfiguration({ storeId }) {
  const [cities, setCities] = useState([]);
  const [zones, setZones] = useState([]);
  const [areas, setAreas] = useState([]);
  const [storeStatus, setStoreStatus] = useState(null);
  const [formData, setFormData] = useState({
    pathao_contact_name: '',
    pathao_contact_number: '',
    pathao_secondary_contact: '',
    pathao_city_id: null,
    pathao_zone_id: null,
    pathao_area_id: null,
    address: ''
  });

  useEffect(() => {
    // Load store Pathao status
    fetch(`/api/pathao/stores/${storeId}/status`)
      .then(res => res.json())
      .then(data => {
        setStoreStatus(data.data);
        if (data.data.is_registered) {
          setFormData(data.data.config);
        }
      });

    // Load cities
    fetch('/api/pathao/cities')
      .then(res => res.json())
      .then(data => setCities(data.data));
  }, [storeId]);

  const handleCityChange = (cityId) => {
    setFormData({ ...formData, pathao_city_id: cityId, pathao_zone_id: null, pathao_area_id: null });
    fetch(`/api/pathao/cities/${cityId}/zones`)
      .then(res => res.json())
      .then(data => setZones(data.data));
  };

  const handleZoneChange = (zoneId) => {
    setFormData({ ...formData, pathao_zone_id: zoneId, pathao_area_id: null });
    fetch(`/api/pathao/zones/${zoneId}/areas`)
      .then(res => res.json())
      .then(data => setAreas(data.data));
  };

  const handleRegister = async () => {
    const response = await fetch(`/api/pathao/stores/${storeId}/register`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(formData)
    });
    
    if (response.ok) {
      alert('Store registered with Pathao successfully!');
      // Reload status
      const statusRes = await fetch(`/api/pathao/stores/${storeId}/status`);
      const statusData = await statusRes.json();
      setStoreStatus(statusData.data);
    } else {
      const error = await response.json();
      alert(`Registration failed: ${error.message}`);
    }
  };

  return (
    <div className="pathao-config-panel">
      <h3>Pathao Courier Configuration</h3>
      
      {storeStatus?.is_registered && (
        <div className="alert alert-success">
          ✅ Store is registered with Pathao (Store ID: {storeStatus.pathao_store_id})
          <br />
          Registered at: {new Date(storeStatus.registered_at).toLocaleString()}
        </div>
      )}

      <form>
        <div className="form-group">
          <label>Contact Name *</label>
          <input
            type="text"
            value={formData.pathao_contact_name}
            onChange={(e) => setFormData({ ...formData, pathao_contact_name: e.target.value })}
            placeholder="John Doe"
          />
        </div>

        <div className="form-group">
          <label>Contact Number *</label>
          <input
            type="tel"
            value={formData.pathao_contact_number}
            onChange={(e) => setFormData({ ...formData, pathao_contact_number: e.target.value })}
            placeholder="01712345678"
          />
        </div>

        <div className="form-group">
          <label>Secondary Contact</label>
          <input
            type="tel"
            value={formData.pathao_secondary_contact}
            onChange={(e) => setFormData({ ...formData, pathao_secondary_contact: e.target.value })}
            placeholder="01898765432"
          />
        </div>

        <div className="form-group">
          <label>City *</label>
          <select
            value={formData.pathao_city_id || ''}
            onChange={(e) => handleCityChange(parseInt(e.target.value))}
          >
            <option value="">Select City</option>
            {cities.map(city => (
              <option key={city.city_id} value={city.city_id}>
                {city.city_name}
              </option>
            ))}
          </select>
        </div>

        <div className="form-group">
          <label>Zone *</label>
          <select
            value={formData.pathao_zone_id || ''}
            onChange={(e) => handleZoneChange(parseInt(e.target.value))}
            disabled={!formData.pathao_city_id}
          >
            <option value="">Select Zone</option>
            {zones.map(zone => (
              <option key={zone.zone_id} value={zone.zone_id}>
                {zone.zone_name}
              </option>
            ))}
          </select>
        </div>

        <div className="form-group">
          <label>Area *</label>
          <select
            value={formData.pathao_area_id || ''}
            onChange={(e) => setFormData({ ...formData, pathao_area_id: parseInt(e.target.value) })}
            disabled={!formData.pathao_zone_id}
          >
            <option value="">Select Area</option>
            {areas.map(area => (
              <option key={area.area_id} value={area.area_id}>
                {area.area_name}
              </option>
            ))}
          </select>
        </div>

        <div className="form-group">
          <label>Store Address *</label>
          <textarea
            value={formData.address}
            onChange={(e) => setFormData({ ...formData, address: e.target.value })}
            placeholder="123 Main Street, Gulshan"
            rows={3}
          />
        </div>

        {!storeStatus?.is_registered ? (
          <button type="button" onClick={handleRegister} className="btn btn-primary">
            Register with Pathao
          </button>
        ) : (
          <button
            type="button"
            onClick={async () => {
              await fetch(`/api/pathao/stores/${storeId}/config`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
              });
              alert('Configuration updated');
            }}
            className="btn btn-secondary"
          >
            Update Configuration
          </button>
        )}
      </form>
    </div>
  );
}
```

---

## Phase 2: Customer Address Management

### 2.1 Add Pathao Location to Customer Address Form

**Purpose**: Capture Pathao location IDs when customer adds/edits delivery address.

**Database Fields** (already added, all nullable):
- `customer_addresses.pathao_city_id`
- `customer_addresses.pathao_zone_id`
- `customer_addresses.pathao_area_id`

**UI Implementation**:
```javascript
function CustomerAddressForm({ customerId, address = null }) {
  const [formData, setFormData] = useState({
    // Existing fields
    address_line_1: address?.address_line_1 || '',
    city: address?.city || '',
    postal_code: address?.postal_code || '',
    phone: address?.phone || '',
    
    // New Pathao fields (optional)
    pathao_city_id: address?.pathao_city_id || null,
    pathao_zone_id: address?.pathao_zone_id || null,
    pathao_area_id: address?.pathao_area_id || null
  });

  const [cities, setCities] = useState([]);
  const [zones, setZones] = useState([]);
  const [areas, setAreas] = useState([]);

  useEffect(() => {
    // Load cities for dropdown
    fetch('/api/pathao/cities')
      .then(res => res.json())
      .then(data => setCities(data.data || []));
  }, []);

  const handleCityChange = (cityId) => {
    setFormData({ ...formData, pathao_city_id: cityId, pathao_zone_id: null, pathao_area_id: null });
    fetch(`/api/pathao/cities/${cityId}/zones`)
      .then(res => res.json())
      .then(data => setZones(data.data || []));
  };

  const handleZoneChange = (zoneId) => {
    setFormData({ ...formData, pathao_zone_id: zoneId, pathao_area_id: null });
    fetch(`/api/pathao/zones/${zoneId}/areas`)
      .then(res => res.json())
      .then(data => setAreas(data.data || []));
  };

  return (
    <form>
      {/* Existing address fields */}
      <input
        type="text"
        placeholder="Address Line 1"
        value={formData.address_line_1}
        onChange={(e) => setFormData({ ...formData, address_line_1: e.target.value })}
      />

      {/* New Pathao Location Section (Optional) */}
      <div className="pathao-location-section">
        <h4>Pathao Courier Location (Optional - for faster delivery)</h4>
        <p className="text-muted">
          Selecting Pathao location improves delivery accuracy and speed.
        </p>

        <div className="form-group">
          <label>Pathao City</label>
          <select
            value={formData.pathao_city_id || ''}
            onChange={(e) => handleCityChange(parseInt(e.target.value) || null)}
          >
            <option value="">Select City (Optional)</option>
            {cities.map(city => (
              <option key={city.city_id} value={city.city_id}>
                {city.city_name}
              </option>
            ))}
          </select>
        </div>

        {formData.pathao_city_id && (
          <div className="form-group">
            <label>Pathao Zone</label>
            <select
              value={formData.pathao_zone_id || ''}
              onChange={(e) => handleZoneChange(parseInt(e.target.value) || null)}
            >
              <option value="">Select Zone (Optional)</option>
              {zones.map(zone => (
                <option key={zone.zone_id} value={zone.zone_id}>
                  {zone.zone_name}
                </option>
              ))}
            </select>
          </div>
        )}

        {formData.pathao_zone_id && (
          <div className="form-group">
            <label>Pathao Area</label>
            <select
              value={formData.pathao_area_id || ''}
              onChange={(e) => setFormData({ ...formData, pathao_area_id: parseInt(e.target.value) || null })}
            >
              <option value="">Select Area (Optional)</option>
              {areas.map(area => (
                <option key={area.area_id} value={area.area_id}>
                  {area.area_name}
                </option>
              ))}
            </select>
          </div>
        )}
      </div>

      <button type="submit">Save Address</button>
    </form>
  );
}
```

**Backward Compatibility Note**:
- Pathao fields are **completely optional**
- If user doesn't select Pathao location, address still works
- Existing addresses without Pathao IDs continue functioning

---

## Phase 3: Product Weight Management

### 3.1 Add Weight Field to Product Form

**Purpose**: Calculate total shipment weight for courier charges.

**Database Field** (already added):
- `products.weight` (decimal, nullable, default 0.5kg)

**UI Implementation**:
```javascript
function ProductForm({ product = null }) {
  const [formData, setFormData] = useState({
    // Existing fields
    name: product?.name || '',
    sku: product?.sku || '',
    price: product?.price || 0,
    
    // New weight field (optional, defaults to 0.5kg)
    weight: product?.weight || 0.5
  });

  return (
    <form>
      {/* Existing product fields */}
      
      <div className="form-group">
        <label>
          Weight (kg) 
          <span className="text-muted"> - Used for courier charge calculation</span>
        </label>
        <input
          type="number"
          step="0.01"
          min="0.01"
          value={formData.weight}
          onChange={(e) => setFormData({ ...formData, weight: parseFloat(e.target.value) || 0.5 })}
          placeholder="0.5"
        />
        <small className="form-text text-muted">
          Default: 0.5 kg. Enter actual product weight for accurate shipping charges.
        </small>
      </div>

      <button type="submit">Save Product</button>
    </form>
  );
}
```

**Backward Compatibility**:
- Existing products get default weight of 0.5kg
- Weight is optional - if not provided, uses default
- Old products continue working without issues

---

## Phase 4: Shipment Creation with Validation

### 4.1 Update Shipment Creation UI with Pathao Validation

**Purpose**: Show clear messages when Pathao data is missing, prevent failed API calls.

**API Behavior**:
- If store not registered: Shows error "Store not registered with Pathao"
- If address missing Pathao IDs: Shows error "Delivery address missing Pathao location"
- Weight auto-calculated from order items
- Existing shipments without Pathao continue working

**UI Implementation**:
```javascript
function ShipmentCreationForm({ orderId, storeId }) {
  const [storeStatus, setStoreStatus] = useState(null);
  const [deliveryAddress, setDeliveryAddress] = useState(null);
  const [validationWarnings, setValidationWarnings] = useState([]);

  useEffect(() => {
    // Check store Pathao status
    fetch(`/api/pathao/stores/${storeId}/status`)
      .then(res => res.json())
      .then(data => {
        setStoreStatus(data.data);
        
        const warnings = [];
        if (!data.data.is_registered) {
          warnings.push({
            type: 'error',
            message: 'Store is not registered with Pathao. Please configure store Pathao settings first.'
          });
        }
      });

    // Load order delivery address
    fetch(`/api/orders/${orderId}`)
      .then(res => res.json())
      .then(data => {
        const address = data.data.delivery_address;
        setDeliveryAddress(address);
        
        const warnings = [];
        if (!address.pathao_city_id || !address.pathao_zone_id || !address.pathao_area_id) {
          warnings.push({
            type: 'warning',
            message: 'Delivery address is missing Pathao location. Shipment will be created but cannot send to Pathao automatically.'
          });
        }
        setValidationWarnings(warnings);
      });
  }, [orderId, storeId]);

  const handleCreateShipment = async () => {
    // Create shipment (works even without Pathao IDs)
    const response = await fetch('/api/shipments', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        order_id: orderId,
        store_id: storeId,
        courier_service: 'pathao'
      })
    });

    if (response.ok) {
      const shipment = await response.json();
      
      // Try to send to Pathao (will fail gracefully if IDs missing)
      const pathaoResponse = await fetch(`/api/shipments/${shipment.data.id}/send-to-pathao`, {
        method: 'POST'
      });

      if (pathaoResponse.ok) {
        alert('Shipment created and sent to Pathao successfully!');
      } else {
        const error = await pathaoResponse.json();
        alert(`Shipment created, but Pathao sending failed: ${error.message}\n\nPlease configure Pathao location and try again.`);
      }
    }
  };

  return (
    <div className="shipment-creation-panel">
      <h3>Create Pathao Shipment</h3>

      {/* Validation Warnings */}
      {validationWarnings.map((warning, idx) => (
        <div key={idx} className={`alert alert-${warning.type}`}>
          {warning.type === 'error' ? '❌' : '⚠️'} {warning.message}
        </div>
      ))}

      {/* Store Status */}
      {storeStatus && (
        <div className="store-status-card">
          <h4>Store Pathao Status</h4>
          {storeStatus.is_registered ? (
            <div className="text-success">
              ✅ Registered (Pathao Store ID: {storeStatus.pathao_store_id})
            </div>
          ) : (
            <div className="text-danger">
              ❌ Not registered - 
              <a href={`/stores/${storeId}/settings?tab=pathao`}>
                Configure Pathao Settings
              </a>
            </div>
          )}
        </div>
      )}

      {/* Delivery Address Info */}
      {deliveryAddress && (
        <div className="address-card">
          <h4>Delivery Address</h4>
          <p>{deliveryAddress.address_line_1}</p>
          <p>{deliveryAddress.city}, {deliveryAddress.postal_code}</p>
          
          {deliveryAddress.pathao_city_id ? (
            <div className="text-success">
              ✅ Pathao location configured
            </div>
          ) : (
            <div className="text-warning">
              ⚠️ Missing Pathao location - 
              <a href={`/customers/${deliveryAddress.customer_id}/addresses/${deliveryAddress.id}/edit`}>
                Update Address
              </a>
            </div>
          )}
        </div>
      )}

      <button
        onClick={handleCreateShipment}
        disabled={!storeStatus?.is_registered}
        className="btn btn-primary"
      >
        Create Shipment
      </button>

      {!storeStatus?.is_registered && (
        <p className="text-muted">
          Store must be registered with Pathao before creating shipments.
        </p>
      )}
    </div>
  );
}
```

---

## Phase 5: Shipment List with Pathao Status

### 5.1 Show Pathao Status in Shipment List

**UI Implementation**:
```javascript
function ShipmentsList() {
  const [shipments, setShipments] = useState([]);

  useEffect(() => {
    fetch('/api/shipments')
      .then(res => res.json())
      .then(data => setShipments(data.data));
  }, []);

  return (
    <table className="table">
      <thead>
        <tr>
          <th>Order #</th>
          <th>Store</th>
          <th>Recipient</th>
          <th>Pathao Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        {shipments.map(shipment => (
          <tr key={shipment.id}>
            <td>{shipment.order.order_number}</td>
            <td>{shipment.store.name}</td>
            <td>{shipment.recipient_name}</td>
            <td>
              {shipment.pathao_consignment_id ? (
                <span className="badge badge-success">
                  {shipment.pathao_status || 'Sent to Pathao'}
                </span>
              ) : (
                <span className="badge badge-secondary">Not sent</span>
              )}
            </td>
            <td>
              {!shipment.pathao_consignment_id && (
                <button
                  onClick={() => sendToPathao(shipment.id)}
                  className="btn btn-sm btn-primary"
                >
                  Send to Pathao
                </button>
              )}
              {shipment.pathao_consignment_id && (
                <button
                  onClick={() => syncPathaoStatus(shipment.id)}
                  className="btn btn-sm btn-secondary"
                >
                  Sync Status
                </button>
              )}
            </td>
          </tr>
        ))}
      </tbody>
    </table>
  );

  async function sendToPathao(shipmentId) {
    const response = await fetch(`/api/shipments/${shipmentId}/send-to-pathao`, {
      method: 'POST'
    });
    
    if (response.ok) {
      alert('Sent to Pathao successfully!');
      // Reload shipments
    } else {
      const error = await response.json();
      alert(`Failed: ${error.message}`);
    }
  }

  async function syncPathaoStatus(shipmentId) {
    const response = await fetch(`/api/shipments/${shipmentId}/sync-pathao-status`);
    const data = await response.json();
    alert(`Status: ${data.data.pathao_status}`);
    // Reload shipments
  }
}
```

---

## Testing Checklist

### ✅ Phase 1: Store Configuration
- [ ] Can view store Pathao status (registered/not registered)
- [ ] Can load cities dropdown
- [ ] Can load zones after selecting city
- [ ] Can load areas after selecting zone
- [ ] Can register store with Pathao (all fields filled)
- [ ] Validation error if required fields missing
- [ ] Can update store config after registration

### ✅ Phase 2: Customer Addresses
- [ ] Can create address without Pathao location (backward compatible)
- [ ] Can create address with Pathao location
- [ ] Can edit existing address and add Pathao location
- [ ] Cascading dropdowns work (city → zone → area)
- [ ] Existing addresses without Pathao IDs still work

### ✅ Phase 3: Product Weight
- [ ] Can add product without weight (uses default 0.5kg)
- [ ] Can add product with custom weight
- [ ] Existing products show default weight
- [ ] Can update product weight

### ✅ Phase 4: Shipment Creation
- [ ] Shows warning if store not registered
- [ ] Shows warning if address missing Pathao location
- [ ] Can create shipment even with warnings (non-blocking)
- [ ] Sends to Pathao successfully if all IDs present
- [ ] Shows clear error if Pathao sending fails
- [ ] Weight calculated automatically from order items

### ✅ Phase 5: Shipment Management
- [ ] Can view shipments with/without Pathao status
- [ ] Can manually send to Pathao after fixing missing IDs
- [ ] Can sync Pathao status after sending
- [ ] Existing shipments without Pathao continue working

---

## Error Handling Guide

### Common Errors and Solutions

**Error: "Store not registered with Pathao"**
- **Cause**: Store doesn't have `pathao_store_id`
- **Solution**: Go to Store Settings → Pathao Configuration → Register Store

**Error: "Delivery address missing Pathao city/zone/area ID"**
- **Cause**: Customer address doesn't have Pathao location IDs
- **Solution**: Edit customer address → Select Pathao location dropdowns

**Error: "Cannot send to Pathao: [validation errors]"**
- **Cause**: Missing required Pathao data (store config or address location)
- **Solution**: Fix the specific validation error mentioned, then retry sending

---

## Database Migration Safety

All database changes are **non-destructive**:

### ✅ Safe Changes
- All new fields are `nullable`
- Existing data continues working
- No data loss or corruption
- Works on MySQL and PostgreSQL

### Migration Files Created
1. `2025_12_21_000001_add_pathao_location_to_customer_addresses.php`
2. `2025_12_21_000002_add_pathao_fields_to_stores.php`
3. `2025_12_21_000003_add_weight_to_products.php`

All migrations already run successfully ✅

---

## API Reference Summary

### Pathao Location APIs
```
GET /api/pathao/cities                           // Get all cities
GET /api/pathao/cities/{cityId}/zones           // Get zones by city
GET /api/pathao/zones/{zoneId}/areas            // Get areas by zone
```

### Store Pathao APIs
```
GET  /api/pathao/stores/{storeId}/status        // Check registration status
POST /api/pathao/stores/{storeId}/register      // Register store with Pathao
PATCH /api/pathao/stores/{storeId}/config       // Update config without re-registering
```

### Shipment APIs (Existing)
```
POST /api/shipments                             // Create shipment
POST /api/shipments/{id}/send-to-pathao         // Send to Pathao (validates IDs)
GET  /api/shipments/{id}/sync-pathao-status     // Sync status from Pathao
```

---

## Rollout Strategy

### Recommended Implementation Order

1. **Week 1**: Store configuration (Admin feature)
   - Implement store Pathao settings page
   - Test with 1-2 pilot stores
   - Verify registration works

2. **Week 2**: Customer address Pathao location (Optional)
   - Add Pathao dropdowns to address form
   - Make it optional/skippable
   - Test with few customers

3. **Week 3**: Product weight (Quick win)
   - Add weight field to product form
   - Set defaults for existing products
   - Test shipment weight calculation

4. **Week 4**: Shipment creation with validation
   - Add Pathao validation warnings
   - Test shipment creation flow
   - Verify error messages

5. **Week 5**: Full rollout
   - Monitor Pathao API success rate
   - Train staff on new features
   - Gather user feedback

---

## Support & Troubleshooting

### Backend Team Contact
- Issues with API responses: [Contact Backend Team]
- Database migration problems: [Contact Backend Team]
- Pathao API errors: [Contact Backend Team]

### Testing Environment
- Use test Pathao credentials for development
- Create test stores and addresses
- Verify backward compatibility with existing data

---

## Summary

✅ **All changes are additive and optional**
✅ **Existing functionality continues working**
✅ **Implement gradually, test incrementally**
✅ **Clear error messages guide users**
✅ **Backward compatible with old data**

**Key Principle**: Users can continue using the system without implementing Pathao features. When they're ready, they can enable Pathao step-by-step without breaking existing workflows.
