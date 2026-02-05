# Deployment Checklist - February 2, 2026

## Changes Summary

| Feature | Files Changed |
|---------|---------------|
| PO Hard Delete | `PurchaseOrderController.php`, `routes/api.php` |
| PO PDF Reports | `PurchaseOrderController.php`, `composer.json`, `routes/api.php`, `resources/views/pdf/*` |
| Product Lookup (PO/Vendor Info) | `LookupController.php` |
| Pathao Payment Sync | `SyncPathaoStatus.php`, `Kernel.php`, `ShipmentController.php`, `Shipment.php`, `routes/api.php`, migration |

---

## Deploy Commands (Run in Order)

```bash
# 1. Pull latest code
git pull origin main

# 2. Install new packages (dompdf)
composer update

# 3. Run new migration (Pathao tracking fields)
php artisan migrate

# 4. Clear caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# 5. Ensure scheduler is running (for Pathao auto-sync)
# Add to crontab if not present:
# * * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

---

## New Files Created

```
app/Console/Commands/SyncPathaoStatus.php
database/migrations/2026_02_04_000001_add_pathao_payment_tracking_to_shipments.php
resources/views/pdf/purchase-order.blade.php
resources/views/pdf/purchase-orders-summary.blade.php
docs/2026-02-02/PURCHASE_ORDER_DELETE_API.md
docs/2026-02-02/PURCHASE_ORDER_PDF_REPORTS.md
docs/2026-02-02/PRODUCT_LOOKUP_PO_VENDOR_INFO.md
```

---

## New API Endpoints

| Method | Endpoint | Feature |
|--------|----------|---------|
| DELETE | `/api/purchase-orders/{id}` | PO Hard Delete |
| GET | `/api/purchase-orders/{id}/can-delete` | Check Delete Eligibility |
| GET | `/api/purchase-orders/{id}/pdf` | Individual PO PDF |
| GET | `/api/purchase-orders/report/pdf` | Summary Report PDF |
| POST | `/api/shipments/trigger-pathao-sync` | Manual Pathao Sync |
| GET | `/api/shipments/pathao-sync-stats` | Sync Statistics |

---

## Verify After Deploy

```bash
# Test Pathao sync command
php artisan pathao:sync-status --limit=5

# Check scheduler is registered
php artisan schedule:list
```
