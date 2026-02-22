# Sale Campaign System Implementation

**Date:** February 22, 2026  
**Feature:** Automatic Discount & Sale Campaign System  
**Status:** ✅ Implemented

---

## Overview

আপনার request অনুযায়ী একটি complete *sale campaign system* implement করা হয়েছে যেটা system-wide automatic discount apply করতে পারবে। এই system টি POS, eCommerce, এবং social commerce সব জায়গায় কাজ করবে।

## What Was Implemented

### 1. **Automatic Campaign System**
- **Product-level discounts:** নির্দিষ্ট product গুলোতে discount দেওয়া যাবে
- **Category-level discounts:** পুরো category তে একসাথে discount দেওয়া যাবে
- **Percentage discount:** যেমন - 20% off
- **Fixed amount discount:** যেমন - ৫০০ টাকা কমানো
- **Start date & End date:** Campaign কবে শুরু হবে এবং কবে শেষ হবে (end date optional - indefinite campaigns এর জন্য)
- **Manual control:** যেকোনো সময় campaign activate/deactivate করা যাবে

### 2. **System-Wide Application**
এই discount system automatically apply হয় এই জায়গায়:

✅ **POS/Counter Orders**  
- Employee যখন order create করে, automatically discount calculate হয়
- Manual discount এর সাথে campaign discount add হয়ে যায়

✅ **eCommerce Orders**  
- Customer যখন checkout করে, automatically discount apply হয়
- Coupon code এর সাথে campaign discount both একসাথে কাজ করে

✅ **Product Catalog (eCommerce)**  
- Product listing এ discounted price দেখায়
- Original price + Discounted price দুইটাই থাকে
- "Was ৳1000, Now ৳800" এই format এ দেখানো possible

✅ **Social Commerce**  
- Same eCommerce order system use করে, তাই automatic discount পায়

### 3. **Database Changes**
একটি মাত্র নতুন field add করা হয়েছে existing `promotions` table এ:
- `is_automatic` (boolean) - এই flag দিয়ে বোঝা যায় এটা automatic campaign নাকি manual coupon code

**Note:** Existing promotions table ই use করা হয়েছে। নতুন table create করা হয়নি।

---

## How It Works

### Campaign Creation

1. **Employee Panel থেকে campaign create করুন:**
   - Campaign name দিন (যেমন: "Winter Sale 2026")
   - Discount type select করুন (percentage অথবা fixed)
   - Discount value দিন (যেমন: 20 বা 500)
   - **Products select করুন** (specific products এর জন্য) **অথবা**
   - **Categories select করুন** (whole category র জন্য)
   - Start date এবং optional end date দিন
   - `is_automatic = true` set করুন (automatic discount এর জন্য)
   - `is_active = true` set করুন (campaign চালু করার জন্য)

2. **Campaign শুরু হয়ে গেলে:**
   - নির্ধারিত products/categories এর সব orders এ automatically discount apply হবে
   - POS, eCommerce, social commerce সব জায়গায় কাজ করবে
   - Product catalog এ discounted price show করবে

3. **Campaign বন্ধ করার জন্য:**
   - `is_active = false` করে দিন (manual deactivation)
   - অথবা end date পার হয়ে গেলে automatically inactive হয়ে যাবে

### Discount Priority

যদি একই product এ multiple campaigns apply হয়:
- **Highest discount টা automatically select হবে**
- Manual discount (যদি থাকে) + Automatic discount = Total discount

যদি coupon code + automatic campaign দুইটাই থাকে:
- **Both combined হয়ে apply হবে**

---

## Real-World Examples

### Example 1: Category-Wide Sale (Saree Category 20% Off)

**Setup:**
```
Campaign Name: "Pohela Boishakh Saree Sale"
Type: percentage
Discount Value: 20
Applicable Categories: [5] (Saree category ID)
Start Date: 2026-04-14 00:00:00
End Date: 2026-04-16 23:59:59
is_automatic: true
is_active: true
```

**Result:**
- Saree category র সব products 20% discount পাবে
- POS এ order করলে automatically discount deduct হবে
- eCommerce catalog এ "Was ৳5000, Now ৳4000" show করবে
- Campaign শেষ হলে automatically discount বন্ধ হবে

---

### Example 2: Product-Specific Flash Sale (2 Products 500 TK Off)

**Setup:**
```
Campaign Name: "Flash Sale - Selected Items"
Type: fixed
Discount Value: 500
Applicable Products: [101, 102] (Product IDs)
Start Date: 2026-02-22 18:00:00
End Date: 2026-02-22 20:00:00
is_automatic: true
is_active: true
```

**Result:**
- Product #101 এবং #102 থেকে ৫০০ টাকা কমবে
- 6 PM থেকে 8 PM পর্যন্ত active থাকবে
- Time শেষ হলে automatically বন্ধ হবে

---

### Example 3: Indefinite Discount (No End Date)

**Setup:**
```
Campaign Name: "Permanent 10% Off on Electronics"
Type: percentage
Discount Value: 10
Applicable Categories: [3] (Electronics category)
Start Date: 2026-02-22 00:00:00
End Date: null (no end date)
is_automatic: true
is_active: true
```

**Result:**
- Electronics category তে সবসময় 10% discount থাকবে
- যতদিন চাইবেন ততদিন চলবে
- Manually deactivate করা ছাড়া বন্ধ হবে না

---

## Campaign Management

### Create Campaign
Employee panel থেকে promotion create করুন with `is_automatic = true`

### View Active Campaigns
- Active campaigns list দেখতে: filter by `is_active = true` and `valid_only = true`
- এই campaigns currently কোন products/categories তে apply হচ্ছে সেটা দেখা যাবে

### Deactivate Campaign
- Manual deactivation: `is_active = false` set করুন
- Immediate effect: order creation এ আর discount পাবে না
- Already created orders এ কোনো effect নেই

### Campaign Analytics
- Total discount দেওয়া হয়েছে কত
- কতবার use হয়েছে
- কোন products এ বেশি discount গিয়েছে

---

## Technical Implementation Summary

### Files Modified/Created:
1. **Migration:** `2026_02_22_150244_add_is_automatic_to_promotions_table.php`
2. **Service:** `app/Services/AutomaticDiscountService.php` (NEW)
3. **Model:** `app/Models/Promotion.php` (updated with `is_automatic` field)
4. **Controllers:**
   - `app/Http/Controllers/PromotionController.php` (added public campaign APIs)
   - `app/Http/Controllers/EcommerceOrderController.php` (automatic discount on checkout)
   - `app/Http/Controllers/OrderController.php` (automatic discount on POS orders)
   - `app/Http/Controllers/EcommerceCatalogController.php` (show discounted prices)
5. **Routes:** `routes/api.php` (added public campaign endpoints)

### Database Impact:
- **Existing table reused:** `promotions` table (just added 1 field)
- **No data migration needed:** existing data unaffected
- **Backward compatible:** existing coupon system still works

---

## Key Benefits

✅ **System-wide automatic application** - POS, eCommerce, social commerce সব জায়গায় কাজ করে  
✅ **Flexible targeting** - Product অথবা category level discount  
✅ **Multiple discount types** - Percentage বা fixed amount  
✅ **Date range control** - Start/end dates with optional indefinite campaigns  
✅ **Manual override** - যেকোনো সময় activate/deactivate  
✅ **Combined discounts** - Automatic + manual/coupon discounts একসাথে কাজ করে  
✅ **Real-time price display** - Catalog এ discounted price show করে  
✅ **Analytics ready** - Campaign performance track করা যায়

---

## Next Steps

1. **Frontend Integration:**
   - Employee panel এ campaign management UI
   - Product catalog এ discount badge/label display
   - Checkout page এ discount breakdown show

2. **Testing:**
   - Different discount scenarios test করুন
   - Multiple campaigns overlap test করুন
   - Date range validation test করুন

3. **Production Deployment:**
   - Migration run করুন: `php artisan migrate`
   - Existing promotion records check করুন
   - Test campaign create করে verify করুন

---

## Questions?

যদি কোনো confusion থাকে বা additional features দরকার হয়, আমাকে জানাবেন।

**Status:** Ready for FE integration ✅
