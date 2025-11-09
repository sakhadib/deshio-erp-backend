# Client Product Search Examples

Based on your actual product inventory, here are real-world search examples demonstrating the multi-language search capabilities.

---

## 📦 Your Product Categories

1. **Sarees** (শাড়ি)
   - Jamdani, Cotton, Silk, Batik, Madhurai, Monipuri, Block, Grameen, Tontuj, Natural/Shiburi tie dye

2. **3-Piece Sets** (৩ পিস সেট)
   - Cotton, Silk, Batik, Jamdani, Block, Chunri chikonkari, Joypuri, Linen, Jom jom lone

3. **2-Piece Sets** (২ পিস সেট)
   - Jama + orna, Orna + salwar, Silk, Deshio ambodray

4. **Kurtis** (কুর্তি)
   - Deshio kurti (various price ranges), Linen kurti, Block dress

5. **Accessories** (এক্সেসরিজ)
   - Orna, Churi sets, Ornaments, Mobile bags, Cute hand bags

6. **Home Items** (ঘরের জিনিসপত্র)
   - Shotoronji, Jute mats, Basket sets, Table mats, Wall runners

---

## 🔍 Search Examples by Query Type

### 1. English Searches

#### Example 1: Search "jamdani saree"
```json
POST /api/products/advanced-search
{
  "query": "jamdani saree"
}
```
**Will Find:**
- Jamdani 1 piece
- Jamdani 3 Piece
- Jamdani saree 40 count
- Jamdani saree 60 count
- Jamdani saree 60&80 mix count
- Cotton jamdani 84 count
- Reshom halfsilk jamdani 80/84/100 count

#### Example 2: Search "silk"
```json
POST /api/products/advanced-search
{
  "query": "silk"
}
```
**Will Find:**
- Silk 2 piece 1450 BDT
- Silk batik 3 piece
- Silk batik saree
- Silk katan Saree
- Silk Madhurai Saree
- Reshom halfsilk jamdani (all counts)

#### Example 3: Search "block"
```json
POST /api/products/advanced-search
{
  "query": "block"
}
```
**Will Find:**
- Block 3 piece 990 BDT
- Block blouse piece
- Deshio block dress 990 BDT
- Deshio production block saree
- Exclusive block saree
- Exclusive block three piece

---

### 2. Bangla Unicode Searches

#### Example 4: Search "জামদানি" (Jamdani in Bangla)
```json
POST /api/products/advanced-search
{
  "query": "জামদানি"
}
```
**Will Find:** Same results as "jamdani" English search
- System automatically converts to Roman and matches

#### Example 5: Search "শাড়ি" (Saree in Bangla)
```json
POST /api/products/advanced-search
{
  "query": "শাড়ি"
}
```
**Will Find:** All saree products
- Cotton batik saree
- Cotton madurai saree
- Jamdani sarees
- Silk sarees
- Monipuri sarees
- Grameen check saree
- Tontuj sarees
- etc.

#### Example 6: Search "কুর্তি" (Kurti in Bangla)
```json
POST /api/products/advanced-search
{
  "query": "কুর্তি"
}
```
**Will Find:**
- Deshio kurti 800 BDT
- Deshio kurti 900 BDT
- Deshio kurti 950 BDT
- Deshio linen kurti 1050 BDT

---

### 3. Romanized Bangla Searches

#### Example 7: Search "jomdani" (Alternative spelling)
```json
POST /api/products/advanced-search
{
  "query": "jomdani"
}
```
**Will Find:** All jamdani products (same as "jamdani")
- System recognizes phonetic variation

#### Example 8: Search "sharee" (Alternative spelling)
```json
POST /api/products/advanced-search
{
  "query": "sharee"
}
```
**Will Find:** All saree products
- Recognizes as variant of "saree"

#### Example 9: Search "monupuri" (Misspelling)
```json
POST /api/products/advanced-search
{
  "query": "monupuri"
}
```
**Will Find:**
- High range monipuri saree
- Mid range monipuri saree
- Monipuri orna
- Offer monupuri saree 1550 BDT

---

### 4. Misspelling Tolerance

#### Example 10: Search "jamadani" (Common misspelling)
```json
POST /api/products/advanced-search
{
  "query": "jamadani",
  "enable_fuzzy": true,
  "fuzzy_threshold": 70
}
```
**Will Find:** All jamdani products
- Fuzzy matching handles the misspelling

#### Example 11: Search "sotoronji" (Missing 'h')
```json
POST /api/products/advanced-search
{
  "query": "sotoronji",
  "enable_fuzzy": true
}
```
**Will Find:**
- 3/2 feet shotoronji 580 BDT
- 4/2 feet shotoronji 780 BDT
- 4/2.5 feet shotoronji 1000 BDT
- 7/5 feet shotoronji 3650 BDT

#### Example 12: Search "batick" (Wrong spelling)
```json
POST /api/products/advanced-search
{
  "query": "batick",
  "enable_fuzzy": true
}
```
**Will Find:**
- Cotton batik 3 piece
- Cotton batik saree
- Silk batik 3 piece
- Silk batik saree
- Chunri chikonkari batik 3piece

---

### 5. Price Range Searches

#### Example 13: Search products by price mention
```json
POST /api/products/advanced-search
{
  "query": "1250 BDT"
}
```
**Will Find:**
- 2 piece (Jama + orna) 1250 BDT
- 2 piece (orna and salwar) 1250 BDT
- Deshio ambodray 2 piece 1250 BDT

#### Example 14: Search affordable items
```json
POST /api/products/advanced-search
{
  "query": "600 BDT"
}
```
**Will Find:**
- Basket set 600 BDT
- Ornaments 590 BDT (fuzzy match)

---

### 6. Category-Specific Searches

#### Example 15: Search within category
```json
POST /api/products/advanced-search
{
  "query": "3 piece",
  "category_id": 5,  // Assuming category_id for clothing
  "search_fields": ["name", "description"]
}
```
**Will Find:**
- All 3-piece clothing sets
- Block 3 piece 990 BDT
- Jamdani 3 Piece
- Cotton 3 piece
- Cotton batik 3 piece
- Silk batik 3 piece
- Exclusive block three piece
- Jom jom lone 3 piece
- Joypuri 3 piece
- Linen 3 piece

#### Example 16: Search Deshio products only
```json
POST /api/products/advanced-search
{
  "query": "deshio"
}
```
**Will Find:**
- Deshio ambodray 2 piece 1250 BDT
- Deshio block dress 990 BDT
- Deshio kurti 800 BDT
- Deshio kurti 900 BDT
- Deshio kurti 950 BDT
- Deshio linen kurti 1050 BDT
- Deshio production block saree

---

### 7. Accessory Searches

#### Example 17: Search bags
```json
POST /api/products/advanced-search
{
  "query": "bag"
}
```
**Will Find:**
- Cute hand bag
- Mobile bag 7/9 inch 260 BDT
- Mobile bag 9/16″ Inch 320 BDT

#### Example 18: Search ornaments
```json
POST /api/products/advanced-search
{
  "query": "churi"
}
```
**Will Find:**
- Churi 4 Piece set 250 BDT

---

### 8. Home Decor Searches

#### Example 19: Search mats
```json
POST /api/products/advanced-search
{
  "query": "mat"
}
```
**Will Find:**
- 3/2 feet jute mat 650 BDT
- 4/2.5 feet jute mat 850 BDT
- Regular table mat 800 BDT

#### Example 20: Search by material
```json
POST /api/products/advanced-search
{
  "query": "jute"
}
```
**Will Find:**
- 3/2 feet jute mat 650 BDT
- 4/2.5 feet jute mat 850 BDT

---

### 9. Count/Quality Searches

#### Example 21: Search specific thread count
```json
POST /api/products/advanced-search
{
  "query": "60 count"
}
```
**Will Find:**
- Jamdani saree 60 count
- Jamdani saree 60&80 mix count

#### Example 22: Search high-quality items
```json
POST /api/products/advanced-search
{
  "query": "100 count"
}
```
**Will Find:**
- Reshom halfsilk jamdani 100 count

---

### 10. Combo/Multi-Word Searches

#### Example 23: Search material + type
```json
POST /api/products/advanced-search
{
  "query": "cotton batik"
}
```
**Will Find:**
- Cotton batik 3 piece
- Cotton batik saree

#### Example 24: Search brand + product
```json
POST /api/products/advanced-search
{
  "query": "deshio kurti"
}
```
**Will Find:**
- Deshio kurti 800 BDT
- Deshio kurti 900 BDT
- Deshio kurti 950 BDT
- Deshio linen kurti 1050 BDT

---

## 🎯 Advanced Search Patterns

### Pattern 1: Find Affordable Sarees
```json
POST /api/products/advanced-search
{
  "query": "saree",
  "search_fields": ["name", "description"],
  "per_page": 20
}
```
Then filter by price range in application logic or add price filters to API.

### Pattern 2: Find Exclusive/Premium Items
```json
POST /api/products/advanced-search
{
  "query": "exclusive"
}
```
**Will Find:**
- Exclusive block saree
- Exclusive block three piece

### Pattern 3: Find Offer Products
```json
POST /api/products/advanced-search
{
  "query": "offer"
}
```
**Will Find:**
- Offer monupuri saree 1550 BDT

### Pattern 4: Size-Based Search
```json
POST /api/products/advanced-search
{
  "query": "7/5 feet"
}
```
**Will Find:**
- 7/5 feet shotoronji 3650 BDT

---

## 💡 Search Tips for Your Team

### For Counter Sales
```javascript
// Quick search as customer speaks
GET /api/products/quick-search?q=jamd&limit=5

// Full search after confirmation
POST /api/products/advanced-search
{
  "query": "jamdani 60 count",
  "enable_fuzzy": true
}
```

### For Inventory Management
```javascript
// Find all products in a category
POST /api/products/advanced-search
{
  "query": "saree",
  "search_fields": ["name"],
  "per_page": 100
}
```

### For Online Orders
```javascript
// Customer searching in Bangla
POST /api/products/advanced-search
{
  "query": "জামদানি শাড়ি",  // Will work perfectly
  "enable_fuzzy": true,
  "fuzzy_threshold": 65
}
```

---

## 🔄 Common Query Variations Handled

| Customer Says | System Understands |
|--------------|-------------------|
| "jamdani" | jamdani, jomdani, jamadhani |
| "saree" | saree, sari, sharee, শাড়ি |
| "monipuri" | monipuri, manipuri, manupuri |
| "kurti" | kurti, kurty, kurta, কুর্তি |
| "batik" | batik, batick, batyk |
| "block" | block, blok |
| "silk" | silk, reshom, রেশম |
| "cotton" | cotton, katun, কটন |
| "piece" | piece, pis, pc |
| "shotoronji" | shotoronji, sataranji, shatranji |

---

## 📊 Expected Search Performance

### Fast Searches (< 100ms)
- Quick search with 2-3 character query
- Exact match searches
- SKU lookups

### Medium Searches (100-300ms)
- Advanced search with filters
- Multi-word queries
- Category-filtered searches

### Slower Searches (300-500ms)
- Fuzzy matching enabled with large product catalog
- Complex multi-field searches
- Custom field searches

**Optimization Tip:** Use quick-search for autocomplete, advanced-search for final results.

---

## 🧪 Testing Checklist

Test these scenarios with your actual products:

- [ ] Search "jamdani" - verify all jamdani products appear
- [ ] Search "জামদানি" (Bangla) - same results as above
- [ ] Search "jomdani" (misspelling) - fuzzy match finds jamdani
- [ ] Search "3 piece" - all 3-piece sets appear
- [ ] Search "deshio" - all Deshio brand products
- [ ] Search "600" - products around that price
- [ ] Search "silk batik" - finds silk batik products
- [ ] Search "monupuri" - finds manipuri products
- [ ] Search "kurty" - finds kurti products
- [ ] Search "sotoronji" - fuzzy finds shotoronji

---

## 📱 Mobile App Integration Example

```javascript
// React Native / Flutter / Android / iOS
async function searchProducts(query) {
  const response = await fetch('https://api.yourcompany.com/api/products/advanced-search', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      query: query,
      enable_fuzzy: true,
      fuzzy_threshold: 65,
      per_page: 20
    })
  });
  
  const data = await response.json();
  
  if (data.success) {
    // Display data.data.items
    return data.data.items;
  }
}

// Usage
searchProducts("জামদানি শাড়ি");  // Works in Bangla
searchProducts("jamdani saree");   // Works in English
searchProducts("jomdani sharee");  // Works with misspelling
```

---

## 🎓 Training Your Sales Team

### Quick Reference Card for Staff

**English customers:**
- Just type what they say: "silk saree", "block dress"

**Bangla-speaking customers:**
- Type in Bangla: "শাড়ি", "কুর্তি"
- OR use English keyboard: "sharee", "kurti"

**If not finding:**
1. Try alternate spelling (jamdani → jomdani)
2. Try just the main word (remove "deshio", "exclusive")
3. Check "Enable Fuzzy" option
4. Lower the fuzzy threshold to 50-60

**Pro Tips:**
- Search "3 piece" to see all 3-piece sets
- Search by price: "1250" or "1250 BDT"
- Search by material: "cotton", "silk", "jute"
- Search by brand: "deshio"

---

**Version:** 1.0  
**Based on:** Client's actual product inventory  
**Last Updated:** November 9, 2025  
**Total Product Examples:** 63 unique items
