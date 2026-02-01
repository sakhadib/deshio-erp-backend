# Bulk Pathao Deployment Checklist

> **Date:** Feb 1, 2026 | **Time:** ~5 min

---

## 1. Run Migration

```bash
php artisan migrate
```

Creates: `pathao_bulk_batches` table

---

## 2. Queue Setup (Pick One)

### Option A: Database Queue (Recommended)

```bash
# In .env
QUEUE_CONNECTION=database

# Run once
php artisan queue:table
php artisan migrate
```

### Option B: Keep Sync (No Change)

If `QUEUE_CONNECTION=sync`, bulk jobs run immediately (blocking).  
Fine for small batches (<20).

---

## 3. Start Queue Worker (If using database queue)

### cPanel / SSH:

```bash
php artisan queue:work --queue=pathao --tries=3 --sleep=3
```

### Supervisor (Better):

```ini
[program:pathao-worker]
command=php /home/USER/public_html/artisan queue:work --queue=pathao --tries=3
numprocs=1
autostart=true
autorestart=true
```

---

## 4. Clear Cache

```bash
php artisan config:clear
php artisan cache:clear
```

---

## Done ✅

Test: `POST /api/shipments/bulk-send-to-pathao`
