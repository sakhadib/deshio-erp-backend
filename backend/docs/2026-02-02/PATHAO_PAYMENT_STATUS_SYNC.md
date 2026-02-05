# Pathao Payment Status Auto-Sync

## Overview

This feature automatically syncs shipment status and COD payment information from Pathao API. It runs as a scheduled task and can also be triggered manually.

---

## How It Works

### 1. Scheduled Sync (Automatic)

The system automatically syncs Pathao status:
- **Every 30 minutes**: Syncs up to 200 pending shipments
- **Daily at 6 AM**: Full sync of last 7 days (up to 500 shipments)

### 2. What Gets Synced

| Data | Description |
|------|-------------|
| `pathao_status` | Current status from Pathao (Pending, Picked_up, Delivered, etc.) |
| `status` | Local shipment status (mapped from Pathao status) |
| `cod_collected` | Whether COD was collected (for delivered orders) |
| `cod_collected_amount` | Actual amount collected |
| `cod_collected_at` | When COD was collected |
| `pathao_last_synced_at` | Last sync timestamp |

### 3. COD Payment Recording

When Pathao marks an order as **Delivered**:
1. System marks `cod_collected = true` on shipment
2. Creates an `OrderPayment` record with:
   - Payment method: "Cash on Delivery"
   - Reference: `PATHAO-COD-{consignment_id}`
   - Status: `completed`
3. Updates order's `payment_status` automatically

---

## Pathao Status Mapping

| Pathao Status | Local Status |
|---------------|--------------|
| Pending | pending |
| Pickup_Pending | pickup_requested |
| Pickup_Request_Accepted | pickup_requested |
| Picked_up | picked_up |
| Reached_at_Pathao_Warehouse | picked_up |
| In_transit | in_transit |
| Out_For_Delivery | in_transit |
| Delivered | delivered |
| Partial_Delivery | delivered |
| Return | returned |
| Return_In_Transit | returned |
| Returned | returned |
| Cancelled | cancelled |
| Hold | pending |

---

## API Endpoints

### 1. Manual Sync Trigger

**Endpoint:** `POST /api/shipments/trigger-pathao-sync`

**Body (Optional):**
```json
{
  "limit": 100,
  "days": 30
}
```

**Response:**
```json
{
  "success": true,
  "message": "Pathao sync triggered for up to 100 shipments from last 30 days",
  "note": "Sync is running in background. Check pathao-sync-stats for progress."
}
```

### 2. Get Sync Statistics

**Endpoint:** `GET /api/shipments/pathao-sync-stats`

**Response:**
```json
{
  "success": true,
  "data": {
    "total_pathao_shipments": 1250,
    "by_status": {
      "pending": 50,
      "pickup_requested": 30,
      "in_transit": 100,
      "delivered": 1000,
      "returned": 50,
      "cancelled": 20
    },
    "by_pathao_status": {
      "Pending": 50,
      "Picked_up": 30,
      "In_transit": 100,
      "Delivered": 1000,
      "Returned": 50,
      "Cancelled": 20
    },
    "pending_sync": 180,
    "cod_stats": {
      "total_cod_shipments": 800,
      "cod_collected": 700,
      "cod_pending": 80,
      "total_cod_amount": "1250000.00"
    },
    "last_sync": "2026-02-04T10:30:00.000000Z",
    "never_synced": 5,
    "synced_last_24h": 150
  }
}
```

### 3. Sync Individual Shipment

**Endpoint:** `GET /api/shipments/{id}/sync-pathao-status`

**Response:**
```json
{
  "success": true,
  "message": "Status synced successfully",
  "data": {
    "old_status": "In_transit",
    "new_status": "Delivered",
    "local_status": "delivered"
  }
}
```

### 4. Bulk Sync

**Endpoint:** `POST /api/shipments/bulk-sync-pathao-status`

**Body (Optional):**
```json
{
  "shipment_ids": [1, 2, 3, 4, 5]
}
```

If `shipment_ids` not provided, syncs all pending shipments.

---

## Artisan Command

Run sync manually via command line:

```bash
# Default: 100 shipments, last 30 days
php artisan pathao:sync-status

# Custom limits
php artisan pathao:sync-status --limit=500 --days=7

# Force sync all (including delivered/cancelled)
php artisan pathao:sync-status --force
```

**Output:**
```
🚀 Starting Pathao status sync...
Found 150 shipments to sync
 150/150 [============================] 100%

📊 Sync Summary:
+------------------+-------+
| Metric           | Count |
+------------------+-------+
| Total Processed  | 150   |
| Successfully Synced | 148 |
| Status Updated   | 45    |
| Payment Updated  | 30    |
| Failed           | 2     |
+------------------+-------+
```

---

## Database Changes

### New Migration: `2026_02_04_000001_add_pathao_payment_tracking_to_shipments.php`

Added fields to `shipments` table:
- `cod_collected` (boolean) - Whether COD was collected
- `cod_collected_amount` (decimal) - Actual collected amount
- `cod_collected_at` (timestamp) - When collected
- `pathao_last_synced_at` (timestamp) - Last sync time
- `pathao_payment_status` (string) - Payment status from Pathao

**Run migration:**
```bash
php artisan migrate
```

---

## Scheduler Setup

For production, ensure the Laravel scheduler runs:

```bash
# Add to crontab
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

Or on Windows Task Scheduler, run every minute:
```
php artisan schedule:run
```

---

## Frontend Integration

### Dashboard Widget

```jsx
function PathaoSyncStats() {
  const [stats, setStats] = useState(null);
  
  useEffect(() => {
    fetch('/api/shipments/pathao-sync-stats', {
      headers: { 'Authorization': `Bearer ${token}` }
    })
    .then(res => res.json())
    .then(data => setStats(data.data));
  }, []);
  
  if (!stats) return <Loading />;
  
  return (
    <div className="pathao-stats">
      <StatCard title="Total Shipments" value={stats.total_pathao_shipments} />
      <StatCard title="Pending Sync" value={stats.pending_sync} />
      <StatCard title="COD Collected" value={stats.cod_stats.cod_collected} />
      <StatCard title="COD Amount" value={`৳${stats.cod_stats.total_cod_amount}`} />
      <StatCard title="Last Sync" value={formatDate(stats.last_sync)} />
    </div>
  );
}
```

### Manual Sync Button

```jsx
const handleManualSync = async () => {
  setLoading(true);
  try {
    const response = await fetch('/api/shipments/trigger-pathao-sync', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ limit: 200, days: 7 })
    });
    const data = await response.json();
    toast.success(data.message);
  } catch (error) {
    toast.error('Failed to trigger sync');
  }
  setLoading(false);
};
```

---

## Troubleshooting

### Sync Not Running

1. Check if scheduler is running:
   ```bash
   php artisan schedule:list
   ```

2. Check logs:
   ```bash
   tail -f storage/logs/laravel.log | grep -i pathao
   ```

### COD Not Recording

1. Verify payment method exists:
   ```sql
   SELECT * FROM payment_methods WHERE code = 'cod';
   ```

2. Check order has COD amount:
   ```sql
   SELECT id, cod_amount, amount_to_collect FROM shipments WHERE pathao_consignment_id = 'XXX';
   ```

### API Rate Limiting

The sync has a 200ms delay between requests to avoid Pathao rate limits. If you see many failures, consider increasing the delay in `SyncPathaoStatus.php`:

```php
usleep(500000); // 500ms delay
```

---

## Logs

Sync activity is logged to `storage/logs/laravel.log`:

- Info: "Pathao sync completed" with stats
- Info: "COD payment recorded from Pathao sync"
- Error: "Pathao sync error" with details
- Error: "Failed to record COD payment"
