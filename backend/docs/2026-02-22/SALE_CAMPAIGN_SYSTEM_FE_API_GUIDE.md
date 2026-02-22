# Sale Campaign System - Frontend API Guide

**Date:** February 22, 2026  
**Feature:** Automatic Discount & Sale Campaign System APIs  
**Backend Update:** v2.22.0

---

## 📋 Overview

This document describes the API changes and new endpoints for the automatic sale campaign system. This system allows automatic discounts to be applied system-wide across POS, eCommerce, and social commerce platforms.

---

## 🆕 New API Endpoints

### 1. Get Active Campaigns (PUBLIC - No Auth Required)

**Endpoint:** `GET /api/campaigns/active`

**Purpose:** Fetch currently active automatic sale campaigns. Use this to display banners, badges, or campaign information on eCommerce/social commerce platforms.

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `product_ids` | array | No | Filter campaigns by specific product IDs |
| `category_ids` | array | No | Filter campaigns by specific category IDs |

**Request Example:**
```http
GET /api/campaigns/active?product_ids[]=101&product_ids[]=102
GET /api/campaigns/active?category_ids[]=5&category_ids[]=7
GET /api/campaigns/active
```

**Response Example:**
```json
{
  "success": true,
  "data": [
    {
      "id": 15,
      "name": "Winter Sale 2026",
      "description": "Get 20% off on all winter clothing",
      "code": "WINTER20",
      "type": "percentage",
      "discount_value": 20.00,
      "start_date": "2026-02-01T00:00:00+06:00",
      "end_date": "2026-02-28T23:59:59+06:00",
      "applicable_products": [101, 102, 103],
      "applicable_categories": [5, 7]
    }
  ]
}
```

**Usage:**
- Display active campaigns on homepage
- Show campaign banners above product listings
- Filter campaigns by product/category to show relevant sales

---

### 2. Calculate Automatic Discount (PUBLIC - No Auth Required)

**Endpoint:** `POST /api/campaigns/calculate-discount`

**Purpose:** Calculate total automatic discounts for a shopping cart. This is useful if you want to preview discounts before checkout.

**Request Body:**
```json
{
  "items": [
    {
      "product_id": 101,
      "quantity": 2,
      "unit_price": 1000.00
    },
    {
      "product_id": 102,
      "quantity": 1,
      "unit_price": 500.00
    }
  ]
}
```

**Response Example:**
```json
{
  "success": true,
  "data": {
    "total_discount": 450.00,
    "items": [
      {
        "product_id": 101,
        "quantity": 2,
        "unit_price": 1000.00,
        "original_price": 1000.00,
        "discounted_price": 800.00,
        "discount_amount_per_unit": 200.00,
        "discount_amount_total": 400.00,
        "discount_percentage": 20.00,
        "active_campaign": {
          "id": 15,
          "name": "Winter Sale 2026",
          "code": "WINTER20",
          "type": "percentage",
          "value": 20.00,
          "start_date": "2026-02-01T00:00:00+06:00",
          "end_date": "2026-02-28T23:59:59+06:00"
        }
      },
      {
        "product_id": 102,
        "quantity": 1,
        "unit_price": 500.00,
        "original_price": 500.00,
        "discounted_price": 450.00,
        "discount_amount_per_unit": 50.00,
        "discount_amount_total": 50.00,
        "discount_percentage": 10.00,
        "active_campaign": {
          "id": 16,
          "name": "Flash Sale",
          "code": "FLASH10",
          "type": "fixed",
          "value": 50.00,
          "start_date": "2026-02-22T00:00:00+06:00",
          "end_date": null
        }
      }
    ],
    "campaigns_applied": [
      {
        "id": 15,
        "name": "Winter Sale 2026",
        "code": "WINTER20",
        "type": "percentage",
        "value": 20.00,
        "start_date": "2026-02-01T00:00:00+06:00",
        "end_date": "2026-02-28T23:59:59+06:00"
      },
      {
        "id": 16,
        "name": "Flash Sale",
        "code": "FLASH10",
        "type": "fixed",
        "value": 50.00,
        "start_date": "2026-02-22T00:00:00+06:00",
        "end_date": null
      }
    ]
  }
}
```

**Usage:**
- Preview discounts in shopping cart
- Show discount breakdown before checkout
- Display which campaigns are applying to the order

---

### 3. Get Active Discounts for Products (PUBLIC - No Auth Required)

**Endpoint:** `GET /api/campaigns/product-discounts`

**Purpose:** Get active discounts for specific products. Use this on product detail pages to show if a product has an active discount.

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `product_ids` | array | Yes | Array of product IDs to check for discounts |

**Request Example:**
```http
GET /api/campaigns/product-discounts?product_ids[]=101&product_ids[]=102&product_ids[]=103
```

**Response Example:**
```json
{
  "success": true,
  "data": {
    "101": [
      {
        "id": 15,
        "name": "Winter Sale 2026",
        "type": "percentage",
        "discount_value": 20.00
      }
    ],
    "102": [
      {
        "id": 16,
        "name": "Flash Sale",
        "type": "fixed",
        "discount_value": 50.00
      }
    ],
    "103": []
  }
}
```

**Usage:**
- Show discount badges on product cards
- Display "On Sale" labels
- Show discount percentage/amount on product pages

---

## 🔄 Modified API Responses

### 1. Product Catalog API (`GET /api/ecommerce/products`)

**What Changed:**  
Product listing now includes automatic discount information in the response.

**New Fields in Response:**

```json
{
  "success": true,
  "data": {
    "products": [
      {
        "id": 101,
        "name": "Winter Jacket - Black",
        "sku": "WJ-BLK-001",
        "original_price": 5000.00,
        "selling_price": 4000.00,
        "discount_amount": 1000.00,
        "discount_percentage": 20.00,
        "has_discount": true,
        "active_campaign": {
          "id": 15,
          "name": "Winter Sale 2026",
          "code": "WINTER20",
          "type": "percentage",
          "value": 20.00,
          "start_date": "2026-02-01T00:00:00+06:00",
          "end_date": "2026-02-28T23:59:59+06:00"
        },
        "price_display": "4,000.00 BDT",
        "stock_quantity": 50,
        "in_stock": true,
        "images": [...],
        "category": {...}
      }
    ]
  }
}
```

**Field Descriptions:**

| Field | Type | Description |
|-------|------|-------------|
| `original_price` | number | Original price before discount |
| `selling_price` | number | Price after automatic discount (if any) |
| `discount_amount` | number | Total discount amount applied |
| `discount_percentage` | number | Discount percentage (calculated) |
| `has_discount` | boolean | `true` if automatic discount is active |
| `active_campaign` | object/null | Campaign details if discount is active |
| `price_display` | string | Formatted price string for display |

**Variants Also Include Discounts:**

```json
{
  "variants": [
    {
      "id": 102,
      "name": "Winter Jacket - Blue",
      "variation_suffix": "Blue",
      "original_price": 5000.00,
      "selling_price": 4000.00,
      "discount_amount": 1000.00,
      "discount_percentage": 20.00,
      "has_discount": true,
      "stock_quantity": 30,
      "in_stock": true
    }
  ]
}
```

**UI Implementation Suggestions:**

```jsx
// Product Card Example
{product.has_discount ? (
  <div className="price">
    <span className="original-price strikethrough">
      ৳{product.original_price.toLocaleString()}
    </span>
    <span className="discounted-price">
      ৳{product.selling_price.toLocaleString()}
    </span>
    <span className="discount-badge">
      {product.discount_percentage}% OFF
    </span>
  </div>
) : (
  <div className="price">
    ৳{product.selling_price.toLocaleString()}
  </div>
)}

// Campaign Badge
{product.active_campaign && (
  <div className="campaign-badge">
    {product.active_campaign.name}
  </div>
)}
```

---

### 2. eCommerce Checkout API (`POST /api/customer/ecommerce-orders/checkout`)

**What Changed:**  
Automatic discounts are now calculated and applied during checkout. The API response includes discount breakdown.

**Existing Behavior:**
- Coupon code discounts still work (`coupon_code` field)
- **NEW:** Automatic campaign discounts are applied in addition to coupon codes

**Request (No Changes):**
```json
{
  "shipping_address_id": 5,
  "billing_address_id": 5,
  "payment_method": "cod",
  "coupon_code": "EXTRA10",
  "notes": "Deliver after 5 PM"
}
```

**Response Changes:**

Order metadata now includes:
```json
{
  "order": {
    "id": 1234,
    "order_number": "ORD-20260222-ABC123",
    "subtotal": 10000.00,
    "discount_amount": 2100.00,
    "total_amount": 7900.00,
    "metadata": {
      "automatic_discount": 2000.00,
      "coupon_discount": 100.00,
      "campaigns_applied": [
        {
          "id": 15,
          "name": "Winter Sale 2026",
          "code": "WINTER20",
          "type": "percentage",
          "value": 20.00
        }
      ]
    }
  }
}
```

Order items now include per-item discounts:
```json
{
  "items": [
    {
      "product_id": 101,
      "product_name": "Winter Jacket",
      "quantity": 2,
      "unit_price": 5000.00,
      "discount_amount": 2000.00,
      "total_amount": 8000.00
    }
  ]
}
```

**UI Implementation:**

```jsx
// Checkout Summary
<div className="order-summary">
  <div className="line">
    <span>Subtotal:</span>
    <span>৳{order.subtotal.toLocaleString()}</span>
  </div>
  
  {order.metadata.automatic_discount > 0 && (
    <div className="line discount">
      <span>Campaign Discount:</span>
      <span>-৳{order.metadata.automatic_discount.toLocaleString()}</span>
    </div>
  )}
  
  {order.metadata.coupon_discount > 0 && (
    <div className="line discount">
      <span>Coupon Discount:</span>
      <span>-৳{order.metadata.coupon_discount.toLocaleString()}</span>
    </div>
  )}
  
  <div className="line total">
    <span>Total:</span>
    <span>৳{order.total_amount.toLocaleString()}</span>
  </div>
</div>

// Applied Campaigns
{order.metadata.campaigns_applied?.length > 0 && (
  <div className="campaigns-applied">
    <h4>Active Campaigns:</h4>
    {order.metadata.campaigns_applied.map(campaign => (
      <div key={campaign.id} className="campaign-badge">
        {campaign.name}
      </div>
    ))}
  </div>
)}
```

---

### 3. POS Order Creation API (`POST /api/employee/orders`)

**What Changed:**  
Automatic discounts are calculated and applied to POS orders.

**Existing Behavior:**
- Manual discount field (`discount_amount`) still works
- **NEW:** Automatic campaign discounts are added to manual discounts

**Request (No Changes):**
```json
{
  "order_type": "counter",
  "customer_id": 123,
  "items": [
    {
      "product_id": 101,
      "batch_id": 50,
      "quantity": 2,
      "unit_price": 5000.00,
      "discount_amount": 100.00
    }
  ],
  "discount_amount": 200.00,
  "payment": {
    "payment_method_id": 1,
    "amount": 9700.00
  }
}
```

**Response Changes:**

Order discount is now the sum of manual + automatic:
```json
{
  "order": {
    "discount_amount": 2200.00,
    "notes": "Manual: 200, Automatic: 2000"
  }
}
```

Order items include combined discounts:
```json
{
  "items": [
    {
      "discount_amount": 2100.00,
      "notes": "Manual: 100, Automatic: 2000"
    }
  ]
}
```

**Important:** Frontend does NOT need to calculate automatic discounts. Backend applies them automatically.

---

## 🔐 Employee Panel API Changes

### Promotion Management APIs (Existing endpoints now support `is_automatic` field)

**Create Promotion:** `POST /api/employee/promotions`

**New Field:**
```json
{
  "name": "Winter Sale 2026",
  "type": "percentage",
  "discount_value": 20.00,
  "applicable_categories": [5, 7],
  "start_date": "2026-02-01 00:00:00",
  "end_date": "2026-02-28 23:59:59",
  "is_active": true,
  "is_automatic": true,
  "is_public": true
}
```

**Field Descriptions:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `is_automatic` | boolean | No | If `true`, discount applies automatically without code. If `false`, acts as coupon (requires code input). Default: `false` |
| `is_public` | boolean | No | If `true`, campaign appears in public API. If `false`, internal only. Default: `false` |

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 15,
    "code": "WINTER20",
    "name": "Winter Sale 2026",
    "type": "percentage",
    "discount_value": 20.00,
    "applicable_products": null,
    "applicable_categories": [5, 7],
    "start_date": "2026-02-01T00:00:00+06:00",
    "end_date": "2026-02-28T23:59:59+06:00",
    "is_active": true,
    "is_automatic": true,
    "is_public": true,
    "created_by": 10,
    "created_at": "2026-02-22T15:30:00+06:00"
  }
}
```

**List Promotions:** `GET /api/employee/promotions`

**New Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `is_automatic` | boolean | Filter by automatic campaigns only |

**Example:**
```http
GET /api/employee/promotions?is_automatic=true&is_active=true&valid_only=true
```

---

## 📊 Discount Logic & Behavior

### 1. Discount Priority

When multiple discounts apply to the same product:

1. **Automatic campaign discount** (highest among active campaigns)
2. **Manual discount** (if provided in request)
3. **Coupon code discount** (if provided)

**Formula:**
```
Total Discount = max(campaign_discount_for_product) + manual_discount + coupon_discount
```

**Example:**

Product Price: ৳5000  
Campaign 1: 20% off = ৳1000  
Campaign 2: 10% off = ৳500  
Manual discount: ৳100  
Coupon discount: ৳50  

**Applied Discount:**
```
Campaign: ৳1000 (highest)
Manual: ৳100
Coupon: ৳50
Total: ৳1150
Final Price: ৳3850
```

### 2. Campaign Eligibility

A campaign applies to a product if:

✅ `is_automatic = true`  
✅ `is_active = true`  
✅ `start_date <= now()`  
✅ `end_date >= now()` OR `end_date = null`  
✅ Product ID is in `applicable_products` OR  
✅ Product's category ID is in `applicable_categories`

### 3. Discount Types

**Percentage Discount:**
```
discount_amount = (price × discount_value) / 100
if (maximum_discount && discount_amount > maximum_discount) {
  discount_amount = maximum_discount
}
```

**Fixed Amount Discount:**
```
discount_amount = discount_value
if (discount_amount > price) {
  discount_amount = price  // Cannot discount more than price
}
```

---

## 🎨 UI/UX Recommendations

### Product Listing Page

```jsx
// Show discount badge
{product.has_discount && (
  <span className="badge badge-sale">
    {product.discount_percentage}% OFF
  </span>
)}

// Show price with strikethrough
{product.has_discount ? (
  <>
    <span className="old-price">৳{product.original_price}</span>
    <span className="new-price">৳{product.selling_price}</span>
  </>
) : (
  <span className="price">৳{product.selling_price}</span>
)}

// Show campaign name
{product.active_campaign && (
  <div className="campaign-tag">
    {product.active_campaign.name}
  </div>
)}
```

### Product Detail Page

```jsx
// Price section
<div className="product-price">
  {product.has_discount ? (
    <>
      <div className="original-price strikethrough">
        ৳{product.original_price.toLocaleString()}
      </div>
      <div className="discounted-price">
        ৳{product.selling_price.toLocaleString()}
      </div>
      <div className="savings">
        You Save: ৳{product.discount_amount.toLocaleString()} ({product.discount_percentage}%)
      </div>
    </>
  ) : (
    <div className="price">
      ৳{product.selling_price.toLocaleString()}
    </div>
  )}
</div>

// Campaign banner
{product.active_campaign && (
  <div className="campaign-banner">
    <span className="icon">🔥</span>
    <span>{product.active_campaign.name}</span>
    {product.active_campaign.end_date && (
      <span className="expiry">
        Ends: {formatDate(product.active_campaign.end_date)}
      </span>
    )}
  </div>
)}
```

### Shopping Cart

```jsx
// Cart item with discount
<div className="cart-item">
  <div className="item-details">
    <h4>{item.product_name}</h4>
    <div className="pricing">
      {item.has_discount ? (
        <>
          <span className="unit-price">
            ৳{item.original_price} × {item.quantity}
          </span>
          <span className="discount">
            -৳{item.discount_amount_total}
          </span>
          <span className="subtotal">
            ৳{(item.original_price * item.quantity - item.discount_amount_total).toLocaleString()}
          </span>
        </>
      ) : (
        <span className="subtotal">
          ৳{(item.unit_price * item.quantity).toLocaleString()}
        </span>
      )}
    </div>
  </div>
</div>

// Cart summary
<div className="cart-summary">
  <div className="line">
    <span>Subtotal:</span>
    <span>৳{subtotal}</span>
  </div>
  
  {automaticDiscount > 0 && (
    <div className="line discount-line">
      <span>Sale Discount:</span>
      <span className="discount-amount">-৳{automaticDiscount}</span>
    </div>
  )}
  
  {couponDiscount > 0 && (
    <div className="line discount-line">
      <span>Coupon ({couponCode}):</span>
      <span className="discount-amount">-৳{couponDiscount}</span>
    </div>
  )}
  
  <div className="line total-line">
    <span>Total:</span>
    <span>৳{total}</span>
  </div>
</div>

// Active campaigns in cart
{campaignsApplied.length > 0 && (
  <div className="active-campaigns">
    <h4>Active Offers:</h4>
    {campaignsApplied.map(campaign => (
      <div key={campaign.id} className="campaign-pill">
        <span className="icon">🎁</span>
        <span>{campaign.name}</span>
      </div>
    ))}
  </div>
)}
```

### Employee Panel - Campaign Management

```jsx
// Campaign form
<Form>
  <FormGroup>
    <Label>Campaign Type</Label>
    <Toggle
      label="Automatic Discount"
      name="is_automatic"
      helpText="If enabled, discount applies automatically without requiring a code"
    />
  </FormGroup>
  
  <FormGroup>
    <Label>Discount Type</Label>
    <Select name="type">
      <option value="percentage">Percentage</option>
      <option value="fixed">Fixed Amount</option>
    </Select>
  </FormGroup>
  
  <FormGroup>
    <Label>Discount Value</Label>
    <Input 
      type="number" 
      name="discount_value"
      placeholder={formData.type === 'percentage' ? '20' : '500'}
      suffix={formData.type === 'percentage' ? '%' : '৳'}
    />
  </FormGroup>
  
  <FormGroup>
    <Label>Apply To</Label>
    <Tabs>
      <Tab title="Specific Products">
        <ProductSelector 
          name="applicable_products"
          multiple
        />
      </Tab>
      <Tab title="Categories">
        <CategorySelector 
          name="applicable_categories"
          multiple
        />
      </Tab>
    </Tabs>
  </FormGroup>
  
  <FormGroup>
    <Label>Campaign Duration</Label>
    <DateRangePicker
      startDateName="start_date"
      endDateName="end_date"
      allowNull
      nullLabel="No end date (Indefinite)"
    />
  </FormGroup>
  
  <FormGroup>
    <Toggle
      label="Activate Campaign"
      name="is_active"
    />
  </FormGroup>
</Form>

// Campaign list
<Table>
  <Thead>
    <Tr>
      <Th>Name</Th>
      <Th>Type</Th>
      <Th>Discount</Th>
      <Th>Target</Th>
      <Th>Duration</Th>
      <Th>Status</Th>
      <Th>Actions</Th>
    </Tr>
  </Thead>
  <Tbody>
    {campaigns.map(campaign => (
      <Tr key={campaign.id}>
        <Td>
          {campaign.name}
          {campaign.is_automatic && (
            <Badge color="blue">Auto</Badge>
          )}
        </Td>
        <Td>
          {campaign.type === 'percentage' 
            ? `${campaign.discount_value}%` 
            : `৳${campaign.discount_value}`}
        </Td>
        <Td>
          {campaign.applicable_products?.length || 0} Products
          {campaign.applicable_categories?.length || 0} Categories
        </Td>
        <Td>
          {formatDate(campaign.start_date)} - 
          {campaign.end_date ? formatDate(campaign.end_date) : 'Indefinite'}
        </Td>
        <Td>
          {campaign.is_active ? (
            <Badge color="green">Active</Badge>
          ) : (
            <Badge color="gray">Inactive</Badge>
          )}
        </Td>
        <Td>
          <Button 
            onClick={() => toggleCampaign(campaign.id)}
            size="sm"
          >
            {campaign.is_active ? 'Deactivate' : 'Activate'}
          </Button>
        </Td>
      </Tr>
    ))}
  </Tbody>
</Table>
```

---

## 🧪 Testing Checklist

### Frontend Testing

- [ ] Product listing shows discounted prices
- [ ] Discount badges appear on sale items
- [ ] Original price shows with strikethrough
- [ ] Campaign name/details display correctly
- [ ] Cart shows automatic discounts
- [ ] Checkout summary includes discount breakdown
- [ ] Multiple campaigns on same product work
- [ ] Coupon + automatic discount combination works
- [ ] POS order creation applies automatic discounts
- [ ] Employee panel campaign CRUD works
- [ ] Campaign activation/deactivation works
- [ ] Date filters work correctly

### API Testing

```bash
# Test active campaigns
curl -X GET "http://your-api.com/api/campaigns/active"

# Test discount calculation
curl -X POST "http://your-api.com/api/campaigns/calculate-discount" \
  -H "Content-Type: application/json" \
  -d '{
    "items": [
      {"product_id": 101, "quantity": 2, "unit_price": 1000}
    ]
  }'

# Test product discounts
curl -X GET "http://your-api.com/api/campaigns/product-discounts?product_ids[]=101"

# Test product catalog with discounts
curl -X GET "http://your-api.com/api/ecommerce/products"
```

---

## 🚀 Implementation Timeline

### Phase 1: Basic Integration (Day 1)
- [ ] Update product listing UI to show discounts
- [ ] Add discount badges, price strikethrough
- [ ] Test with sample campaigns

### Phase 2: Cart & Checkout (Day 2)
- [ ] Update cart to show discount breakdown
- [ ] Update checkout summary
- [ ] Test combined discounts (automatic + coupon)

### Phase 3: Employee Panel (Day 3)
- [ ] Add campaign creation UI
- [ ] Add campaign list with filters
- [ ] Add activate/deactivate buttons

### Phase 4: Polish & Testing (Day 4)
- [ ] Add campaign banners, badges
- [ ] Add countdown timers for expiring campaigns
- [ ] Comprehensive testing

---

## ❓ FAQs

**Q: Do I need to calculate discounts in frontend?**  
A: No. Backend automatically calculates and applies discounts. Frontend just displays the values.

**Q: Can automatic discount and coupon code work together?**  
A: Yes. Both are applied. Total discount = automatic + coupon.

**Q: What if a product has multiple campaigns?**  
A: Backend automatically picks the highest discount.

**Q: How do I show "Sale Ends In 2 Hours"?**  
A: Use `active_campaign.end_date` and calculate countdown in frontend.

**Q: Should I send `is_automatic` in checkout API?**  
A: No. Checkout API remains unchanged. Backend applies automatic discounts automatically.

**Q: How to test if discount is working?**  
A: Create a test campaign, check product listing API, verify `has_discount = true`.

---

## 📞 Support

For questions or issues:
- Backend API issues: Contact backend team
- Frontend implementation: Refer to this guide
- Business logic questions: Contact PM

**Status:** Ready for frontend integration ✅
