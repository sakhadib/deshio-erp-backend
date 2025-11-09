# Product Multi-Language Search System

## Overview

Comprehensive product search system supporting:
- **English queries** (standard Latin characters)
- **Bangla Unicode queries** (বাংলা ইউনিকোড)
- **Romanized Bangla queries** (e.g., "cha", "murgi", "aloo")
- **Misspelling tolerance** (fuzzy matching with Levenshtein distance)
- **Phonetic variations** (handles different spellings of same sound)

---

## Features

### 🔍 Search Algorithms
1. **Multi-stage search**
   - Stage 1: Exact match (100% relevance)
   - Stage 2: Starts with (80% relevance)
   - Stage 3: Contains (60% relevance)
   - Stage 4: Fuzzy match (configurable threshold)

2. **Bangla transliteration**
   - Automatic Bangla → Roman conversion
   - Common Roman → Bangla word mapping
   - Phonetic variation generation

3. **Fuzzy matching**
   - Levenshtein distance algorithm
   - Similar text percentage matching
   - Configurable similarity threshold (default: 60%)

4. **Relevance scoring**
   - Multi-factor scoring system
   - Location-based boost (name, SKU, description, category)
   - Sorted by relevance score

---

## API Endpoints

### 1. Advanced Search
**Endpoint:** `POST /api/products/advanced-search`

**Description:** Full-featured search with multi-language support, fuzzy matching, and relevance scoring.

**Request Body:**
```json
{
  "query": "আলু",
  "category_id": 5,
  "vendor_id": 2,
  "is_archived": false,
  "enable_fuzzy": true,
  "fuzzy_threshold": 60,
  "search_fields": ["name", "sku", "description", "category", "custom_fields"],
  "per_page": 15
}
```

**Parameters:**
- `query` (required): Search term (English, Bangla, or Romanized)
- `category_id` (optional): Filter by category
- `vendor_id` (optional): Filter by vendor
- `is_archived` (optional): Include archived products (default: false)
- `enable_fuzzy` (optional): Enable fuzzy matching (default: true)
- `fuzzy_threshold` (optional): Similarity threshold 50-100 (default: 60)
- `search_fields` (optional): Fields to search in (default: all)
  - Options: `name`, `sku`, `description`, `category`, `custom_fields`
- `per_page` (optional): Results per page, 1-100 (default: 15)

**Response:**
```json
{
  "success": true,
  "query": "আলু",
  "search_terms": [
    "আলু",
    "aloo",
    "alu"
  ],
  "total_results": 12,
  "data": {
    "items": [
      {
        "id": 45,
        "name": "আলু (পটেটো)",
        "sku": "VEG-001",
        "category": {
          "id": 5,
          "name": "সবজি"
        },
        "vendor": {
          "id": 2,
          "name": "Fresh Farms Ltd"
        },
        "search_stage": "exact",
        "base_score": 100,
        "relevance_score": 180,
        "custom_fields": [
          {
            "field_title": "Weight",
            "value": "1 kg"
          }
        ]
      }
    ],
    "pagination": {
      "total": 12,
      "per_page": 15,
      "current_page": 1,
      "last_page": 1,
      "from": 1,
      "to": 12
    }
  },
  "search_metadata": {
    "fuzzy_enabled": true,
    "fuzzy_threshold": 60,
    "search_fields": ["name", "sku", "description", "category", "custom_fields"]
  }
}
```

**Example Queries:**

1. **English:**
```bash
curl -X POST http://localhost:8000/api/products/advanced-search \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"query": "potato"}'
```

2. **Bangla Unicode:**
```bash
curl -X POST http://localhost:8000/api/products/advanced-search \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"query": "আলু"}'
```

3. **Romanized Bangla:**
```bash
curl -X POST http://localhost:8000/api/products/advanced-search \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"query": "aloo"}'
```

4. **With Misspelling:**
```bash
curl -X POST http://localhost:8000/api/products/advanced-search \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"query": "pottato", "enable_fuzzy": true, "fuzzy_threshold": 70}'
```

---

### 2. Quick Search
**Endpoint:** `GET /api/products/quick-search?q=query`

**Description:** Fast autocomplete-style search for real-time suggestions.

**Query Parameters:**
- `q` (required): Search query (min 1 character)
- `limit` (optional): Max results, 1-20 (default: 10)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 45,
      "name": "আলু (পটেটো)",
      "sku": "VEG-001",
      "category": "সবজি",
      "vendor": "Fresh Farms Ltd"
    },
    {
      "id": 46,
      "name": "Potato Chips",
      "sku": "SNK-012",
      "category": "Snacks",
      "vendor": "Pran Foods"
    }
  ]
}
```

**Example:**
```bash
curl -X GET "http://localhost:8000/api/products/quick-search?q=alu&limit=5" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

### 3. Search Suggestions
**Endpoint:** `GET /api/products/search-suggestions?q=query`

**Description:** Get search suggestions with relevance scoring.

**Query Parameters:**
- `q` (required): Partial search query
- `limit` (optional): Max suggestions, 1-10 (default: 5)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "text": "আলু (পটেটো)",
      "type": "product",
      "relevance": 90
    },
    {
      "text": "সবজি",
      "type": "category",
      "relevance": 75
    }
  ]
}
```

**Example:**
```bash
curl -X GET "http://localhost:8000/api/products/search-suggestions?q=al&limit=5" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

### 4. Search Statistics
**Endpoint:** `GET /api/products/search-stats`

**Description:** Get search analytics and product statistics.

**Response:**
```json
{
  "success": true,
  "data": {
    "total_products": 245,
    "total_categories": 15,
    "products_by_category": [
      {
        "category": {
          "id": 5,
          "title": "সবজি"
        },
        "count": 45
      }
    ],
    "recent_products": [
      {
        "id": 250,
        "name": "Fresh Tomatoes",
        "sku": "VEG-050",
        "category_id": 5
      }
    ]
  }
}
```

---

## Search Algorithm Details

### Multi-Stage Search Process

```
1. Input Query: "murgi"

2. Prepare Search Terms:
   - Original: "murgi"
   - Lowercase: "murgi"
   - Roman to Bangla: "মুরগি"
   - Phonetic variations: ["murgi", "moorgi", "murgi"]

3. Execute Searches:
   Stage 1: Exact Match
     → name = "murgi" OR sku = "murgi"
   
   Stage 2: Starts With
     → name LIKE "murgi%" OR sku LIKE "murgi%"
   
   Stage 3: Contains
     → name LIKE "%murgi%" OR sku LIKE "%murgi%"
     → category.name LIKE "%murgi%"
     → custom_fields.value LIKE "%murgi%"
   
   Stage 4: Fuzzy Match (if <10 results)
     → Calculate similarity score for all products
     → Return matches above threshold (default: 60%)

4. Score Results:
   Base Score:
     - Exact match: 100
     - Starts with: 80
     - Contains: 60
     - Fuzzy: 50-100 (similarity percentage)
   
   Bonuses:
     - Exact name match: +50
     - Exact SKU match: +40
     - Name starts with term: +30
     - SKU starts with term: +25
     - Name contains term: +15
     - Category match: +10

5. Sort by relevance_score (descending)

6. Paginate and return
```

---

## Bangla Language Support

### Transliteration Mappings

**Bangla Vowels:**
```
আ → a    ই → i    উ → u    এ → e    ও → o
অ → o    ঈ → ee   ঊ → oo   ঐ → oi   ঔ → ou
```

**Bangla Consonants:**
```
ক → k    খ → kh   গ → g    ঘ → gh   ঙ → ng
চ → ch   ছ → chh  জ → j    ঝ → jh   ঞ → ng
ট → t    ঠ → th   ড → d    ঢ → dh   ণ → n
ত → t    থ → th   দ → d    ধ → dh   ন → n
প → p    ফ → f    ব → b    ভ → bh   ম → m
য → y    র → r    ল → l    শ → sh   ষ → sh
স → s    হ → h    ড় → r   ঢ় → rh  য় → y
```

**Bangla Vowel Signs:**
```
া → a    ি → i    ী → ee   ু → u    ূ → oo
ৃ → ri   ে → e    ৈ → oi   ো → o    ৌ → ou
্ → (halant, removes vowel)
```

### Common Word Mappings

| Romanized | Bangla | English |
|-----------|--------|---------|
| bhat, bhaat | ভাত | rice |
| cha, chai | চা | tea |
| dal, daal | ডাল | lentil |
| mach | মাছ | fish |
| murgi | মুরগি | chicken |
| dim | ডিম | egg |
| doodh, dudh | দুধ | milk |
| aloo, alu | আলু | potato |
| peyaj, piaj | পেঁয়াজ | onion |
| rosun | রসুন | garlic |
| ada | আদা | ginger |
| morich | মরিচ | chili/pepper |
| nun | নুন | salt |
| chini | চিনি | sugar |
| tel | তেল | oil |
| ghee | ঘি | clarified butter |
| doi | দই | yogurt |
| panir | পনির | cheese |
| mangsho, mangso | মাংস | meat |
| shobji | সবজি | vegetable |
| fol | ফল | fruit |
| pani | পানি | water |

---

## Fuzzy Matching Details

### Algorithms Used

1. **Levenshtein Distance**
   - Measures minimum edit operations to transform one string to another
   - Operations: insertion, deletion, substitution
   - Score: `(1 - distance/maxLength) * 100`

2. **Similar Text Percentage**
   - PHP's `similar_text()` function
   - Returns percentage of similar characters
   - More lenient than Levenshtein

3. **Final Fuzzy Score**
   - Returns higher of Levenshtein and Similar Text scores
   - Ensures best possible match is found

### Example Matches

```
Query: "pottato"

Results:
- "potato" → 85% (1 letter difference)
- "potatoes" → 75% (2 letters + plural)
- "tomato" → 65% (different word, similar structure)
```

---

## Phonetic Variations

### English Phonetic Rules
```
ph ↔ f     (phone → fone)
v ↔ bh     (vat → bhat)
w ↔ v      (water → vater)
c ↔ k      (cat → kat)
z ↔ s      (zero → sero)
x → ks     (box → boks)
```

### Bengali Romanization Variations
```
oo ↔ u     (murgi ↔ moorgi)
ee ↔ i     (dim ↔ deem)
a ↔ aa     (bhat ↔ bhaat)
o ↔ ou     (doi ↔ dou)
```

---

## Performance Optimization

### Query Optimization Strategies

1. **Multi-stage approach**
   - Start with fast exact/partial matches
   - Only use expensive fuzzy matching if needed

2. **Early termination**
   - Skip fuzzy matching if 10+ results found in stages 1-3

3. **Database indexing** (recommended)
```sql
-- Add indexes to frequently searched columns
CREATE INDEX idx_products_name ON products(name);
CREATE INDEX idx_products_sku ON products(sku);
CREATE INDEX idx_products_category ON products(category_id);
CREATE FULLTEXT INDEX idx_products_description ON products(description);
```

4. **Caching** (future enhancement)
   - Cache common search queries
   - Cache Bangla transliteration results

---

## Usage Examples

### Example 1: Basic English Search
```javascript
fetch('/api/products/advanced-search', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer ' + token,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    query: 'rice',
    per_page: 10
  })
})
```

### Example 2: Bangla Unicode Search
```javascript
fetch('/api/products/advanced-search', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer ' + token,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    query: 'চাল',
    enable_fuzzy: true
  })
})
```

### Example 3: Romanized Bangla with Category Filter
```javascript
fetch('/api/products/advanced-search', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer ' + token,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    query: 'cha',
    category_id: 3,
    search_fields: ['name', 'description']
  })
})
```

### Example 4: Autocomplete Integration
```javascript
// Debounced autocomplete
let searchTimeout;
document.getElementById('searchInput').addEventListener('input', (e) => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    fetch(`/api/products/quick-search?q=${e.target.value}&limit=5`, {
      headers: { 'Authorization': 'Bearer ' + token }
    })
    .then(res => res.json())
    .then(data => {
      // Display suggestions
      displaySuggestions(data.data);
    });
  }, 300);
});
```

### Example 5: Misspelling Tolerance
```javascript
// User types "pottato" by mistake
fetch('/api/products/advanced-search', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer ' + token,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    query: 'pottato',
    enable_fuzzy: true,
    fuzzy_threshold: 70  // 70% similarity required
  })
})
// Will still find "potato" products
```

---

## Testing Guide

### Test Cases

1. **English Query**
```bash
POST /api/products/advanced-search
{"query": "chicken"}
Expected: Products with "chicken" in name/description
```

2. **Bangla Unicode**
```bash
POST /api/products/advanced-search
{"query": "মুরগি"}
Expected: Same chicken products
```

3. **Romanized Bangla**
```bash
POST /api/products/advanced-search
{"query": "murgi"}
Expected: Same chicken products
```

4. **Misspelling**
```bash
POST /api/products/advanced-search
{"query": "chiken", "enable_fuzzy": true}
Expected: Chicken products with fuzzy match
```

5. **Phonetic Variation**
```bash
POST /api/products/advanced-search
{"query": "cha"}
{"query": "chai"}
Expected: Both return tea products
```

6. **Category Filter**
```bash
POST /api/products/advanced-search
{"query": "aloo", "category_id": 5}
Expected: Only vegetables category potatoes
```

7. **Quick Search**
```bash
GET /api/products/quick-search?q=al&limit=5
Expected: Fast results starting with "al"
```

8. **Search Suggestions**
```bash
GET /api/products/search-suggestions?q=mu&limit=5
Expected: Suggestions like "murgi", "mushroom", etc.
```

---

## Future Enhancements

1. **Search History Tracking**
   - Log search queries to database
   - Analyze popular searches
   - Improve suggestions based on history

2. **Synonyms and Aliases**
   - Product name aliases table
   - Multi-language product names
   - Brand name variations

3. **Advanced NLP**
   - Intent detection
   - Entity extraction
   - Context-aware search

4. **Image Search**
   - Upload image to find similar products
   - OCR for product labels
   - Visual similarity matching

5. **Voice Search**
   - Speech-to-text integration
   - Accent-aware transcription
   - Multi-language voice input

6. **Machine Learning**
   - Personalized search ranking
   - Learning from user clicks
   - Automatic synonym detection

---

## Troubleshooting

### Common Issues

**Issue:** Bangla characters not displaying
- **Solution:** Ensure database charset is `utf8mb4`
- Check: `ALTER DATABASE database_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`

**Issue:** Fuzzy search too slow
- **Solution:** Reduce fuzzy_threshold or disable fuzzy for large datasets
- Consider caching or pre-computed similarities

**Issue:** No results for romanized queries
- **Solution:** Add more mappings to `$romanToBanglaCommon` array
- Verify transliteration logic

**Issue:** Too many irrelevant results
- **Solution:** Increase fuzzy_threshold (e.g., 75-80)
- Narrow search_fields scope

---

## API Response Codes

- `200` - Success
- `400` - Bad Request (invalid parameters)
- `401` - Unauthorized (missing/invalid token)
- `422` - Validation Error
- `500` - Server Error

---

## Security Considerations

1. **Input Sanitization**
   - All queries are sanitized through Laravel validation
   - SQL injection prevented by query builder

2. **Rate Limiting** (recommended)
```php
// In RouteServiceProvider or middleware
Route::middleware(['auth:api', 'throttle:60,1'])->group(function () {
    // Search routes
});
```

3. **Authentication**
   - All search endpoints require valid JWT token
   - User permissions can be added for sensitive searches

---

## Contact & Support

For questions or issues with the search system:
- Create an issue in the repository
- Contact the development team
- Check Laravel logs: `storage/logs/laravel.log`

---

**Version:** 1.0  
**Last Updated:** November 9, 2025  
**Controller:** `ProductSearchController.php`  
**Routes:** 4 search endpoints in `api.php`
