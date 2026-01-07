# Customer Tags API Documentation

## Overview

The Customer Tags feature allows you to categorize customers with flexible string tags like "regular", "loyal", "problematic", "vip", "at-risk", etc. Tags are stored as JSON arrays, indexed for efficient filtering.

**Base URL:** `/api/customers`

**Authentication:** All endpoints require `auth:api` middleware (Bearer token)

---

## Managing Tags

### Data Structure

Tags are stored as an array of strings:

```json
{
  "tags": ["regular", "loyal", "vip"]
}
```

### Create Customer with Tags

**Endpoint:** `POST /api/customers`

```json
{
  "customer_type": "social_commerce",
  "name": "John Doe",
  "phone": "01712345678",
  "email": "john@example.com",
  "tags": ["regular", "loyal"]
}
```

**Validation:**
- `tags` (optional): array
- `tags.*`: string, max 50 characters

### Update Customer Tags

**Endpoint:** `PUT /api/customers/{id}`

```json
{
  "tags": ["vip", "loyal", "high-spender"]
}
```

---

## Tag Management Endpoints

### Add Tags to Customer

**Endpoint:** `POST /api/customers/{id}/tags`

**Description:** Add one or more tags to a customer. Duplicates are automatically prevented.

**Request Body:**

```json
{
  "tags": ["vip", "high-spender"]
}
```

**Validation:**
- `tags` (required): array, minimum 1 item
- `tags.*` (required): string, max 50 characters

**Example Request:**

```bash
POST /api/customers/123/tags
Authorization: Bearer {token}
Content-Type: application/json

{
  "tags": ["vip", "loyal"]
}
```

**Response:**

```json
{
  "success": true,
  "message": "Tags added successfully",
  "data": {
    "id": 123,
    "tags": ["regular", "vip", "loyal"]
  }
}
```

---

### Remove Tags from Customer

**Endpoint:** `DELETE /api/customers/{id}/tags`

**Description:** Remove one or more tags from a customer.

**Request Body:**

```json
{
  "tags": ["problematic"]
}
```

**Validation:**
- `tags` (required): array, minimum 1 item
- `tags.*` (required): string

**Example Request:**

```bash
DELETE /api/customers/123/tags
Authorization: Bearer {token}
Content-Type: application/json

{
  "tags": ["problematic", "at-risk"]
}
```

**Response:**

```json
{
  "success": true,
  "message": "Tags removed successfully",
  "data": {
    "id": 123,
    "tags": ["regular", "loyal"]
  }
}
```

---

### Replace All Tags (Set Tags)

**Endpoint:** `PUT /api/customers/{id}/tags`

**Description:** Replace all existing tags with a new set of tags.

**Request Body:**

```json
{
  "tags": ["vip", "verified", "high-spender"]
}
```

**Validation:**
- `tags` (required): array (can be empty to clear all tags)
- `tags.*` (required): string, max 50 characters

**Example Request:**

```bash
PUT /api/customers/123/tags
Authorization: Bearer {token}
Content-Type: application/json

{
  "tags": ["vip", "verified"]
}
```

**Response:**

```json
{
  "success": true,
  "message": "Tags updated successfully",
  "data": {
    "id": 123,
    "tags": ["vip", "verified"]
  }
}
```

**Clear All Tags:**

```json
{
  "tags": []
}
```

---

### Get All Available Tags

**Endpoint:** `GET /api/customers/tags/all`

**Description:** Get a list of all unique tags currently used across all customers. Useful for building tag selection UI.

**Example Request:**

```bash
GET /api/customers/tags/all
Authorization: Bearer {token}
```

**Response:**

```json
{
  "success": true,
  "data": [
    "at-risk",
    "bargain-hunter",
    "high-spender",
    "loyal",
    "new",
    "problematic",
    "regular",
    "returns-frequently",
    "verified",
    "vip"
  ]
}
```

---

## Filtering Customers by Tags

### Filter by Single Tag

**Endpoint:** `GET /api/customers?tag=loyal`

Returns all customers that have the "loyal" tag.

**Example Request:**

```bash
GET /api/customers?tag=loyal
Authorization: Bearer {token}
```

### Filter by Multiple Tags (OR logic)

**Endpoint:** `GET /api/customers?tags=loyal,vip,regular`

Returns customers that have **ANY** of the specified tags.

**Example Request:**

```bash
GET /api/customers?tags=loyal,vip
Authorization: Bearer {token}
```

**Alternative (Array format):**

```bash
GET /api/customers?tags[]=loyal&tags[]=vip
Authorization: Bearer {token}
```

### Combined Filters

You can combine tag filtering with other customer filters:

```bash
GET /api/customers?customer_type=social_commerce&tags=loyal,vip&status=active&per_page=20
Authorization: Bearer {token}
```

---

## Social Commerce Phone Lookup

**Endpoint:** `GET /api/customers/find-by-phone`

**Description:** Find customer by phone number. Tags are automatically included in the response.

**Request:**

```json
{
  "phone": "01712345678"
}
```

**Response:**

```json
{
  "success": true,
  "data": {
    "id": 123,
    "customer_type": "social_commerce",
    "name": "John Doe",
    "phone": "01712345678",
    "email": "john@example.com",
    "customer_code": "CUST-2025-ABC123",
    "total_purchases": "15000.00",
    "total_orders": 12,
    "status": "active",
    "tags": ["regular", "loyal"],
    "created_by": {
      "id": 5,
      "name": "Sales Person"
    },
    "assigned_employee": {
      "id": 5,
      "name": "Sales Person"
    }
  }
}
```

---

## Model Helper Methods

The Customer model includes helper methods for tag management:

### Add Tag

```php
$customer->addTag('vip');
```

### Remove Tag

```php
$customer->removeTag('problematic');
```

### Set Tags (Replace All)

```php
$customer->setTags(['vip', 'loyal', 'high-spender']);
```

### Check if Customer Has Tag

```php
if ($customer->hasTag('vip')) {
    // Customer is VIP
}
```

### Check if Customer Has Any Tag

```php
if ($customer->hasAnyTag(['loyal', 'vip'])) {
    // Customer has at least one of these tags
}
```

### Check if Customer Has All Tags

```php
if ($customer->hasAllTags(['vip', 'verified', 'active'])) {
    // Customer has all three tags
}
```

---

## Common Tag Examples

### Customer Loyalty
- `new` - New customer (first purchase)
- `regular` - Regular customer
- `loyal` - Loyal customer (high frequency)
- `vip` - VIP customer (high value)
- `at-risk` - Customer at risk of churning

### Customer Behavior
- `problematic` - Problematic customer
- `returns-frequently` - Returns products frequently
- `high-spender` - Spends above average
- `bargain-hunter` - Looks for discounts

### Customer Status
- `verified` - Verified customer
- `unverified` - Unverified customer
- `blocked` - Temporarily blocked
- `blacklisted` - Permanently blocked

### Business Context
- `wholesale` - Wholesale buyer
- `reseller` - Reseller/distributor
- `influencer` - Social media influencer
- `referrer` - Refers other customers

---

## Database Technical Details

### PostgreSQL Implementation

- **Column Type:** `jsonb` (JSON Binary)
- **Index Type:** GIN (Generalized Inverted Index)
- **Query Operator:** `@>` (contains) and `?|` (contains any)

**Example Raw Query:**

```sql
-- Find customers with "loyal" tag
SELECT * FROM customers WHERE tags @> '["loyal"]';

-- Find customers with ANY of these tags
SELECT * FROM customers WHERE tags ?| array['loyal', 'vip'];
```

### MySQL Implementation

- **Column Type:** `json`
- **Index:** Not required (JSON_CONTAINS performs well)
- **Query Function:** `JSON_CONTAINS`

**Example Raw Query:**

```sql
-- Find customers with "loyal" tag
SELECT * FROM customers WHERE JSON_CONTAINS(tags, '"loyal"');
```

---

## Frontend Integration Examples
### Tag Management UI

```javascript
// Add tags to customer
async function addTags(customerId, tags) {
  const response = await fetch(`/api/customers/${customerId}/tags`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ tags })
  });
  
  const result = await response.json();
  console.log('Updated tags:', result.data.tags);
  return result;
}

// Remove tags from customer
async function removeTags(customerId, tags) {
  const response = await fetch(`/api/customers/${customerId}/tags`, {
    method: 'DELETE',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ tags })
  });
  
  return await response.json();
}

// Replace all tags
async function setTags(customerId, tags) {
  const response = await fetch(`/api/customers/${customerId}/tags`, {
    method: 'PUT',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ tags })
  });
  
  return await response.json();
}

// Get all available tags for dropdown
async function getAllTags() {
  const response = await fetch('/api/customers/tags/all', {
    headers: { 'Authorization': `Bearer ${token}` }
  });
  
  const { data: tags } = await response.json();
  return tags; // ["loyal", "vip", "regular", ...]
}

// Tag selector component example
function TagSelector({ customerId, currentTags }) {
  const [allTags, setAllTags] = useState([]);
  const [selectedTags, setSelectedTags] = useState(currentTags);
  
  useEffect(() => {
    getAllTags().then(setAllTags);
  }, []);
  
  const handleAddTag = (tag) => {
    addTags(customerId, [tag]).then(result => {
      setSelectedTags(result.data.tags);
    });
  };
  
  const handleRemoveTag = (tag) => {
    removeTags(customerId, [tag]).then(result => {
      setSelectedTags(result.data.tags);
    });
  };
  
  return (
    <div>
      <div className="current-tags">
        {selectedTags.map(tag => (
          <Badge key={tag}>
            {tag}
            <button onClick={() => handleRemoveTag(tag)}>×</button>
          </Badge>
        ))}
      </div>
      
      <Select onChange={e => handleAddTag(e.target.value)}>
        <option value="">Add tag...</option>
        {allTags.filter(tag => !selectedTags.includes(tag)).map(tag => (
          <option key={tag} value={tag}>{tag}</option>
        ))}
      </Select>
    </div>
  );
}
```
### Display Tags

```javascript
// Display tags as badges
customer.tags.map(tag => (
  <Badge key={tag} variant="secondary">{tag}</Badge>
))
```

### Filter UI

```javascript
// Multi-select filter
<Select multiple value={selectedTags} onChange={handleTagChange}>
  <option value="regular">Regular</option>
  <option value="loyal">Loyal</option>
  <option value="vip">VIP</option>
  <option value="at-risk">At Risk</option>
  <option value="problematic">Problematic</option>
</Select>

// Build API call
const queryParams = new URLSearchParams({
  tags: selectedTags.join(','),
  per_page: 20
});

fetch(`/api/customers?${queryParams}`, {
  headers: { 'Authorization': `Bearer ${token}` }
});
```

### Social Commerce Order Flow

```javascript
// Step 1: Find customer by phone
const response = await fetch('/api/customers/find-by-phone', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({ phone: '01712345678' })
});

const { data: customer } = await response.json();

// Step 2: Display customer info including tags
if (customer) {
  console.log('Customer Tags:', customer.tags);
  
  // Show special handling for VIP customers
  if (customer.tags.includes('vip')) {
    showVIPDiscountBanner();
  }
  
  // Warn for problematic customers
  if (customer.tags.includes('problematic')) {
    showWarningMessage('This customer has had issues before');
  }
}
```

---

## Best Practices

1. **Consistent Naming:** Use lowercase, hyphenated tags (`high-spender`, not `High Spender`)
2. **Limited Set:** Don't create too many unique tags - maintain a manageable list
3. **Document Tags:** Keep an internal document of what each tag means
4. **Automated Tagging:** Consider auto-tagging based on behavior (e.g., auto-add "loyal" after 10 orders)
5. **Tag Cleanup:** Periodically review and remove unused tags

---

## Performance Notes

- **Indexed:** Tags are indexed for fast filtering (GIN index for PostgreSQL)
- **Array Size:** Keep tag arrays reasonably small (recommended: max 10 tags per customer)
- **Query Performance:** Filtering by tags is efficient even with large customer tables

---

**Last Updated:** December 23, 2025  
**API Version:** 1.0
