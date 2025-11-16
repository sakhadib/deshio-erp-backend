<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_number',
        'transaction_date',
        'amount',
        'type',
        'account_id',
        'reference_type',
        'reference_id',
        'description',
        'store_id',
        'created_by',
        'metadata',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            if (empty($transaction->transaction_number)) {
                $transaction->transaction_number = static::generateTransactionNumber();
            }

            if (empty($transaction->transaction_date)) {
                $transaction->transaction_date = now()->toDateString();
            }
        });
    }

    // Relationships
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    // Scopes
    public function scopeDebit($query)
    {
        return $query->where('type', 'debit');
    }

    public function scopeCredit($query)
    {
        return $query->where('type', 'credit');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeByAccount($query, $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    public function scopeByStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeByReference($query, $referenceType, $referenceId)
    {
        return $query->where('reference_type', $referenceType)
                    ->where('reference_id', $referenceId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('transaction_date', now()->month)
                    ->whereYear('transaction_date', now()->year);
    }

    public function scopeThisYear($query)
    {
        return $query->whereYear('transaction_date', now()->year);
    }

    // Business logic methods
    public function complete(): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        $this->status = 'completed';
        return $this->save();
    }

    public function fail(string $reason = null): bool
    {
        if ($this->status === 'completed') {
            return false;
        }

        $this->status = 'failed';
        $this->metadata = array_merge($this->metadata ?? [], ['failure_reason' => $reason]);
        return $this->save();
    }

    public function cancel(string $reason = null): bool
    {
        if ($this->status === 'completed') {
            return false;
        }

        $this->status = 'cancelled';
        $this->metadata = array_merge($this->metadata ?? [], ['cancellation_reason' => $reason]);
        return $this->save();
    }

    public function isDebit(): bool
    {
        return $this->type === 'debit';
    }

    public function isCredit(): bool
    {
        return $this->type === 'credit';
    }

    // Static methods for creating transactions
    public static function createFromOrderPayment(OrderPayment $payment): self
    {
        $type = $payment->status === 'completed' ? 'debit' : 'pending';
        $status = $payment->status === 'completed' ? 'completed' : 'pending';

        return static::create([
            'transaction_date' => $payment->completed_at ?? $payment->processed_at ?? now(),
            'amount' => $payment->amount,
            'type' => 'debit', // Money coming into the business
            'account_id' => static::getCashAccountId($payment->store_id),
            'reference_type' => OrderPayment::class,
            'reference_id' => $payment->id,
            'description' => "Order Payment - {$payment->payment_number}",
            'store_id' => $payment->store_id,
            'created_by' => $payment->processed_by,
            'metadata' => [
                'payment_method' => $payment->paymentMethod->name ?? 'Unknown',
                'order_number' => $payment->order->order_number ?? null,
                'customer_name' => $payment->customer->name ?? null,
            ],
            'status' => $status,
        ]);
    }

    public static function createFromServiceOrderPayment(ServiceOrderPayment $payment): self
    {
        $type = $payment->status === 'completed' ? 'debit' : 'pending';
        $status = $payment->status === 'completed' ? 'completed' : 'pending';

        return static::create([
            'transaction_date' => $payment->completed_at ?? $payment->processed_at ?? now(),
            'amount' => $payment->amount,
            'type' => 'debit', // Money coming into the business
            'account_id' => static::getCashAccountId($payment->store_id),
            'reference_type' => ServiceOrderPayment::class,
            'reference_id' => $payment->id,
            'description' => "Service Order Payment - {$payment->payment_number}",
            'store_id' => $payment->store_id,
            'created_by' => $payment->processed_by,
            'metadata' => [
                'payment_method' => $payment->paymentMethod->name ?? 'Unknown',
                'service_order_number' => $payment->serviceOrder->order_number ?? null,
                'customer_name' => $payment->customer->name ?? null,
            ],
            'status' => $status,
        ]);
    }

    public static function createFromRefund(Refund $refund): self
    {
        $status = $refund->status === 'completed' ? 'completed' : 'pending';

        return static::create([
            'transaction_date' => $refund->completed_at ?? now(),
            'amount' => $refund->refund_amount,
            'type' => 'credit', // Money going out of the business
            'account_id' => static::getCashAccountId($refund->order->store_id ?? null),
            'reference_type' => Refund::class,
            'reference_id' => $refund->id,
            'description' => "Refund - {$refund->refund_number}",
            'store_id' => $refund->order->store_id ?? null,
            'created_by' => $refund->processed_by,
            'metadata' => [
                'refund_method' => $refund->refund_method,
                'order_number' => $refund->order->order_number ?? null,
                'customer_name' => $refund->customer->name ?? null,
                'refund_type' => $refund->refund_type,
            ],
            'status' => $status,
        ]);
    }

    public static function createFromExpense(Expense $expense): self
    {
        $status = $expense->payment_status === 'paid' ? 'completed' : 'pending';

        return static::create([
            'transaction_date' => $expense->expense_date,
            'amount' => $expense->total_amount,
            'type' => 'credit', // Money going out of the business
            'account_id' => static::getExpenseAccountId($expense->category_id),
            'reference_type' => Expense::class,
            'reference_id' => $expense->id,
            'description' => "Expense - {$expense->expense_number}: {$expense->title}",
            'store_id' => $expense->store_id,
            'created_by' => $expense->created_by,
            'metadata' => [
                'expense_category' => $expense->category->name ?? null,
                'vendor_name' => $expense->vendor->name ?? null,
                'expense_type' => $expense->expense_type,
            ],
            'status' => $status,
        ]);
    }

    public static function createFromExpensePayment(ExpensePayment $payment): self
    {
        $status = $payment->status === 'completed' ? 'completed' : 'pending';

        return static::create([
            'transaction_date' => $payment->completed_at ?? $payment->processed_at ?? now(),
            'amount' => $payment->amount,
            'type' => 'credit', // Money going out for expense payment
            'account_id' => static::getCashAccountId($payment->expense->store_id),
            'reference_type' => ExpensePayment::class,
            'reference_id' => $payment->id,
            'description' => "Expense Payment - {$payment->payment_number}",
            'store_id' => $payment->expense->store_id,
            'created_by' => $payment->processed_by,
            'metadata' => [
                'payment_method' => $payment->paymentMethod->name ?? 'Unknown',
                'expense_number' => $payment->expense->expense_number ?? null,
                'expense_title' => $payment->expense->title ?? null,
            ],
            'status' => $status,
        ]);
    }

    public static function createFromVendorPayment(VendorPayment $payment): self
    {
        $status = $payment->status === 'completed' ? 'completed' : 'pending';

        return static::create([
            'transaction_date' => $payment->processed_at ?? $payment->payment_date ?? now(),
            'amount' => $payment->amount,
            'type' => 'credit', // Money going out to vendor
            'account_id' => static::getCashAccountId(),
            'reference_type' => VendorPayment::class,
            'reference_id' => $payment->id,
            'description' => "Vendor Payment - {$payment->payment_number}",
            'created_by' => $payment->employee_id,
            'metadata' => [
                'payment_method' => $payment->paymentMethod->name ?? 'Unknown',
                'vendor_name' => $payment->vendor->name ?? null,
                'payment_type' => $payment->payment_type,
                'allocated_amount' => $payment->allocated_amount,
                'unallocated_amount' => $payment->unallocated_amount,
            ],
            'status' => $status,
        ]);
    }

    // Helper methods for account IDs
    private static function getCashAccountId($storeId = null): ?int
    {
        // Return default cash account ID - this should be configured
        return 1; // Placeholder - should be configurable
    }

    private static function getExpenseAccountId($categoryId): ?int
    {
        // Map expense categories to accounts - this should be configurable
        return 2; // Placeholder - should be configurable based on category
    }

    // Accessors
    public function getTypeColorAttribute(): string
    {
        return match ($this->type) {
            'debit' => 'success',
            'credit' => 'danger',
            default => 'secondary',
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'debit' => 'Debit',
            'credit' => 'Credit',
            default => 'Unknown',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'completed' => 'success',
            'pending' => 'warning',
            'failed' => 'danger',
            'cancelled' => 'secondary',
            default => 'secondary',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'completed' => 'Completed',
            'pending' => 'Pending',
            'failed' => 'Failed',
            'cancelled' => 'Cancelled',
            default => 'Unknown',
        };
    }

    public function getReferenceModelAttribute()
    {
        return $this->reference_type::find($this->reference_id);
    }

    // Static methods
    public static function generateTransactionNumber(): string
    {
        do {
            $transactionNumber = 'TXN-' . date('Ymd') . '-' . strtoupper(Str::random(8));
        } while (static::where('transaction_number', $transactionNumber)->exists());

        return $transactionNumber;
    }

    public static function getAccountBalance(int $accountId, $storeId = null, $endDate = null): float
    {
        $query = static::byAccount($accountId)->completed();

        if ($storeId) {
            $query->byStore($storeId);
        }

        if ($endDate) {
            $query->where('transaction_date', '<=', $endDate);
        }

        $debits = (clone $query)->debit()->sum('amount');
        $credits = (clone $query)->credit()->sum('amount');

        return $debits - $credits;
    }

    public static function getStoreBalance($storeId, $endDate = null): float
    {
        $query = static::byStore($storeId)->completed();

        if ($endDate) {
            $query->where('transaction_date', '<=', $endDate);
        }

        $debits = (clone $query)->debit()->sum('amount');
        $credits = (clone $query)->credit()->sum('amount');

        return $debits - $credits;
    }

    public static function getTrialBalance($storeId = null, $startDate = null, $endDate = null): array
    {
        $query = static::completed();

        if ($storeId) {
            $query->byStore($storeId);
        }

        if ($startDate && $endDate) {
            $query->byDateRange($startDate, $endDate);
        }

        $debits = (clone $query)->debit()->sum('amount');
        $credits = (clone $query)->credit()->sum('amount');

        return [
            'total_debits' => $debits,
            'total_credits' => $credits,
            'balance' => $debits - $credits,
            'in_balance' => abs($debits - $credits) < 0.01, // Allow for small floating point differences
        ];
    }
}
