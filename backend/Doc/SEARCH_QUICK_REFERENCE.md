# Product Search - Quick Reference

## 🔍 Search Endpoints

### 1. Advanced Search (Main Search)
```
POST /api/products/advanced-search
```
**Use for:** Full search with filters, multi-language, fuzzy matching

### 2. Quick Search
```
GET /api/products/quick-search?q={query}
```
**Use for:** Autocomplete, fast results

### 3. Search Suggestions
```
GET /api/products/search-suggestions?q={query}
```
**Use for:** Search bar dropdown suggestions

### 4. Search Stats
```
GET /api/products/search-stats
```
**Use for:** Analytics dashboard

---

## 📝 Quick Examples

### English Search
```json
POST /api/products/advanced-search
{
  "query": "potato chips"
}
```

### Bangla Unicode Search
```json
POST /api/products/advanced-search
{
  "query": "আলু"
}
```

### Romanized Bangla Search
```json
POST /api/products/advanced-search
{
  "query": "murgi"
}
```

### With Misspelling Tolerance
```json
POST /api/products/advanced-search
{
  "query": "chiken",
  "enable_fuzzy": true,
  "fuzzy_threshold": 70
}
```

### With Category Filter
```json
POST /api/products/advanced-search
{
  "query": "cha",
  "category_id": 3,
  "search_fields": ["name", "description"]
}
```

---

## 🎯 Key Features

✅ **English queries** - Standard search  
✅ **Bangla Unicode** - নেটিভ বাংলা সাপোর্ট  
✅ **Romanized Bangla** - "cha" → finds "চা"  
✅ **Misspellings** - "pottato" → finds "potato"  
✅ **Phonetic variations** - "chai" = "cha"  
✅ **Relevance scoring** - Best matches first  
✅ **Multi-field search** - Name, SKU, description, category  
✅ **Custom field search** - Search product attributes  

---

## 🌐 Supported Languages

### Bangla Common Words
| Roman | Bangla | English |
|-------|--------|---------|
| aloo/alu | আলু | potato |
| cha/chai | চা | tea |
| murgi | মুরগি | chicken |
| mach | মাছ | fish |
| dal | ডাল | lentil |
| doodh/dudh | দুধ | milk |
| bhat | ভাত | rice |
| peyaj/piaj | পেঁয়াজ | onion |
| morich | মরিচ | pepper |
| shobji | সবজি | vegetable |

---

## ⚙️ Configuration Options

### Request Parameters

```javascript
{
  "query": "search term",           // Required
  "category_id": 5,                 // Optional: filter by category
  "vendor_id": 2,                   // Optional: filter by vendor
  "is_archived": false,             // Optional: include archived (default: false)
  "enable_fuzzy": true,             // Optional: fuzzy matching (default: true)
  "fuzzy_threshold": 60,            // Optional: 50-100 (default: 60)
  "search_fields": [                // Optional: fields to search
    "name",
    "sku", 
    "description",
    "category",
    "custom_fields"
  ],
  "per_page": 15                    // Optional: 1-100 (default: 15)
}
```

---

## 📊 Search Scoring

**Base Scores:**
- Exact match: 100 points
- Starts with: 80 points
- Contains: 60 points
- Fuzzy match: 50-100 points (similarity %)

**Score Bonuses:**
- Exact name match: +50
- Exact SKU match: +40
- Name starts with term: +30
- SKU starts with term: +25
- Name contains term: +15
- Category match: +10

**Total Score = Base Score + All Bonuses**

Results sorted by total score (descending)

---

## 🚀 Performance Tips

1. **Use Quick Search for autocomplete** - Faster than advanced search
2. **Narrow search_fields** - Search only needed fields
3. **Increase fuzzy_threshold** - Reduce false positives (use 70-80)
4. **Disable fuzzy for large datasets** - Set `enable_fuzzy: false`
5. **Add database indexes** - Index name, SKU columns

---

## 🐛 Common Issues & Solutions

**No results found:**
- Check query spelling
- Try lowering fuzzy_threshold to 50
- Enable all search_fields
- Verify product is not archived

**Too many irrelevant results:**
- Increase fuzzy_threshold to 75-80
- Narrow search_fields scope
- Add category/vendor filters

**Slow search:**
- Use quick-search for autocomplete
- Add database indexes
- Disable fuzzy matching
- Reduce per_page limit

**Bangla not working:**
- Verify database charset is utf8mb4
- Check Content-Type: application/json header
- Ensure UTF-8 encoding in request

---

## 💡 Usage Patterns

### Pattern 1: Search Bar with Autocomplete
```javascript
// Show suggestions as user types
onInput: quick-search?q={query}&limit=5

// Full search on Enter/Submit
onSubmit: advanced-search with full query
```

### Pattern 2: Category Browse with Search
```javascript
// Filter products within category
{
  "query": "user input",
  "category_id": 5,
  "search_fields": ["name", "description"]
}
```

### Pattern 3: Exact Product Lookup
```javascript
// Search by SKU
{
  "query": "PROD-001",
  "search_fields": ["sku"],
  "enable_fuzzy": false
}
```

### Pattern 4: Multi-language Product Search
```javascript
// Works with any language input
{
  "query": "user_input",  // Can be English/Bangla/Roman
  "enable_fuzzy": true,
  "fuzzy_threshold": 65
}
```

---

## 📱 Mobile Integration

```kotlin
// Android Example
val request = JsonObjectRequest(
    Request.Method.POST,
    "https://api.example.com/api/products/advanced-search",
    JSONObject().apply {
        put("query", searchQuery)
        put("enable_fuzzy", true)
        put("per_page", 20)
    },
    { response -> 
        // Handle results
    },
    { error ->
        // Handle error
    }
)
```

---

## 🧪 Test Queries

Copy-paste these to test:

```bash
# English
curl -X POST /api/products/advanced-search -d '{"query":"chicken"}'

# Bangla Unicode
curl -X POST /api/products/advanced-search -d '{"query":"মুরগি"}'

# Romanized Bangla
curl -X POST /api/products/advanced-search -d '{"query":"murgi"}'

# Misspelling
curl -X POST /api/products/advanced-search -d '{"query":"chiken","enable_fuzzy":true}'

# Quick search
curl -X GET "/api/products/quick-search?q=chi&limit=5"

# Suggestions
curl -X GET "/api/products/search-suggestions?q=mu"
```

---

## 📖 Documentation

- Full docs: `Doc/PRODUCT_SEARCH_SYSTEM.md`
- Controller: `app/Http/Controllers/ProductSearchController.php`
- Routes: `routes/api.php` (search section)

---

**Quick Help:**
- All endpoints require JWT authentication
- Responses always include `success` boolean
- Errors return appropriate HTTP status codes
- Pagination included in all list responses

---

**Version:** 1.0  
**Last Updated:** November 9, 2025
