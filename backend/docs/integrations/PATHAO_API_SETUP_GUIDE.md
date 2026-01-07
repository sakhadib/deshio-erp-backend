# Pathao API Integration - Setup & Troubleshooting

## Current Issue

**Problem:** Frontend team reports that Pathao API is not working - even the cities GET request fails.

**Root Cause:** Missing Pathao merchant account credentials (Username and Password).

---

## Why It's Not Working

Pathao API uses **OAuth2 Password Grant** authentication, which requires:
1. ✅ Client ID (provided: `ELe3QM9b69`)
2. ✅ Client Secret (provided: `34wMViuF691Ms80C2nWT8ofaTDpKmo7ZABME4EmH`)
3. ❌ **Username** (missing - your Pathao merchant username)
4. ❌ **Password** (missing - your Pathao merchant password)

Without username and password, the API cannot obtain an access token, so **all requests fail** including basic operations like fetching cities.

---

## How to Fix

### Step 1: Get Your Pathao Merchant Credentials

You need to retrieve your Pathao merchant account credentials:

1. **Login to Pathao Merchant Portal:**
   - Go to: https://merchant.pathao.com
   - Login with your merchant account

2. **Navigate to API Settings:**
   - Go to **Settings** → **API Settings** (or **Developer Settings**)
   - You should see:
     - Client ID (already have this)
     - Client Secret (already have this)
     - **Username** (copy this)
     - **Password** (copy this or reset if needed)

3. **Alternative:** Contact Pathao Support
   - If you can't find the credentials in the portal
   - Email: merchant.support@pathao.com
   - Phone: +880-9610001010
   - Request your API username and password

---

### Step 2: Update .env File

Add the credentials to your `.env` file:

```env
# Pathao Courier Configuration
PATHAO_BASE_URL=https://api-hermes.pathao.com
PATHAO_CLIENT_ID=ELe3QM9b69
PATHAO_CLIENT_SECRET=34wMViuF691Ms80C2nWT8ofaTDpKmo7ZABME4EmH
PATHAO_USERNAME=your_merchant_username_here
PATHAO_PASSWORD=your_merchant_password_here
PATHAO_STORE_ID=
PATHAO_SANDBOX=false
```

**Replace:**
- `your_merchant_username_here` with your actual Pathao username
- `your_merchant_password_here` with your actual Pathao password

---

### Step 3: Test the Connection

Run the diagnostic command:

```bash
php artisan pathao:test
```

**Expected Output (Success):**
```
=== Pathao API Connection Test ===

1. Checking configuration...
   Base URL: https://api-hermes.pathao.com
   Sandbox Mode: No
   Client ID: ✓ Set
   Client Secret: ✓ Set
   Username: ✓ Set
   Password: ✓ Set

2. Testing authentication...
   ✓ Authentication successful!
   Access Token: eyJ0eXAiOiJKV1QiLCJ...
   Token Type: Bearer
   Expires In: 3600 seconds

3. Testing city list endpoint...
   ✓ Successfully fetched 64 cities!
   First 5 cities:
   - Dhaka (ID: 1)
   - Chattogram (ID: 2)
   - Sylhet (ID: 3)
   - Rajshahi (ID: 4)
   - Khulna (ID: 5)

4. Testing store list endpoint...
   ✓ Successfully fetched 1 stores!
   Your stores:
   - Main Store (ID: 12345)

=== Test Complete ===
✓ Pathao API connection is working!
```

---

### Step 4: Create a Pathao Store (If Needed)

If you don't have a store created, create one:

**API Endpoint:** `POST /api/shipments/pathao/stores`

**Request Body:**
```json
{
  "name": "Your Store Name",
  "contact_name": "Manager Name",
  "contact_number": "01XXXXXXXXX",
  "address": "123 Store Street, Dhaka",
  "secondary_contact": "01XXXXXXXXX",
  "city_id": 1,
  "zone_id": 1,
  "area_id": 1
}
```

**To get city/zone/area IDs:**
1. Get cities: `GET /api/shipments/pathao/cities`
2. Get zones: `GET /api/shipments/pathao/zones/{cityId}`
3. Get areas: `GET /api/shipments/pathao/areas/{zoneId}`

**After creating store, update .env:**
```env
PATHAO_STORE_ID=12345
```

---

## Testing the API

### 1. Test City List (Basic Connectivity)
```bash
curl -X GET http://localhost:8000/api/shipments/pathao/cities \
  -H "Authorization: Bearer YOUR_AUTH_TOKEN"
```

### 2. Test Zone List
```bash
curl -X GET http://localhost:8000/api/shipments/pathao/zones/1 \
  -H "Authorization: Bearer YOUR_AUTH_TOKEN"
```

### 3. Test Area List
```bash
curl -X GET http://localhost:8000/api/shipments/pathao/areas/1 \
  -H "Authorization: Bearer YOUR_AUTH_TOKEN"
```

### 4. Test Store List
```bash
curl -X GET http://localhost:8000/api/shipments/pathao/stores \
  -H "Authorization: Bearer YOUR_AUTH_TOKEN"
```

---

## Available Pathao Endpoints

### Location Lookup
```
GET /api/shipments/pathao/cities           - Get all cities
GET /api/shipments/pathao/zones/{cityId}   - Get zones for city
GET /api/shipments/pathao/areas/{zoneId}   - Get areas for zone
```

### Store Management
```
GET  /api/shipments/pathao/stores          - List stores
POST /api/shipments/pathao/stores          - Create new store
```

### Shipment Operations
```
GET  /api/shipments                        - List shipments
POST /api/shipments                        - Create shipment
GET  /api/shipments/{id}                   - View shipment
POST /api/shipments/{id}/send-to-pathao    - Send to Pathao
GET  /api/shipments/{id}/sync-pathao-status - Sync status
```

### Bulk Operations
```
POST /api/shipments/bulk-send-to-pathao    - Bulk send to Pathao
POST /api/shipments/bulk-sync-pathao-status - Bulk sync status
```

---

## Common Issues & Solutions

### Issue 1: "Failed to get Pathao access token"
**Cause:** Wrong username/password  
**Solution:** 
- Double-check credentials in .env
- Ensure no extra spaces
- Try resetting password in Pathao portal

### Issue 2: "Unauthenticated" or "Token expired"
**Cause:** Invalid token or expired session  
**Solution:** 
- Token auto-refreshes every 50 minutes
- Clear cache: `php artisan cache:clear`
- Restart server

### Issue 3: "Store ID not found"
**Cause:** No store created or wrong store ID  
**Solution:**
- Create store via API or Pathao portal
- Get store ID from store list endpoint
- Update PATHAO_STORE_ID in .env

### Issue 4: Cities endpoint returns empty
**Cause:** Usually authentication failure  
**Solution:**
- Run `php artisan pathao:test` to diagnose
- Check Laravel logs: `storage/logs/laravel.log`
- Look for "Pathao Token Error" entries

---

## Environment Configuration Reference

```env
# Pathao Courier Configuration
PATHAO_BASE_URL=https://api-hermes.pathao.com   # Production API
PATHAO_CLIENT_ID=ELe3QM9b69                     # Your Client ID
PATHAO_CLIENT_SECRET=34wMViuF691Ms80C2nWT8ofaTDpKmo7ZABME4EmH  # Your Secret
PATHAO_USERNAME=                                 # ← ADD THIS
PATHAO_PASSWORD=                                 # ← ADD THIS
PATHAO_STORE_ID=                                 # Add after creating store
PATHAO_SANDBOX=false                             # false=production, true=testing
```

---

## Sandbox vs Production

### Sandbox (Testing)
```env
PATHAO_SANDBOX=true
PATHAO_BASE_URL=https://staging-api-hermes.pathao.com
```
- Use for development/testing
- Separate credentials needed
- No real deliveries created

### Production (Live)
```env
PATHAO_SANDBOX=false
PATHAO_BASE_URL=https://api-hermes.pathao.com
```
- Real deliveries
- Actual charges applied
- Use merchant credentials

---

## Authentication Flow

1. **Request Token:**
   ```
   POST https://api-hermes.pathao.com/aladdin/api/v1/issue-token
   Body: { client_id, client_secret, username, password, grant_type: "password" }
   Response: { access_token, expires_in: 3600 }
   ```

2. **Cache Token:**
   - Token cached for 50 minutes (auto-refresh before expiry)
   - Cache key: `pathao_access_token`

3. **Use Token:**
   ```
   GET https://api-hermes.pathao.com/aladdin/api/v1/countries/1/city-list
   Header: Authorization: Bearer {access_token}
   ```

---

## Checklist

Before deploying Pathao integration:

- [ ] Client ID added to .env
- [ ] Client Secret added to .env
- [ ] **Username added to .env** ← **MISSING**
- [ ] **Password added to .env** ← **MISSING**
- [ ] Run `php artisan pathao:test` - should pass all checks
- [ ] At least one store created
- [ ] Store ID added to .env
- [ ] Test city list endpoint works
- [ ] Frontend can fetch cities/zones/areas
- [ ] Test shipment creation (without sending to Pathao)

---

## Next Steps

1. **Get credentials from Pathao merchant portal**
2. **Update .env file with username and password**
3. **Run `php artisan pathao:test` to verify**
4. **Create a store if needed**
5. **Test API endpoints from frontend**
6. **Create test shipments**

---

## Support & Resources

- **Pathao Merchant Portal:** https://merchant.pathao.com
- **Pathao Support Email:** merchant.support@pathao.com
- **Pathao Support Phone:** +880-9610001010
- **Package Documentation:** https://github.com/codeboxrcodehub/pathao-courier
- **Laravel Package:** `codeboxr/pathao-courier` v1.0

---

## Quick Fix Summary

**The issue is simple:** Pathao API requires username and password which are currently missing.

**To fix:**
1. Get username/password from Pathao merchant portal
2. Add to .env:
   ```
   PATHAO_USERNAME=your_username
   PATHAO_PASSWORD=your_password
   ```
3. Test: `php artisan pathao:test`
4. Done! API will work.

**Tell frontend team:** "API will work once we add the merchant username and password to the server. Working on getting those credentials now."
