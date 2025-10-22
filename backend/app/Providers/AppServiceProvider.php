<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\OrderPayment;
use App\Models\ServiceOrderPayment;
use App\Models\Refund;
use App\Models\Expense;
use App\Observers\OrderPaymentObserver;
use App\Observers\ServiceOrderPaymentObserver;
use App\Observers\RefundObserver;
use App\Observers\ExpenseObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register observers for automatic transaction creation
        OrderPayment::observe(OrderPaymentObserver::class);
        ServiceOrderPayment::observe(ServiceOrderPaymentObserver::class);
        Refund::observe(RefundObserver::class);
        Expense::observe(ExpenseObserver::class);
    }
}
