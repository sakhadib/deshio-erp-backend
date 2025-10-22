<?php

namespace App\Observers;

use App\Models\Expense;
use App\Models\Transaction as AccountingTransaction;

class ExpenseObserver
{
    /**
     * Handle the Expense "created" event.
     */
    public function created(Expense $expense): void
    {
        // Create transaction when expense is created (if it's already approved/paid)
        if ($expense->status === 'approved' || $expense->payment_status === 'paid') {
            AccountingTransaction::createFromExpense($expense);
        }
    }

    /**
     * Handle the Expense "updated" event.
     */
    public function updated(Expense $expense): void
    {
        // Check if status changed to approved
        if ($expense->wasChanged('status') && $expense->status === 'approved') {
            // Find existing transaction or create new one
            $transaction = AccountingTransaction::byReference(Expense::class, $expense->id)->first();

            if (!$transaction) {
                // Create new transaction for approved expense
                AccountingTransaction::createFromExpense($expense);
            }
        }

        // Check if payment status changed to paid
        if ($expense->wasChanged('payment_status') && $expense->payment_status === 'paid') {
            // Find existing transaction or create new one
            $transaction = AccountingTransaction::byReference(Expense::class, $expense->id)->first();

            if ($transaction) {
                // Update existing transaction
                $transaction->update([
                    'status' => 'completed',
                    'transaction_date' => $expense->expense_date,
                ]);
            } else {
                // Create new transaction if it doesn't exist
                AccountingTransaction::createFromExpense($expense);
            }
        }
    }

    /**
     * Handle the Expense "deleted" event.
     */
    public function deleted(Expense $expense): void
    {
        // Mark related transactions as cancelled
        AccountingTransaction::byReference(Expense::class, $expense->id)
            ->update(['status' => 'cancelled']);
    }

    /**
     * Handle the Expense "restored" event.
     */
    public function restored(Expense $expense): void
    {
        // Restore related transactions based on current expense status
        $status = ($expense->status === 'approved' || $expense->payment_status === 'paid') ? 'completed' : 'pending';

        AccountingTransaction::byReference(Expense::class, $expense->id)
            ->update(['status' => $status]);
    }

    /**
     * Handle the Expense "force deleted" event.
     */
    public function forceDeleted(Expense $expense): void
    {
        // Permanently delete related transactions
        AccountingTransaction::byReference(Expense::class, $expense->id)->delete();
    }
}
